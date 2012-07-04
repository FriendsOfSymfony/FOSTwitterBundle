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

use FOS\TwitterBundle\Security\Authentication\Token\TwitterAnywhereToken;
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
        if($request->getSession()->get("oauth_token", null) && $request->get("oauth_token", null) && 
            $request->getSession()->get("oauth_token", null) === $request->get("oauth_token", null))
        {
          if (true === $this->options['use_twitter_anywhere']) {
              if (null === $identity = $request->cookies->get('twitter_anywhere_identity')) {
                  throw new AuthenticationException(sprintf('Identity cookie "twitter_anywhere_identity" was not sent.'));
              }
              if (false === $pos = strpos($identity, ':')) {
                  throw new AuthenticationException(sprintf('The submitted identity "%s" is invalid.', $identity));
              }
  
              return $this->authenticationManager->authenticate(TwitterAnywhereToken::createUnauthenticated(substr($identity, 0, $pos), substr($identity, $pos + 1)));
          } else {
              return $this->authenticationManager->authenticate(new TwitterUserToken());
          }
        }
        return null;
    }
}
