<?php


namespace Ling\Light_Events\Service;


use Ling\Light_Events\Exception\LightEventsException;
use Ling\Light_Events\Listener\LightEventsListenerInterface;

/**
 * The LightEventsService class.
 */
class LightEventsService
{


    /**
     * This property holds the listeners for this instance.
     * Each listener is either:
     * - a LightEventsListenerInterface instance
     * - a callable, with signature:
     *      - f ( mixed data, string event ) // same as LightEventsListenerInterface->process
     *
     * @var array
     */
    protected $listeners;


    /**
     * Builds the LightEventsService instance.
     */
    public function __construct()
    {
        $this->listeners = [];
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
            foreach ($this->listeners[$event] as $listener) {
                if ($listener instanceof LightEventsListenerInterface) {
                    $listener->process($data, $event);
                } elseif (is_callable($listener)) {
                    call_user_func($listener, $data, $event);
                } else {
                    $type = gettype($listener);
                    throw new LightEventsException("Invalid listener for event $event, with type $type.");
                }
            }
        }
    }


    /**
     * Registers one or more listener(s) (either a callable or a LightEventsListenerInterface instance).
     *
     * @param string|array $eventName
     * @param $listener
     */
    public function registerListener($eventName, $listener)
    {
        if (false === is_array($eventName)) {
            $eventName = [$eventName];
        }
        foreach ($eventName as $event) {
            if (false === array_key_exists($event, $this->listeners)) {
                $this->listeners[$event] = [];
            }
            $this->listeners[$event][] = $listener;
        }
    }
}