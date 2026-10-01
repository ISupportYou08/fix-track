<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResubmitTechnicianApplicationRequest;
use App\Models\ServiceCatalog;
use App\Models\TechnicianVerification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ApplicationResubmissionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ResubmitTechnicianApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $technician = $request->user();
        abort_unless($technician instanceof User, 403);

        /** @var array<string, array{label: string, path: string}> $replacementDocuments */
        $replacementDocuments = [];
        $replacedDocumentPaths = [];

        try {
            foreach (['valid_id', 'credentials'] as $inputName) {
                if (! isset($validated[$inputName])) {
                    continue;
                }

                $uploadedDocument = $validated[$inputName];
                $path = $uploadedDocument->store("technician-documents/{$technician->id}", 'local');

                if (! is_string($path)) {
                    throw new RuntimeException("The {$inputName} document could not be stored.");
                }

                $replacementDocuments[$inputName] = [
                    'label' => $uploadedDocument->getClientOriginalName(),
                    'path' => $path,
                ];
            }

            DB::transaction(function () use ($technician, $validated, $replacementDocuments, &$replacedDocumentPaths): void {
                $verification = TechnicianVerification::query()
                    ->whereBelongsTo($technician, 'technician')
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(in_array($verification->status, ['rejected', 'information_requested'], true), 422, 'This application is not available for resubmission.');

                $verification->fill([
                    'status' => 'submitted',
                    'service_categories' => ServiceCatalog::normalizeCodes($validated['service_categories']),
                    'years_experience' => $validated['years_experience'],
                    'service_area' => $validated['service_area'],
                    'service_type' => $validated['service_type'],
                    'shop_name' => in_array($validated['service_type'], ['walkin', 'both'], true) ? $validated['shop_name'] : null,
                    'phone' => $validated['phone'],
                    'address' => $validated['service_area'],
                    'resubmission_notes' => $validated['resubmission_notes'],
                    'reviewer_id' => null,
                    'reviewed_at' => null,
                    'submitted_at' => now(),
                ]);
                $verification->resubmission_count++;
                $verification->save();

                foreach ($replacementDocuments as $type => $documentData) {
                    $document = $verification->documents()->firstOrNew(['type' => $type]);

                    if ($document->exists && filled($document->file_path)) {
                        $replacedDocumentPaths[] = $document->file_path;
                    }

                    $document->fill([
                        'label' => $documentData['label'],
                        'file_path' => $documentData['path'],
                        'status' => 'pending',
                        'masked_number' => null,
                        'expires_at' => null,
                    ])->save();
                }

                $technician->update([
                    'name' => Str::of(implode(' ', array_filter([
                        $validated['first_name'],
                        $validated['middle_name'] ?? null,
                        $validated['surname'],
                    ])))->squish()->toString(),
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'] ?? null,
                    'surname' => $validated['surname'],
                    'phone' => $validated['phone'],
                    'account_status' => User::ACCOUNT_REVIEW_PENDING,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_column($replacementDocuments, 'path'));

            throw $exception;
        }

        Storage::disk('local')->delete($replacedDocumentPaths);

        return redirect()->route('technician.module')->with('status', 'Your corrected application was resubmitted for staff review.');
    }
}
