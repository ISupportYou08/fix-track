<?php

namespace App\Actions\Bookings;

use App\Models\ServiceCatalog;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class AnalyzeServiceItemImage
{
    /**
     * @param  array<int, UploadedFile>  $images
     * @param  Collection<int, ServiceCatalog>  $services
     * @return array{supported: bool, detected_item_name: string, service_code: string, service_category: string, confidence: float, explanation: string, model: string}
     */
    public function analyze(array $images, Collection $services): array
    {
        $apiToken = config('services.cloudflare.api_token');
        $accountId = config('services.cloudflare.account_id');
        $model = (string) config('services.cloudflare.vision_model');

        if (! is_string($apiToken) || trim($apiToken) === '' || ! is_string($accountId) || trim($accountId) === '') {
            throw new RuntimeException('AI image analysis is not configured.');
        }

        if (trim($model) === '') {
            throw new RuntimeException('No AI vision model is configured.');
        }

        if (count($images) !== 3) {
            throw new RuntimeException('Choose exactly three item images.');
        }

        if ($services->isEmpty()) {
            throw new RuntimeException('No active services are available for image matching.');
        }

        $catalog = $services
            ->map(static fn (ServiceCatalog $service): array => [
                'code' => (string) $service->code,
                'name' => (string) $service->name,
                'category' => (string) $service->category,
                'description' => (string) $service->description,
            ])
            ->values()
            ->all();

        $prompt = 'Identify the main repairable item in this image and match it to exactly one service from the supplied FixTrack catalog: '
            .json_encode($catalog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            .'. Never invent a service code or category. Set supported to false and leave service_code empty when the image is unclear, contains no repairable item, or no catalog service applies. Identify the item only; do not claim to diagnose a fault that is not visibly established. Return only a JSON object with these exact fields: supported (boolean), detected_item_name (string), service_code (string), confidence (number from 0 to 1), and explanation (string). Do not use Markdown code fences.';
        $url = sprintf(
            'https://api.cloudflare.com/client/v4/accounts/%s/ai/run/%s',
            rawurlencode(trim($accountId)),
            ltrim($model, '/'),
        );
        $payloads = collect($images)->map(function (UploadedFile $image) use ($prompt): array {
            $imageContents = file_get_contents($image->getRealPath());

            if ($imageContents === false) {
                throw new RuntimeException('A selected image could not be read.');
            }

            return [
                'prompt' => $prompt,
                'image' => 'data:'.($image->getMimeType() ?: 'image/jpeg').';base64,'.base64_encode($imageContents),
                'max_tokens' => 400,
                'temperature' => 0.1,
            ];
        });
        $responses = Http::pool(
            fn (Pool $pool): array => $payloads
                ->map(fn (array $payload) => $pool
                    ->withToken($apiToken)
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(20)
                    ->post($url, $payload))
                ->all(),
            concurrency: 3,
        );
        $successfulResponses = collect($responses)
            ->filter(static fn (mixed $response): bool => $response instanceof Response
                && $response->successful()
                && $response->json('success') === true);

        if ($successfulResponses->isEmpty()) {
            throw new RuntimeException('The image analysis service is temporarily unavailable.');
        }

        $analyses = $successfulResponses
            ->map(function (Response $response): ?array {
                try {
                    return $this->decodeResponse($response);
                } catch (RuntimeException) {
                    return null;
                }
            })
            ->filter()
            ->values()
            ->all();

        if ($analyses === []) {
            return [
                'supported' => false,
                'detected_item_name' => '',
                'service_code' => '',
                'service_category' => '',
                'confidence' => 0,
                'explanation' => 'The three images could not be confidently matched to an available service.',
                'model' => $model,
            ];
        }

        return $this->combineAnalyses($analyses, $services, $model);
    }

    /** @return array<string, mixed> */
    private function decodeResponse(Response $response): array
    {
        if (! $response->successful() || $response->json('success') !== true) {
            throw new RuntimeException('The image analysis service is temporarily unavailable.');
        }

        $outputText = (string) $response->json('result.response', '');

        if (trim($outputText) === '') {
            throw new RuntimeException('The image analysis service returned an empty result.');
        }

        return $this->decodeAnalysis($outputText);
    }

    /**
     * @param  array<int, array<string, mixed>>  $analyses
     * @param  Collection<int, ServiceCatalog>  $services
     * @return array{supported: bool, detected_item_name: string, service_code: string, service_category: string, confidence: float, explanation: string, model: string}
     */
    private function combineAnalyses(array $analyses, Collection $services, string $model): array
    {
        $normalizedAnalyses = collect($analyses)->map(function (array $analysis) use ($services): array {
            $detectedItemName = Str::limit(trim((string) ($analysis['detected_item_name'] ?? '')), 120, '');
            $serviceCode = trim((string) ($analysis['service_code'] ?? ''));
            $service = $services->firstWhere('code', $serviceCode);
            $confidence = max(0, min(1, (float) ($analysis['confidence'] ?? 0)));
            $hasCatalogMatch = $detectedItemName !== '' && $service instanceof ServiceCatalog;
            $supported = ($analysis['supported'] ?? false) === true
                && $hasCatalogMatch;

            return [
                'supported' => $supported,
                'detected_item_name' => $detectedItemName,
                'service_code' => $hasCatalogMatch ? (string) $service->code : '',
                'service_category' => $hasCatalogMatch ? (string) $service->category : '',
                'confidence' => $confidence,
                'explanation' => Str::limit(trim((string) ($analysis['explanation'] ?? '')), 500, ''),
            ];
        });

        $supportedAnalyses = $normalizedAnalyses->where('supported', true);

        if ($supportedAnalyses->isEmpty()) {
            $bestUnsupportedAnalysis = $normalizedAnalyses->sortByDesc('confidence')->first();

            return [...$bestUnsupportedAnalysis, 'model' => $model];
        }

        $winningAnalyses = $supportedAnalyses
            ->groupBy('service_code')
            ->sortByDesc(static fn (Collection $group): float => ($group->count() * 10) + (float) $group->avg('confidence'))
            ->first();
        $bestAnalysis = $winningAnalyses->sortByDesc('confidence')->first();

        return [
            ...$bestAnalysis,
            'confidence' => round((float) $winningAnalyses->avg('confidence'), 4),
            'model' => $model,
        ];
    }

    /** @return array<string, mixed> */
    private function decodeAnalysis(string $outputText): array
    {
        preg_match_all('/\{[^{}]*\}/s', $outputText, $matches);

        $candidates = collect([$outputText, ...array_reverse($matches[0] ?? [])])
            ->map(static fn (string $candidate): string => trim($candidate))
            ->filter()
            ->unique();

        foreach ($candidates as $candidate) {
            try {
                $analysis = json_decode($candidate, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (is_array($analysis)
                && array_key_exists('supported', $analysis)
                && array_key_exists('detected_item_name', $analysis)
                && array_key_exists('service_code', $analysis)
                && array_key_exists('confidence', $analysis)
                && array_key_exists('explanation', $analysis)) {
                return $analysis;
            }
        }

        throw new RuntimeException('The image analysis result could not be understood.');
    }
}
