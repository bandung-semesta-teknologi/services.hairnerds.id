<?php

namespace App\Enums;

enum CredentialType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Social = 'social';
}
