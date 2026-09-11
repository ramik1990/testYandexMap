<?php

namespace App\Http\Requests;

use App\Rules\YandexMapsUrl;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', 'url', new YandexMapsUrl()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['url' => trim((string) $this->input('url'))]);
    }
}
