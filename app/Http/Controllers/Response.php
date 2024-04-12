<?php

namespace App\Http\Controllers;

use Hamcrest\Arrays\IsArray;
use stdClass;

class Response extends Controller
{
    static $code;
    static $status;
    static $message;
    static $data;
    static $codes;

    function __construct()
    {
        self::$codes = object(config("responseCode"));
        self::$code = self::$codes->bad_request;
        self::$message = "";
        self::$status = false;
        self::$data = new stdClass;
    }

    public static function set(array $object = [], $success = false): object
    {
        if ($success) {
            self::$status = true;
            self::$code = self::$codes->{'success'};
            self::$message = "SUCCESSFUL";
        }
        foreach ($object as $key => $value) {
            if (isset(self::${$key})) {
                if ($key === "code") self::$code = self::$codes->{$value};
                else self::${$key} = $value;
            }
        }

        return self::get();
    }


    public static function get()
    {
        return object(["status" => self::$status, "code" => self::$code, "message" => self::$message, "data" => self::$data]);
    }

    public static function requiredFields($fields, $haystack)
    {
        $r = [];
        if (gettype($haystack) == 'array') $haystack = object($haystack);
        foreach ($fields as $v) {
            if (empty($haystack->$v)) {
                return (object(["status" => self::$status, "code" => self::$code, "message" => $v . " is required", "data" => self::$data]));
                // return object(["status" => self::$status, "code" => self::$code, "message" => self::$message, "data" => self::$data]);
            } else {
                $r[$v] = self::sanitize($haystack->{$v});
            }
        }
        return object($r);
    }

    public static function sanitize($dirty)
    {
        if (is_array($dirty)) {
            foreach ($dirty as $k => $d) {
                if (is_array($d)) {
                    foreach ($d as $a => $b) {
                        $d[$a] = htmlentities(trim($b), ENT_QUOTES, 'UTF-8');
                    }
                } else $dirty[$k] = htmlentities(trim($d), ENT_QUOTES, 'UTF-8');
            }
            return $dirty;
        } else return htmlentities(trim($dirty), ENT_QUOTES, 'UTF-8');
    }

    public static function desanitize($dirty)
    {
        if (is_array($dirty)) {
            foreach ($dirty as $k => $d) {
                if (is_array($d)) {
                    foreach ($d as $a => $b) {
                        $d[$a] = html_entity_decode(trim($b), ENT_QUOTES, 'UTF-8');
                    }
                } else $dirty[$k] = html_entity_decode(trim($d), ENT_QUOTES, 'UTF-8');
            }
            return $dirty;
        } else return html_entity_decode(trim($dirty), ENT_QUOTES, 'UTF-8');
    }

    public static function array_search_full($arr, $key, $value)
    {
        $ab = array_filter($arr, function ($var) use ($key, &$value) {
            if (is_object($var)) {
                if (!property_exists($var, $key)) {
                    return null;
                }
                return ($var->{$key} == $value);
            } else {
                if (!array_key_exists($key, $var)) {
                    return null;
                }
                return ($var[$key] == $value);
            }
        });
        if (count($ab) == 1) {
            foreach ($ab as $c) {
                $ab = $c;
            }
        }
        return $ab;
    }
}
