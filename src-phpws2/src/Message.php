<?php

namespace phpws2;


/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Message {

    public static function set($message)
    {
        \phpws2\Session::getInstance()->message = $message;
    }

    public static function get()
    {
        if (isset(Session::getInstance()->message)) {
            $message = \phpws2\Session::getInstance()->message;
        } else {
            $message = null;
        }
        unset(Session::getInstance()->message);
        return $message;
    }

    public static function forward($message, $url)
    {
        self::set($message);
        header('location: ' . $url);
        exit();
    }

}
