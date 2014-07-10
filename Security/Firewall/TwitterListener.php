<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\Security\Firewall;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use FOS\TwitterBundle\Security\Authentication\Token\TwitterUserToken;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * Twitter authentication listener.
 */
class TwitterListener extends AbstractAuthenticationListener
{
    protected function attemptAuthentication(Request $request)
    {
        return $this->authenticationManager->authenticate(new TwitterUserToken($request->query->get('oauth_token'), $request->query->get('oauth_verifier')));
    }
}
