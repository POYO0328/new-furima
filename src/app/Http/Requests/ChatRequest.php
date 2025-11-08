<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize()
    {
        // 誰でも送信できる場合は true
        return true;
    }

    public function rules()
    {
        return [
            'message' => 'required|string|max:400',
            'image' => 'nullable|file|mimes:jpeg,png',
        ];
    }

    public function messages()
    {
        return [
            'message.required' => '本文を入力してください',
            'message.max' => '本文は400文字以内で入力してください。',
            'image.mimes' => '画像は jpeg または png のみアップロード可能です。',
        ];
    }
}

