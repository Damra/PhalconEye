<?php
/*
  +------------------------------------------------------------------------+
  | PhalconEye CMS                                                         |
  +------------------------------------------------------------------------+
  | Copyright (c) 2013-2014 PhalconEye Team (http://phalconeye.com/)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconeye.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Author: Ivan Vorontsov <ivan.vorontsov@phalconeye.com>                 |
  +------------------------------------------------------------------------+
*/

namespace Engine;

use Engine\Behaviour\DIBehaviour;
use Engine\Plugin\CacheAnnotation;
use Engine\Plugin\DispatchErrorHandler;
use Engine\View\Extension;
use Phalcon\Config as PhalconConfig;
use Phalcon\DI;
use Phalcon\DiInterface;
use Phalcon\Events\Manager;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;

/**
 * Bootstrap class.
 *
 * @category  PhalconEye
 * @package   Engine
 * @author    Ivan Vorontsov <ivan.vorontsov@phalconeye.com>
 * @copyright 2013-2014 PhalconEye Team
 * @license   New BSD License
 * @link      http://phalconeye.com/
 */
abstract class Bootstrap implements BootstrapInterface
{
    use DIBehaviour {
        DIBehaviour::__construct as protected __DIConstruct;
    }

    /**
     * Module name.
     *
     * @var string
     */
    protected $_moduleName = "";

    /**
     * Configuration.
     *
     * @var PhalconConfig
     */
    private $_config;

    /**
     * Events manager.
     *
     * @var Manager
     */
    private $_em;

    /**
     * Create Bootstrap.
     *
     * @param DiInterface $di Dependency injection.
     * @param Manager     $em Events manager.
     */
    public function __construct($di, $em)
    {
        $this->__DIConstruct($di);
        $this->_em = $em;
        $this->_config = $this->getDI()->get('config');
    }

    /**
     * Register the services.
     *
     * @throws Exception
     * @return void
     */
    public function registerServices()
    {
        if (empty($this->_moduleName)) {
            $class = new \ReflectionClass($this);
            throw new Exception('Bootstrap has no module name: ' . $class->getFileName());
        }

        $di = $this->getDI();
        $config = $this->getConfig();
        $eventsManager = $this->getEventsManager();
        $moduleDirectory = $this->getModuleDirectory();

        /*************************************************/
        //  Initialize view.
        /*************************************************/
        $view = $this->_initView($di, $config);
        $di->set(
            'view',
            function () use ($view, $moduleDirectory) {
                $view
                    ->reset()
                    ->setVars([], false)
                    ->setRenderLevel(View::LEVEL_ACTION_VIEW)
                    ->setViewsDir($moduleDirectory . '/View/');

                return $view;
            }
        );

        /*************************************************/
        //  Initialize dispatcher.
        /*************************************************/
        $eventsManager->attach("dispatch:beforeException", new DispatchErrorHandler());
        if (!$config->application->debug) {
            $eventsManager->attach('dispatch:beforeExecuteRoute', new CacheAnnotation());
        }

        // Create dispatcher.
        $dispatcher = new Dispatcher();
        $dispatcher->setEventsManager($eventsManager);
        $di->set('dispatcher', $dispatcher);
    }

    /**
     * Get current module directory.
     *
     * @return string
     */
    public function getModuleDirectory()
    {
        return $this->getDI()->get('registry')->directories->modules . $this->_moduleName;
    }

    /**
     * Get config object.
     *
     * @return mixed|PhalconConfig
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Get events manager.
     *
     * @return Manager
     */
    public function getEventsManager()
    {
        return $this->_em;
    }

    /**
     * Get current module name.
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_moduleName;
    }

    /**
     * Init view.
     *
     * @param DIBehaviour $di     DI.
     * @param Config      $config Configuration.
     *
     * @return View
     */
    protected function _initView($di, $config)
    {
        $view = new View();
        $volt = new Volt($view, $di);
        $volt->setOptions(
            [
                "compiledPath" => $config->application->view->compiledPath,
                "compiledExtension" => $config->application->view->compiledExtension,
                'compiledSeparator' => $config->application->view->compiledSeparator,
                'compileAlways' => $config->application->debug && $config->application->view->compileAlways
            ]
        );

        $compiler = $volt->getCompiler();
        $compiler->addExtension(new Extension());
        $view->registerEngines([".volt" => $volt]);

        // Attach a listener for type "view".
        $this->_em->attach(
            "view",
            function ($event, $view) use ($di, $config) {
                if ($config->application->profiler && $di->has('profiler')) {
                    if ($event->getType() == 'beforeRender') {
                        $di->get('profiler')->start();
                    }
                    if ($event->getType() == 'afterRender') {
                        $di->get('profiler')->stop($view->getActiveRenderPath(), 'view');
                    }
                }
                if ($event->getType() == 'notFoundView') {
                    throw new Exception('View not found - "' . $view->getActiveRenderPath() . '"');
                }
            }
        );
        $view->setEventsManager($this->_em);

        return $view;
    }
}