<?php

namespace CAO\Core;

use CAO\Core\Controllers\MitarbeiterController;
use CAO\Request;
use EGroupware\Api;

class Mitarbeiter
{
    private static $DATASET;

    public static function all()
    {
        return self::$DATASET;
    }

    public static function Find(int $MA_ID)
    {
        $key = array_search($MA_ID, array_column(self::$DATASET, 'MA_ID'));
        $object = (object) self::$DATASET[$key];

        return new MitarbeiterController($object);
    }

    public static function init_static()
    {
        self::$DATASET = self::getCache();
    }

    private static function getCache()
    {
        self::$DATASET = unserialize(Api\Cache::getCache(Api\Cache::INSTANCE, __CLASS__, 'MITARBEITER'));
        if (empty(self::$DATASET)) {
            self::reloadCache();

            return self::$DATASET;
        }

        return self::$DATASET;
    }

    private static function reloadCache()
    {
        $ARTIKEL = Request::Run('SELECT * FROM MITARBEITER');
        Api\Cache::setCache(Api\Cache::INSTANCE, __CLASS__, 'MITARBEITER', serialize($ARTIKEL), 3600);
        self::$DATASET = $ARTIKEL;
    }

    public static function Update()
    {
        self::reloadCache();

        return new static();
    }
}

Mitarbeiter::init_static();
