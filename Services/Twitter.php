<?php
namespace FOS\TwitterBundle\Services;

use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use \TwitterOAuth;

class Twitter {

    private $twitter;
    private $callbackURL;
    private $session;
    private $request;

    public function __construct(TwitterOAuth $twitter, Session $session, Request $request, $callbackURL = null)
    {
        $this->twitter = $twitter;
        $this->callbackURL = $callbackURL;
        $this->session = $session;
        $this->request = $request;
    }

    public function getLoginUrl()
    {
        /* Get temporary credentials. */
        $requestToken = $this->callbackURL ? $this->twitter->getRequestToken($this->callbackURL) : $this->twitter->getRequestToken();

        /* Save temporary credentials to session. */
        $this->session->set('oauth_token', $requestToken['oauth_token']);
        $this->session->set('oauth_token_secret', $requestToken['oauth_token_secret']);

        /* If last connection failed don't display authorization link. */
        switch ($this->twitter->http_code)
        {
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

    public function getAccessToken()
    {
        /* Check if the oauth_token is old */
        if ($this->session->has('oauth_token')) {
            if ($this->session->get('oauth_token') && ($this->session->get('oauth_token') !== $this->request->get('oauth_token'))) {
                $this->session->remove('oauth_token');
                return null;
            }
        }

        /* Request access tokens from twitter */
        $accessToken = $this->twitter->getAccessToken($this->request->get('oauth_verifier'));

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
}
