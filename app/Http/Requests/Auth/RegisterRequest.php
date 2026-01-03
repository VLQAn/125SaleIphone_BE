<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class RegisterRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rule()
    {
        return [
            'UserName' => 'required|string|max:255',
            'Email' => 'required|string|email|max:255|unique:user,email',
            'Password' => 'required|string|max:255|comfermed',
        ];
    }
}
