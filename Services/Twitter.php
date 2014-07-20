<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\Services;

use Symfony\Component\Routing\RouterInterface;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use TwitterOAuth;

class Twitter
{
    private $twitter;
    private $session;
    private $router;
    private $callbackRoute;
    private $callbackURL;

    public function __construct(TwitterOAuth $twitter, Session $session, $callbackURL = null)
    {
        $this->twitter = $twitter;
        $this->session = $session;
        $this->callbackURL = $callbackURL;
    }

    public function setCallBackURL($callbackURL)
    {
	$this->callbackURL = $callbackURL;
    }

    public function setCallbackRoute(RouterInterface $router, $routeName)
    {
        $this->router = $router;
        $this->callbackRoute = $routeName;
    }

    public function getLoginUrl()
    {
        /* Get temporary credentials. */
        $requestToken = ($callbackUrl = $this->getCallbackUrl()) ?
            $this->twitter->getRequestToken($callbackUrl)
            : $this->twitter->getRequestToken();

        if (!isset($requestToken['oauth_token']) || !isset($requestToken['oauth_token_secret'])) {
            throw new \RuntimeException('Failed to validate oauth signature and token.');
        }

        /* Save temporary credentials to session. */
        $this->session->set('oauth_token', $requestToken['oauth_token']);
        $this->session->set('oauth_token_secret', $requestToken['oauth_token_secret']);

        /* If last connection failed don't display authorization link. */
        switch ($this->twitter->http_code) {
            case 200:
                /* Build authorize URL and redirect user to Twitter. */
                $redirectURL = $this->twitter->getAuthorizeURL($requestToken);
                return $redirectURL;
                break;
            default:
                /* return null if something went wrong. */
                return null;
        }
    }

    public function getAccessToken($oauthToken, $oauthVerifier)
    {
        //set OAuth token in the API
        $this->twitter->setOAuthToken($oauthToken, $this->session->get('oauth_token_secret'));

        /* Check if the oauth_token is old */
        if ($this->session->has('oauth_token')) {
            if ($this->session->get('oauth_token') && ($this->session->get('oauth_token') !== $oauthToken)) {
                $this->session->remove('oauth_token');
                return null;
            }
        }

        /* Request access tokens from twitter */
        $accessToken = $this->twitter->getAccessToken($oauthVerifier);

        /* Save the access tokens. Normally these would be saved in a database for future use. */
        $this->session->set('access_token', $accessToken['oauth_token']);
        $this->session->set('access_token_secret', $accessToken['oauth_token_secret']);

        /* Remove no longer needed request tokens */
        !$this->session->has('oauth_token') ?: $this->session->remove('oauth_token', null);
        !$this->session->has('oauth_token_secret') ?: $this->session->remove('oauth_token_secret', null);

        /* If HTTP response is 200 continue otherwise send to connect page to retry */
        if (200 == $this->twitter->http_code) {
            /* The user has been verified and the access tokens can be saved for future use */
            return $accessToken;
        }

        /* Return null for failure */
        return null;
    }

    private function getCallbackUrl()
    {
        if (!empty($this->callbackURL)) {
            return $this->callbackURL;
        }

        if (!empty($this->callbackRoute)) {
            return $this->router->generate($this->callbackRoute, array(), true);
        }

        return null;
    }
}
