<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;

class TwitterFactory extends AbstractFactory
{
    public function __construct()
    {
        $this->addOption('create_user_if_not_exists', false);
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'fos_twitter';
    }

    protected function getListenerId()
    {
        return 'fos_twitter.security.authentication.listener';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        // configure auth for standard Twitter API
        // with user provider
        if (isset($config['provider'])) {
            $authProviderId = 'fos_twitter.auth.'.$id;

            $container
                ->setDefinition($authProviderId, new DefinitionDecorator('fos_twitter.auth'))
                ->addArgument(new Reference($userProviderId))
                ->addArgument(new Reference('security.user_checker'))
                ->addArgument($config['create_user_if_not_exists'])
            ;

            return $authProviderId;
        }

        // without user provider
        return 'fos_twitter.auth';
    }

    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);

        return $listenerId;
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPointId)
    {
        $entryPointId = 'fos_twitter.security.authentication.entry_point.'.$id;
        $container
            ->setDefinition($entryPointId, new DefinitionDecorator('fos_twitter.security.authentication.entry_point'))
        ;

        return $entryPointId;
    }
}
