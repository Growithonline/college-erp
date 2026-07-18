<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstituteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Institute
            'name'       => 'required|string|max:255',
            'short_name' => 'required|string|max:10|alpha_num',
            'mobile' => 'required|string|max:20',
            'email' => 'required|email|unique:institutes,email',

            'image' => 'nullable|file|max:2048|extensions:jpg,jpeg,png',
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',

            // Owner
            'owner_name' => 'required|string|max:255',
            'owner_mobile' => 'required|string|max:20',
            'owner_email' => 'required|email',

            'owner_whatsapp' => 'nullable|string|max:20',
            'owner_address' => 'nullable|string|max:500',

            'owner_identity_proof' => 'nullable|file|max:2048',

            // SaaS
            'student_limit' => 'required|integer|min:1',
            'subscription_start' => 'nullable|date',
            'subscription_end' => 'nullable|date|after_or_equal:subscription_start',
        ];
    }
}
