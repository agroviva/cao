<?php

namespace CAO\Core;

use AgroEgw\DB;
use CAO\Core\Collections\AdressenCollection;
use CAO\Request;
use EGroupware\Api;
use FuzzyWuzzy\Fuzz;

class Adressen
{
    public $relationships;

    private static $DATASET;

    public function __construct()
    {
        $cao_adressen = (new DB("
			SELECT * FROM egw_addressbook_extra 
			WHERE contact_name = 'cao kunden nr'
		"))->FetchAll() ?: [];

        foreach ($cao_adressen as $key => $data) {
            if (!empty($data['contact_value'])) {
                $this->relationships['egw'][] = $data['contact_id'];
                $this->relationships['cao'][] = $data['contact_value'];
                $this->relationships['egw_cao'][$data['contact_id']] = $data['contact_value'];
            }
        }
    }

    public static function Find($KUNNUM)
    {
        if (is_array($KUNNUM)) {
            $array = [];

            foreach ($KUNNUM as $NUM) {
                $indexKey = array_search($NUM, array_column(self::$DATASET, 'KUNNUM1'));
                $indexKey = $indexKey ?? array_search($NUM, array_column(self::$DATASET, 'KUNNUM2'));

                $array[] = self::$DATASET[$indexKey];
            }

            return new AdressenCollection($array);
        }
        $indexKey = array_search($KUNNUM, array_column(self::$DATASET, 'KUNNUM1'));
        $indexKey = $indexKey ?? array_search($KUNNUM, array_column(self::$DATASET, 'KUNNUM2'));

        $array = self::$DATASET[$indexKey];

        return new AdressenCollection($array);
    }

    public static function Connect($encrypted)
    {
        $data = json_decode(decryptIt(base64_decode($encrypted)), true);
        if (is_array($data)) {
            (new DB())->Query("
				INSERT INTO egw_addressbook_extra (contact_id, contact_owner, contact_name, contact_value) 
				VALUES ('$data[contact_id]', '-93', 'cao kunden nr', '$data[num]');
			");

            return true;
        } else {
            return false;
        }
    }

    public static function Search($query)
    {
        $addresses = self::$DATASET;
        $fuzz = new Fuzz();

        usort($addresses, function ($a, $b) use ($query, $fuzz) {
            $a_label = ($a['KTO_INHABER'].' '.$a['MATCHCODE'].' '.$a['NAME1'].' '.$a['NAME2'].' '.$a['NAME3']);
            $b_label = ($b['KTO_INHABER'].' '.$b['MATCHCODE'].' '.$b['NAME1'].' '.$b['NAME2'].' '.$b['NAME3']);

            $percent_a = $fuzz->tokenSetRatio($query, $a_label);
            $percent_b = $fuzz->tokenSetRatio($query, $b_label);

            return $percent_a === $percent_b ? 0 : ($percent_a > $percent_b ? -1 : 1);
        });

        return array_slice($addresses, 0, 9);
    }

    public static function init_static()
    {
        self::$DATASET = self::getCache();
    }

    private static function getCache()
    {
        $DATASET = unserialize(Api\Cache::getCache(Api\Cache::INSTANCE, __CLASS__, 'ADRESSEN_DATASET'));
        if (empty($DATASET)) {
            self::reloadCache();

            return self::$DATASET;
        }

        return $DATASET;
    }

    private static function reloadCache()
    {
        $ADRESSEN = Request::Run('SELECT * FROM ADRESSEN');
        Api\Cache::setCache(Api\Cache::INSTANCE, __CLASS__, 'ADRESSEN_DATASET', serialize($ADRESSEN), 3600);
        self::$DATASET = $ADRESSEN;
    }

    private static function Update()
    {
        self::reloadCache();
    }

    private static function UnsetCache()
    {
        Api\Cache::unsetCache(Api\Cache::INSTANCE, __CLASS__, 'ADRESSEN_DATASET');

        return true;
    }

    public static function SearchFor($arrays, $assoc_key, $value)
    {
        foreach ($arrays as $key => $array) {
            if ($array[$assoc_key] == $value) {
                return $array;
            }
        }

        return false;
    }

    public function GetAdresses()
    {
        $addresses = [];

        $IN_KUNNUM = implode(',', (array) $this->relationships['cao']);
        $cao = Request::Run("
			SELECT * FROM ADRESSEN 
			WHERE KUNNUM1 IN($IN_KUNNUM)
		");

        if (!empty($this->relationships['egw'])) {
            $IN_CONTACTIDS = implode(',', $this->relationships['egw']);
            $egw = (new DB("
				SELECT * FROM egw_addressbook
				WHERE contact_id IN($IN_CONTACTIDS)
			"))->FetchAll();
        }

        $i = 0;
        foreach ((array) $this->relationships['egw_cao'] as $egw_id => $cao_id) {
            if ($row = self::SearchFor($cao, 'KUNNUM1', $cao_id)) {
                $addresses[$i] = self::SearchFor($egw, 'contact_id', $egw_id);
                $addresses[$i]['cao_conn'] = $row;
                $i++;
            }
        }
        //Dump($addresses);
        return $addresses;
    }
}

Adressen::init_static();
