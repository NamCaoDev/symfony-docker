<?php

namespace App\Api;

use Symfony\Component\Validator\Constraints as Assert;

class UserRegisterInput
{
    #[Assert\NotBlank(message: 'The email cannot be blank.')]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email address.')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'The password cannot be blank.')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Your password must be at least {{ limit }} characters long.'
    )]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'Your password must contain at least one uppercase letter.'
    )]
    #[Assert\Regex(
        pattern: '/[a-z]/',
        message: 'Your password must contain at least one lowercase letter.'
    )]
    #[Assert\Regex(
        pattern: '/[0-9]/',
        message: 'Your password must contain at least one number.'
    )]
    #[Assert\Regex(
        pattern: '/[^a-zA-Z0-9\s]/',
        message: 'Your password must contain at least one special character.'
    )]
    public ?string $password = null;
}
