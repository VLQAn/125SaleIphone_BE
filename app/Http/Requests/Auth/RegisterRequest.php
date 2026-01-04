<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Task\BaseRequest;

class RegisterRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'UserName' => 'required|string|max:255',
            'Email' => 'required|string|email|max:255|unique:users,Email',
            'Password' => 'required|string|max:255|confirmed',
        ];
    }
}
