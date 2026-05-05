<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status'    => ['sometimes', 'string', Rule::in(['all', 'active', 'inactive'])],
            'search'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort'      => ['sometimes', 'string', Rule::in(['name', 'active'])],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}

