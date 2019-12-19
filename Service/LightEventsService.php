<?php


namespace Ling\Light_Events\Service;


use Ling\Light\ServiceContainer\LightServiceContainerAwareInterface;
use Ling\Light\ServiceContainer\LightServiceContainerInterface;
use Ling\Light_Events\Exception\LightEventsException;
use Ling\Light_Events\Listener\LightEventsListenerInterface;

/**
 * The LightEventsService class.
 */
class LightEventsService
{


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
     * Builds the LightEventsService instance.
     */
    public function __construct()
    {
        $this->listeners = [];
        $this->container = null;
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
        if (array_key_exists($event, $this->listeners)) {
            $listeners = $this->listeners[$event];
            krsort($listeners);
            $stopPropagation = false;


            foreach ($listeners as $listenerGroup) {
                foreach ($listenerGroup as $listener) {
                    if ($listener instanceof LightEventsListenerInterface) {
                        $listener->process($data, $event, $stopPropagation);
                    } elseif (is_callable($listener)) {
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
}