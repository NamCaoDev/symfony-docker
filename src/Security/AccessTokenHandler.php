<?php

namespace App\Security;

use App\Repository\AccessTokenRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepository
    ) {
    }

     public function authenticate(string $accessToken): Passport
    {
        // dd('AccessTokenHandler called with token: ' . $accessToken); // <-- Add this to see if it's called

        $token = $this->accessTokenRepository->findOneByValue($accessToken);

        if (null === $token || !$token->isValid()) { // Add isValid() method to your entity
            // dd('Token invalid or not found'); // <-- Debugging
            throw new BadCredentialsException('Invalid credentials.');
        }

        // Make sure your AccessToken entity has a getUser() method
        $user = $token->getUser();

        if (null === $user) {
            // dd('Token found, but no associated user'); // <-- Debugging
            throw new BadCredentialsException('Invalid credentials.');
        }

        // dd('User found, authenticating: ' . $user->getUserIdentifier()); // <-- Debugging

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), function ($userIdentifier) use ($user) {
            // You might re-fetch the user here, or just return the already loaded user
            return $user;
        }));
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        // e.g. query the "access token" database to search for this token
        $accessToken = $this->accessTokenRepository->findOneByValue($accessToken);
        if (null === $accessToken || !$accessToken->isValid()) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        // and return a UserBadge object containing the user identifier from the found token
        // (this is the same identifier used in Security configuration; it can be an email,
        // a UUID, a username, a database ID, etc.)
        return new UserBadge($accessToken->getUserIdentifier());
    }
}
