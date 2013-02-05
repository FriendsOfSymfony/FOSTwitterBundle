Introduction
============

This Bundle enables integration with Twitter PHP. Furthermore it
also provides a Symfony2 authentication provider so that users can login to a
Symfony2 application via Twitter. Furthermore via custom user provider support
the Twitter login can also be integrated with other data sources like the
database based solution provided by FOSUserBundle.

``If you are using Symfony 2.0 switch to the branch v1.0 of TwitterBundle or use the tag 1.0.0``

[![Build Status](https://secure.travis-ci.org/FriendsOfSymfony/FOSTwitterBundle.png)](http://travis-ci.org/FriendsOfSymfony/FOSTwitterBundle)

Installation
============

  1. Add this bundle and Abraham Williams' Twitter library to your project as Git submodules:

          "require": {
            "_some_package": "*",

            "friendsofsymfony/twitter-bundle": "*"
          }

  2. Add this bundle to your application's kernel:

          //app/AppKernel.php
          public function registerBundles()
          {
              return array(
                  // ...
                  new FOS\TwitterBundle\FOSTwitterBundle(),
                  // ...
              );
          }

  3. Configure the `twitter` service in your YAML configuration:

            #app/config/config.yml
            fos_twitter:
                consumer_key: xxxxxx
                consumer_secret: xxxxxx
                callback_url: http://www.example.com/login_check

  5. Add the following configuration to use the security component:

            #app/config/security.yml
            security:
                providers:
                    fos_twitter:
                        id: fos_twitter.auth
                firewalls:
                    secured:
                        pattern:   /secured/.*
                        fos_twitter: true
                    public:
                        pattern:   /.*
                        anonymous: true
                        fos_twitter: true
                        logout: true
                access_control:
                    - { path: /.*, role: [IS_AUTHENTICATED_ANONYMOUSLY] }

Using Twitter @Anywhere
-----------------------

>**Note:** If you want the Security Component to work with Twitter @Anywhere, you need to send a request to the configured check path upon successful client authentication (see https://gist.github.com/1021384 for a sample configuration).

A templating helper is included for using Twitter @Anywhere. To use it, first
call the `->setup()` method toward the top of your DOM:

        <!-- inside a php template -->
          <?php echo $view['twitter_anywhere']->setup() ?>
        </head>

        <!-- inside a twig template -->
          {{ twitter_anywhere_setup() }}
        </head>

Once that's done, you can queue up JavaScript to be run once the library is
actually loaded:

        <!-- inside a php template -->
        <span id="twitter_connect"></span>
        <?php $view['twitter_anywhere']->setConfig('callbackURL', 'http://www.example.com/login_check') ?>
        <?php $view['twitter_anywhere']->queue('T("#twitter_connect").connectButton()') ?>

        <!-- inside a twig template -->
        <span id="twitter_connect"></span>
        {{ twitter_anywhere_setConfig('callbackURL', 'http://www.example.com/login_check') }}
        {{ twitter_anywhere_queue('T("#twitter_connect").connectButton()') }}

Finally, call the `->initialize()` method toward the bottom of the DOM:

        <!-- inside a php template -->
          <?php $view['twitter_anywhere']->initialize() ?>
        </body>

        <!-- inside a twig template -->
        {{ twitter_anywhere_initialize() }}
        </body>

### Configuring Twitter @Anywhere

You can set configuration using the templating helper. with the setConfig() method.


Example Custom User Provider using the FOSUserBundle
-------------------------------------------------------


To use this provider you will need to add a new service in your config.yml

``` yaml
# app/config/config.yml
services:
        my.twitter.user:
            class: Acme\YourBundle\Security\User\Provider\TwitterProvider
            arguments:
                twitter_oauth: "@fos_twitter.api"
                userManager: "@fos_user.user_manager"
                validator: "@validator"
                session: "@session" 
```

Also you would need some new properties and methods in your User model class.

``` php

<?php
// src/Acme/YourBundle/Entity/User.php

        /** 
         * @var string
         */
        protected $twitterID;

        /** 
         * @var string
         */
        protected $twitter_username;


        /**
         * Set twitterID
         *
         * @param string $twitterID
         */
        public function setTwitterID($twitterID)
        {
            $this->twitterID = $twitterID;
            $this->setUsername($twitterID);
            $this->salt = '';
        }

        /**
         * Get twitterID
         *
         * @return string 
         */
        public function getTwitterID()
        {
            return $this->twitterID;
        }

        /**
         * Set twitter_username
         *
         * @param string $twitterUsername
         */
        public function setTwitterUsername($twitterUsername)
        {
            $this->twitter_username = $twitterUsername;
        }

        /**
         * Get twitter_username
         *
         * @return string 
         */
        public function getTwitterUsername()
        {
            return $this->twitter_username;
        }
```
        
And this is the TwitterProvider class

``` php
<?php
// src/Acme/YourBundle/Security/User/Provider/TwitterProvider.php


namespace Acme\YourBundle\Security\User\Provider;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session;
use \TwitterOAuth;
use FOS\UserBundle\Entity\UserManager;
use Symfony\Component\Validator\Validator;

class TwitterProvider implements UserProviderInterface
{
    /** 
     * @var \Twitter
     */
    protected $twitter_oauth;
    protected $userManager;
    protected $validator;
    protected $session;

    public function __construct(TwitterOAuth $twitter_oauth, UserManager $userManager,Validator $validator, Session $session)
    {   
        $this->twitter_oauth = $twitter_oauth;
        $this->userManager = $userManager;
        $this->validator = $validator;
        $this->session = $session;
    }   

    public function supportsClass($class)
    {   
        return $this->userManager->supportsClass($class);
    }   

    public function findUserByTwitterId($twitterID)
    {   
        return $this->userManager->findUserBy(array('twitterID' => $twitterID));
    }   

    public function loadUserByUsername($username)
    {
        $user = $this->findUserByTwitterId($username);


         $this->twitter_oauth->setOAuthToken( $this->session->get('access_token') , $this->session->get('access_token_secret'));

        try {
             $info = $this->twitter_oauth->get('account/verify_credentials');
        } catch (Exception $e) {
             $info = null;
        }

        if (!empty($info)) {
            if (empty($user)) {
                $user = $this->userManager->createUser();
                $user->setEnabled(true);
                $user->setPassword('');
                $user->setAlgorithm('');
            }

            $username = $info->screen_name;


            $user->setTwitterID($info->id);
            $user->setTwitterUsername($username);
            $user->setEmail('');
            $user->setFirstname($info->name);

            $this->userManager->updateUser($user);
        }

        if (empty($user)) {
            throw new UsernameNotFoundException('The user is not authenticated on twitter');
        }

        return $user;

    }

    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user)) || !$user->getTwitterID()) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getTwitterID());
    }
}
```


Finally, to get the authentication tokens from Twitter you would need to create an action in your controller like this one.

``` php

<?php
// src/Acme/YourBundle/Controller/DefaultController.php

        /** 
        * @Route("/connectTwitter", name="connect_twitter")
        *
        */
        public function connectTwitterAction()
        {   

          $request = $this->get('request');
          $twitter = $this->get('fos_twitter.service');

          $authURL = $twitter->getLoginUrl($request);

          $response = new RedirectResponse($authURL);

          return $response;

        }  

```

You can create a button in your Twig template that will send the user to authenticate with Twitter.

```
         <a href="{{ path ('connect_twitter')}}"> <img src="/images/twitterLoginButton.png"></a> 

```

* Note: Your callback URL in your config.yml must point to your configured check_path

``` yaml
# app/config/config.yml

        fos_twitter:
            ...
            callback_url: http://www.yoursite.com/twitter/login_check
```

Remember to edit your security.yml to use this provider


``` yaml
# app/config/security.yml

        security:
            encoders:
                Symfony\Component\Security\Core\User\User: plaintext

            role_hierarchy:
                ROLE_ADMIN:       ROLE_USER
                ROLE_SUPER_ADMIN: [ROLE_USER, ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]

            providers:

                my_fos_twitter_provider:
                    id: my.twitter.user 

            firewalls:
                dev:
                    pattern:  ^/(_(profiler|wdt)|css|images|js)/
                    security: false

                public:
                    pattern:  /
                    fos_twitter:
                        login_path: /twitter/login
                        check_path: /twitter/login_check
                        default_target_path: /
                        provider: my_fos_twitter_provider

                    anonymous: ~

```
