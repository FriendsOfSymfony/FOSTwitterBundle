<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\Security\EntryPoint;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContext;

use FOS\TwitterBundle\Security\Exception\ConnectionException;
use FOS\TwitterBundle\Services\Twitter;

/**
 * TwitterAuthenticationEntryPoint starts an authentication via Twitter.
 */
class TwitterAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    protected $twitter;

    /**
     * Constructor
     *
     * @param Twitter $twitter
     */
    public function __construct(Twitter $twitter)
    {
        $this->twitter = $twitter;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $authURL = $this->twitter->getLoginUrl($request);
        if (!$authURL) {
            throw new ConnectionException('Could not connect to Twitter!');
        }

        return new RedirectResponse($authURL);
    }
}
