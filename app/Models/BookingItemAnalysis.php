<?php

namespace App\Models;

use Database\Factories\BookingItemAnalysisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_id
 * @property string $image_path
 * @property string $image_original_name
 * @property string $image_mime_type
 * @property array<int, array{path: string, original_name: string, mime_type: string}>|null $images
 * @property string $detected_item_name
 * @property string $suggested_service_code
 * @property string $suggested_service_category
 * @property string|float $confidence
 * @property string|null $explanation
 * @property string $model
 * @property Carbon $confirmed_at
 */
class BookingItemAnalysis extends Model
{
    /** @use HasFactory<BookingItemAnalysisFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'image_path',
        'image_original_name',
        'image_mime_type',
        'images',
        'detected_item_name',
        'suggested_service_code',
        'suggested_service_category',
        'confidence',
        'explanation',
        'model',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:4',
            'confirmed_at' => 'datetime',
            'images' => 'array',
        ];
    }

    /** @return array<int, array{path: string, original_name: string, mime_type: string}> */
    public function imageFiles(): array
    {
        if (is_array($this->images) && $this->images !== []) {
            return $this->images;
        }

        return [[
            'path' => $this->image_path,
            'original_name' => $this->image_original_name,
            'mime_type' => $this->image_mime_type,
        ]];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
