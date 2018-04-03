<?php
defined("ABSPATH") or die("");


class DUPX_EventManager
{
    private static $events = array();

    public static function registerEvent($name, $callback)
    {
        self::$events[$name][] = $callback;
    }

    public static function triggerEvent($name, $params = array())
    {
        foreach (self::$events[$name] as $callback) {

            $callback($params);
        }
    }

    public static function isRegistered($name)
    {
        return array_key_exists($name, self::$events);
    }
}