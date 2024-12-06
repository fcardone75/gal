<?php

namespace App\Service\Security;

use App\Service\Contracts\Security\TokenGeneratorInterface;

class TokenGenerator implements TokenGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
