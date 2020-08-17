<?php


namespace Ling\Light_Events\Service;


use Ling\BabyYaml\BabyYamlUtil;
use Ling\Bat\DebugTool;
use Ling\DirScanner\YorgDirScannerTool;
use Ling\Light\Helper\LightHelper;
use Ling\Light\ServiceContainer\LightServiceContainerAwareInterface;
use Ling\Light\ServiceContainer\LightServiceContainerInterface;
use Ling\Light_Events\Exception\LightEventsException;
use Ling\Light_Events\Listener\LightEventsListenerInterface;
use Ling\Light_Logger\LightLoggerService;

/**
 * The LightEventsService class.
 */
class LightEventsService
{


    public const STOP_PROPAGATION = '_stop_propagation_';


    /**
     * This property holds the listeners for this instance.
     * It's an array of priority => listenerGroup.
     * Each listenerGroup is an array of listeners.
     *
     * Each listener is either:
     * - a LightEventsListenerInterface instance
     * - a callable, with signature:
     *      - f ( mixed data, string event ) // same as LightEventsListenerInterface->process
     *
     * @var array
     */
    protected $listeners;

    /**
     * This property holds the container for this instance.
     * @var LightServiceContainerInterface
     */
    protected $container;

    /**
     * This property holds the dispatchedEvents for this instance.
     * @var array
     */
    protected $dispatchedEvents;

    /**
     * This property holds the options for this instance.
     *
     * Available options are:
     *
     * - useDebug: bool = false.
     *      If true, we log the dispatching details in a a log.
     *      See more details in the @page(Light_Events conception notes).
     *
     *
     * @var array
     */
    protected $options;


    /**
     * Builds the LightEventsService instance.
     */
    public function __construct()
    {
        $this->listeners = [];
        $this->dispatchedEvents = [];
        $this->container = null;
        $this->options = [];
    }

    /**
     * Dispatches the given event along with the given data.
     *
     * @param string $event
     * @param $data
     * @throws \Exception
     */
    public function dispatch(string $event, $data = null)
    {

        $debugSent = $this->options['debugSent'] ?? false;
        if (true === $debugSent) {
            /**
             * @var $logger LightLoggerService
             */
            $logger = $this->container->get("logger");
            $logger->log("Dispatching event $event", "events.debug");
        }


        //--------------------------------------------
        // STATIC CALLS
        //--------------------------------------------


        if (array_key_exists($event, $this->listeners)) {
            $this->dispatchedEvents[] = $event;

            $listeners = $this->listeners[$event];
            krsort($listeners);
            $stopPropagation = false;

            foreach ($listeners as $listenerGroup) {
                foreach ($listenerGroup as $listener) {
                    if ($listener instanceof LightEventsListenerInterface) {
                        $this->onListenerProcessBefore($listener, $event, $data);
                        $listener->process($data, $event, $stopPropagation);
                    } elseif (is_callable($listener)) {
                        $this->onListenerProcessBefore($listener, $event, $data);
                        call_user_func_array($listener, [$data, $event, &$stopPropagation]);
                    } else {
                        $type = gettype($listener);
                        throw new LightEventsException("Invalid listener for event $event, with type $type.");
                    }
                    if (true === $stopPropagation) {
                        return;
                    }
                }
            }
        }


        //--------------------------------------------
        // DYNAMIC CALLS
        //--------------------------------------------
        $dir = $this->container->getApplicationDir() . "/config/dynamic/Light_Events/$event";
        if (is_dir($dir)) {
            $originId = null;
            $files = YorgDirScannerTool::getFilesWithExtension($dir, 'byml', false, false);
            foreach ($files as $path) {
                $events = BabyYamlUtil::readFile($path);
                foreach ($events as $expr) {
                    $res = LightHelper::executeMethod($expr, $this->container, [
                        "argReplace" => [
                            'event' => $event,
                            'data' => $data,
                            'dynamicPath' => $path,
                        ],
                    ]);

                    if (self::STOP_PROPAGATION === $res) {
                        break 2;
                    }
                }
            }

        }


    }


    /**
     * Registers one or more listener(s) (either a callable or a LightEventsListenerInterface instance).
     *
     * @param string|array $eventName
     * @param $listener
     * @param int $priority = 0
     */
    public function registerListener($eventName, $listener, int $priority = 0)
    {
        if ($listener instanceof LightServiceContainerAwareInterface) {
            $listener->setContainer($this->container);
        }


        if (false === is_array($eventName)) {
            $eventName = [$eventName];
        }
        foreach ($eventName as $event) {
            if (false === array_key_exists($event, $this->listeners)) {
                $this->listeners[$event] = [];
            }
            if (false === array_key_exists($priority, $this->listeners[$event])) {
                $this->listeners[$event][$priority] = [];
            }
            $this->listeners[$event][$priority][] = $listener;
        }
    }


    /**
     * Returns the dispatchedEvents of this instance, in the order they appeared.
     *
     * @return array
     */
    public function getDispatchedEvents(): array
    {
        return array_unique($this->dispatchedEvents);
    }

    /**
     * Sets the container.
     *
     * @param LightServiceContainerInterface $container
     */
    public function setContainer(LightServiceContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Sets the options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }




    //--------------------------------------------
    //
    //--------------------------------------------
    /**
     * A hook called just before a listener is triggered.
     *
     * By default, we log the listener details if the useDebug option is true.
     *
     *
     * @param $listener
     * @param string $event
     * @param $data
     *
     * @overrideMe
     */
    protected function onListenerProcessBefore($listener, string $event, $data)
    {

        $useDebug = $this->options['debugCaught'] ?? false;
        if (true === $useDebug) {
            $listenerName = null;
            if ($listener instanceof LightEventsListenerInterface) {
                $listenerName = get_class($listener);
            } else {
                $listenerName = DebugTool::toString($listener);
            }
            $sentence = "Calling listener $listenerName on event $event.";

            /**
             * @var $logger LightLoggerService
             */
            $logger = $this->container->get("logger");
            $logger->log($sentence, "events.debug");
        }
    }

}