<?php

namespace App\Http\Requests;

use App\Models\ServiceCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteGoogleRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $commonRules = [
            'role' => ['required', Rule::in(['customer', 'technician'])],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        if ($this->string('role')->toString() === 'technician') {
            $serviceCodes = ServiceCatalog::activeCodes();

            return [
                ...$commonRules,
                'service_category' => ['nullable', 'string', Rule::in($serviceCodes)],
                'service_categories' => ['required', 'array', 'min:1'],
                'service_categories.*' => ['string', Rule::in($serviceCodes)],
                'service_area' => ['required', 'string', 'max:255'],
                'service_type' => ['required', Rule::in(['home', 'walkin', 'both'])],
                'shop_name' => [
                    'nullable',
                    Rule::requiredIf(fn (): bool => in_array($this->input('service_type'), ['walkin', 'both'], true)),
                    'string',
                    'max:255',
                ],
                'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
                'valid_id' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
                'credentials' => ['required', 'file', 'mimes:pdf,doc,docx', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'max:5120'],
            ];
        }

        return [
            ...$commonRules,
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('service_categories') && $this->filled('service_category')) {
            $this->merge(['service_categories' => [$this->input('service_category')]]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'valid_id.uploaded' => 'The valid ID could not be uploaded. Use a JPG, PNG, or WEBP image no larger than 5 MB.',
            'valid_id.max' => 'The valid ID must not be larger than 5 MB.',
            'valid_id.mimes' => 'The valid ID must be a JPG, PNG, or WEBP image.',
            'valid_id.mimetypes' => 'The valid ID must be a JPG, PNG, or WEBP image.',
            'credentials.uploaded' => 'The credentials could not be uploaded. Use a PDF, DOC, or DOCX file no larger than 5 MB.',
            'credentials.max' => 'The credentials must not be larger than 5 MB.',
            'credentials.mimes' => 'The credentials must be a PDF, DOC, or DOCX file.',
            'credentials.mimetypes' => 'The credentials must be a PDF, DOC, or DOCX file.',
        ];
    }
}
