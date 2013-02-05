<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\Tests\DependencyInjection;

use FOS\TwitterBundle\DependencyInjection\FOSTwitterExtension;

class TwitterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Bundle\FOS\TwitterBundle\DependencyInjection\FOSTwitterExtension::load
     */
    public function testLoadFailed()
    {
        $this->setExpectedException('\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');

        $container = $this->getMock('Symfony\\Component\\DependencyInjection\\ContainerBuilder');

        $extension = new FOSTwitterExtension();
        $extension->load(array(), $container);
    }

    /**
     * @covers Bundle\FOS\TwitterBundle\DependencyInjection\FOSTwitterExtension::load
     */
    public function testLoadSuccess()
    {
        $configs = array(
            array(
                'callback_url' => 'foo',
                'consumer_key' => 'foo',
                'consumer_secret' => 'foo',
            ),
        );

        $container = $this->getMock('Symfony\\Component\\DependencyInjection\\ContainerBuilder');
        $parameterbag = $this->getMock('Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface');
        $container
            ->expects($this->once())
            ->method('getParameterBag')
            ->with()
            ->will($this->returnValue($parameterbag));

        $alias = 'bar';

        $container
            ->expects($this->once())
            ->method('setAlias')
            ->with($alias, 'fos_twitter');

        $configs[] = array('alias' => $alias);

        $extension = new FOSTwitterExtension();
        $extension->load($configs, $container);
    }
}
