<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;

class ValidationHelper
{
    public static function applyCustomErrorMessages()
    {
        $customMessages = [
            'unique' => __('messages.exception.unique', ['attribute' => ':attribute']),
            'required' => __('messages.exception.required', ['attribute' => ':attribute']),
            'max' => __('messages.exception.max', ['attribute' => ':attribute', 'max' => ':max']),
            // altri messaggi di errore...
        ];

        Validator::resolver(function ($translator, $data, $rules, $messages) use ($customMessages) {
            return new \Illuminate\Validation\Validator($translator, $data, $rules, $customMessages);
        });
    }
}
