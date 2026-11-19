<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        $presence = $this->isMethod('post') ? 'required' : 'sometimes';
        return [
            'category_id' => [$presence, 'integer', 'exists:categories,id'],
            'title'       => [$presence, 'string', 'max:255'],
            'author'      => [$presence, 'string', 'max:255'],
            'isbn'        => [$presence, 'string', 'max:20', Rule::unique('books')->ignore($this->route('book'))],
            'stock'       => [$presence, 'integer', 'min:0'],
        ];
    }
}
