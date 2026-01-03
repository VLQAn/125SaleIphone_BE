<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
      /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rule()
    {
        return [
            'Email' => 'required|string|email|max:255',
            'Password' => 'required|string|max:255'
        ];
    }
}