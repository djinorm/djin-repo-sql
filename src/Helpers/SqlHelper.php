<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 18.04.2019 14:25
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Helpers;


use DjinORM\Djin\Exceptions\InvalidArgumentException;

class SqlHelper
{

    /**
     * @param $data
     * @param $property
     * @param bool $allowNull
     * @throws InvalidArgumentException
     */
    public static function toFlat(&$data, $property, bool $allowNull = false)
    {
        if ($data[$property] === null) {

            if ($allowNull === false) {
                throw new InvalidArgumentException("{$property} should be not null");
            }

            return;
        }

        $nestedData = $data[$property];
        unset($data[$property]);

        if ($allowNull) {
            $data[$property] = 1;
        }

        foreach ($nestedData as $key => $value) {
            $data["{$property}___$key"] = $value;
        }
    }

    public static function fromFlat(&$data, $property)
    {
        if ($data === null) {
            return;
        }

        $prefix = "{$property}___";
        $length = mb_strlen($prefix);

        $nestedData = [];
        if (array_key_exists($property, $data)) {
            if (!$data[$property]) {
                $nestedData = null;
            }
            unset($nestedData[$property]);
        }

        foreach ($data as $key => $value) {
            if (mb_substr($key, 0, $length) === $prefix) {
                if ($nestedData !== null) {
                    $nestedKey = mb_substr($key, $length);
                    $nestedData[$nestedKey] = $value;
                }
                unset($data[$key]);
            }
        }

        $data[$property] = $nestedData;
    }


    ## JSON ##


    public static function toJson(&$data, $property)
    {
        $data[$property] = json_encode($data[$property]);
    }

    public static function fromJson(&$data, $property)
    {
        $data[$property] = json_decode($data[$property], true);
    }


    ## IP ##


    public static function ipToDb(&$data, $ip)
    {
        if ($data[$ip]) {
            $data[$ip] = inet_pton(trim($data[$ip]));
        } else {
            $data[$ip] = null;
        }
    }

    public static function ipFromDb(&$data, $ip)
    {
        if ($data[$ip] === null) {
            return;
        }
        $data[$ip] = inet_ntop($data[$ip]);
    }

}