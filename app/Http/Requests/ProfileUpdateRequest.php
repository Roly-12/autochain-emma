<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'theme_preference' => ['nullable', 'in:light,dark,system'],
            'notification_email' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:8192'],
            'company_logo' => [
                Rule::prohibitedIf(fn () => ! $this->user()->hasRole(UserRole::SuperAdmin)),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:8192',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.uploaded' => 'La photo n’a pas pu être téléversée. Sa taille maximale est de 8 Mo.',
            'avatar.max' => 'La photo ne doit pas dépasser 8 Mo.',
            'avatar.mimes' => 'Utilisez une photo JPG, PNG, GIF ou WebP. Le format HEIC n’est pas pris en charge.',
            'company_logo.uploaded' => 'Le logo n’a pas pu être téléversé. Sa taille maximale est de 8 Mo.',
            'company_logo.max' => 'Le logo ne doit pas dépasser 8 Mo.',
            'company_logo.mimes' => 'Utilisez un logo JPG, PNG, GIF ou WebP.',
        ];
    }
}
