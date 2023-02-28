<?php

namespace CAO\Core;

use CAO\Core\Collections\ArtikelCollection;
use CAO\Request;
use EGroupware\Api;

class Artikel
{
	private static $DATASET;

	public static function GetAll()
	{
		return self::$DATASET;
	}

	public static function Find($REC_IDorARTNUM)
	{
		if (!empty($REC_IDorARTNUM)) {
			if (is_string($REC_IDorARTNUM)) {
				$indexKey = array_search($REC_IDorARTNUM, array_column(self::$DATASET, 'ARTNUM'));
			} else {
				$indexKey = array_search($REC_IDorARTNUM, array_column(self::$DATASET, 'REC_ID'));
			}
			$object = (object) self::$DATASET[$indexKey];

			return new ArtikelCollection($object);
		}

		return false;
	}

	public static function Get()
	{
		return self::GetAll();
	}

	public static function init_static()
	{
		self::$DATASET = self::getCache();
	}

	private static function getCache()
	{
		self::$DATASET = unserialize(Api\Cache::getCache(Api\Cache::INSTANCE, __CLASS__, 'ARTIKEL_DATASET'));
		if (empty(self::$DATASET)) {
			self::reloadCache();

			return self::$DATASET;
		}

		return self::$DATASET;
	}

	private static function reloadCache()
	{
		$ARTIKEL = Request::Run('SELECT * FROM ARTIKEL');
		Api\Cache::setCache(Api\Cache::INSTANCE, __CLASS__, 'ARTIKEL_DATASET', serialize($ARTIKEL), 3600);
		self::$DATASET = $ARTIKEL;
	}

	public static function Update()
	{
		self::reloadCache();

		return new static();
	}
}

Artikel::init_static();
