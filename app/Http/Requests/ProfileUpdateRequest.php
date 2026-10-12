<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:100',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'nip' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'profile_photo' => ['nullable', function ($attribute, $value, $fail) {
                if (request()->hasFile('profile_photo')) {
                    $file = request()->file('profile_photo');
                    if (!str_starts_with($file->getMimeType(), 'image/')) {
                        $fail('File foto profil harus berupa gambar.');
                    }
                } elseif (is_string($value)) {
                    if ($value !== 'delete' && !str_starts_with($value, 'data:image/')) {
                        $fail('File foto profil harus berupa gambar.');
                    }
                }
            }],
        ];
    }
}
