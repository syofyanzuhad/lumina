<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('domain') && is_string($this->domain)) {
            $domain = $this->domain;

            // Remove protocol
            $domain = (string) preg_replace('#^https?://#i', '', $domain);

            // Remove www.
            $domain = (string) preg_replace('#^www\.#i', '', $domain);

            // Remove path and trailing slash (keep only domain/host)
            $domain = explode('/', $domain)[0];

            $this->merge([
                'domain' => strtolower($domain),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/',
                Rule::unique('sites', 'domain')->where('owner_id', $this->user()->id),
            ],
        ];
    }
}
