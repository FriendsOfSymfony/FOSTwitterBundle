<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Templating\EngineInterface;

class TwitterAnywhereHelper extends Helper
{
    protected $templating;
    protected $apiKey;
    protected $version;
    protected $callbackURL;

    protected $config = array();
    protected $scripts = array();

    public function __construct(EngineInterface $templating, $apiKey, $version = 1)
    {
        $this->templating = $templating;
        $this->apiKey = $apiKey;
        $this->version = $version;
    }

    /*
     *
     */
    public function setup($parameters = array(), $name = null)
    {
        $name = $name ?: 'FOSTwitterBundle::setup.html.php';
        return $this->templating->render($name, $parameters + array(
            'apiKey'      => $this->apiKey,
            'version'     => $this->version,
        ));
    }

    /*
     *
     */
    public function initialize($parameters = array(), $name = null)
    {
        //convert scripts into lines
        $lines = '';
        foreach ($this->scripts as $script) {
            $lines .= rtrim($script, ';').";\n";
        }

        $name = $name ?: 'FOSTwitterBundle::initialize.html.php';
        return $this->templating->render($name, $parameters + array(
            'configMap'     => $this->config,
            'scripts'       => $lines,
        ));
    }

    /*
     *
     */
    public function queue($script)
    {
        $this->scripts[] = $script;
    }

    /*
     *
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return 'twitter_anywhere';
    }
}
