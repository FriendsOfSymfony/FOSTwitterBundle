<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use FOS\TwitterBundle\Security\User\UserManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\ValidatorInterface;

use FOS\TwitterBundle\Security\Authentication\Token\TwitterUserToken;
use FOS\TwitterBundle\Services\Twitter;
use FOS\UserBundle\Security\Authentication\Token\IncompleteUserToken;

class TwitterProvider implements AuthenticationProviderInterface
{
    private $twitter;
    private $userProvider;
    private $userChecker;
    private $createUserIfNotExists;
    private $validator;

    public function __construct(Twitter $twitter, ValidatorInterface $validator, UserProviderInterface $userProvider = null, UserCheckerInterface $userChecker = null, $createUserIfNotExists = false)
    {
        if (null !== $userProvider && null === $userChecker) {
            throw new \InvalidArgumentException('$userChecker cannot be null, if $userProvider is not null.');
        }

        if ($createUserIfNotExists && !$userProvider instanceof UserManagerInterface) {
            throw new \InvalidArgumentException('$userProvider must be an instanceof UserManagerInterface if createUserIfNotExists is true.');
        }

        $this->twitter = $twitter;
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->createUserIfNotExists = $createUserIfNotExists;
        $this->validator = $validator;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            // FIXME: Should we make a call to Twitter for verification?
            $newToken = new TwitterUserToken($user, null, $user->getRoles());
            $newToken->setAttributes($token->getAttributes());

            return $newToken;
        }

        try {
            if ($accessToken = $this->twitter->getAccessToken($token->getUser(), $token->getOauthVerifier())) {
                $newToken = $this->createAuthenticatedToken($accessToken);
                $newToken->setAttributes($token->getAttributes());

                return $newToken;
            }
        } catch (AuthenticationException $failed) {
            throw $failed;
        } catch (\Exception $failed) {
            throw new AuthenticationException($failed->getMessage(), null, $failed->getCode(), $failed);
        }

        throw new AuthenticationException('The Twitter user could not be retrieved from the session.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof TwitterUserToken;
    }

    private function createAuthenticatedToken(array $accessToken)
    {
        if (null === $this->userProvider) {
            return new TwitterUserToken($accessToken['screen_name'], null, array('ROLE_TWITTER_USER'));
        }

        try {
            $user = $this->userProvider->loadUserByUsername($accessToken['screen_name']);
            $this->userChecker->checkPostAuth($user);
        } catch (UsernameNotFoundException $ex) {
            if (!$this->createUserIfNotExists) {
                throw $ex;
            }

            $user = $this->userProvider->createUserFromAccessToken($accessToken);
        }

        if (!$user instanceof UserInterface) {
            throw new \RuntimeException('User provider did not return an implementation of user interface.');
        }

        $errors = $this->validator->validate($user, array('Profile'));

        if (count($errors) > 0) {
            $user->setIncomplete(true);
            return new IncompleteUserToken('', $user, $user->getRoles());
        } else {
            return new TwitterUserToken($user, null, $user->getRoles());
        }
    }
}
