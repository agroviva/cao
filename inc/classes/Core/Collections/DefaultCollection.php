<?php

namespace CAO\Core\Collections;

use CAO\Core\MainNumbers;
use CAO\Request;
use CAO\Core;
use EGroupware\Api\Cache;
use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;


abstract class DefaultCollection extends Collection
{
    protected $MainNum = null;

    protected static $primaryKey = "REC_ID";

    protected static $searchKeys = [];

    public function __construct($collection = [])
    {
        parent::__construct($collection);
        if (empty($collection)) {
            $this->getMainNum()->addMitarbeiterID()->generateTERM_ID();
        }
    }

    public static function Find($num, string $key = "", $comparisonType = "="){
        $key = empty($key) ? self::$primaryKey : $key;
        $storageKey = static::$tablename.$key.$num;

        if ($result = Core::Temp($storageKey)) {
            return $result;
        }

        $sql = "SELECT * FROM ".static::$tablename." ";

        if (empty(static::$searchKeys)) {
            $sql .= "WHERE $key $comparisonType '{$num}'";
        } else {
            $sql .= "WHERE 1=1 ";
            $i = 0;
            foreach (static::$searchKeys as $searchKey) {
                $i++;
                if ($i == 1) {
                    $sql .= "AND (";
                }
                $sql .= "$searchKey $comparisonType '{$num}'";
                if ($i != count(static::$searchKeys)) {
                    $sql .= " OR ";
                }
            }
            if ($i > 0) {
                $sql .= ")";
            }
        }

        $result = Request::Run($sql);
        $result = is_array($result[0]) ? $result[0] : array();

        return Core::Temp($storageKey, new static($result));
    }

    public static function All(){
        $tablename = static::$tablename;
        $storageKey = $tablename."|ALL";

        $result = unserialize(Cache::getCache(Cache::INSTANCE, __CLASS__, $storageKey));
        if (empty($result)) {
            $query = "
                SELECT * FROM $tablename
                WHERE 1=1
            ";
            $result = Request::Run($query);

            $result = is_array($result) ? $result : array();
            Cache::setCache(Cache::INSTANCE, __CLASS__, $storageKey, serialize($result), 3600);
        }

        return new static($result);
    }

    public static function last($where = "", $order = "DESC"){
        $tablename = static::$tablename;
        $sql = "SELECT * FROM $tablename WHERE 1=1 ";
        $sql .= "$where ";
        $sql .= "ORDER BY $order";

        $storageKey = "$tablename|$where|$order";
        if ($result = Core::Temp($storageKey)) {
            return $result;
        } else {
             $result = Request::Run($sql);
             $result = is_array($result[0]) ? $result[0] : array();
        }

        return Core::Temp($storageKey, new static($result));
    }

    public static function first(){

    }

    public function addMitarbeiterID()
    {
        return $this->add('MA_ID', MA_ID);
    }

    public function setAddress(AdressenCollection $Adresse)
    {
        return $this->add('ADDR_ID', $Adresse->REC_ID) //"Kunden ID in der Datenbank";
            ->add('KUN_NUM', $Adresse->KUNNUM1 ?? $Adresse->KUNNUM2)
            ->add('GLOBRABATT', $Adresse->GRABATT) //"GLOBRABATT von der Adresse"; // GRABATT
            ->add('PR_EBENE', $Adresse->PR_EBENE) //"Preisebene von der Adresse"; // PR_EBENE
            ->add('LIEFART', $Adresse->KUN_LIEFART) //"Lieferart von der Adresse"; // KUN_LIEFART
            ->add('ZAHLART', $Adresse->KUN_ZAHLART) //"Zahlungsart von der Adresse" // KUN_ZAHLART
            ->add('GEGENKONTO', $Adresse->DEB_NUM)
            ->add('SOLL_NTAGE', $Adresse->BRT_TAGE)
            ->add('SOLL_SKONTO', $Adresse->NET_SKONTO)
            ->add('SOLL_STAGE', $Adresse->NET_TAGE)
            ->add('KUN_ANREDE', $Adresse->ANREDE)
            ->add('KUN_NAME1', $Adresse->NAME1)
            ->add('KUN_NAME2', $Adresse->NAME2)
            ->add('KUN_NAME3', $Adresse->NAME3)
            ->add('KUN_ABTEILUNG', $Adresse->ABTEILUNG)
            ->add('KUN_STRASSE', $Adresse->STRASSE)
            ->add('KUN_LAND', $Adresse->LAND)
            ->add('KUN_PLZ', $Adresse->PLZ)
            ->add('KUN_ORT', $Adresse->ORT)
            ->add('VERTRETER_ID', $Adresse->VERTRETER_ID);
    }

    public function getMainNum()
    {
        if (!isset(static::$MainNumbersName)) {
            return $this;
        }

        $MainNumber = MainNumbers::Find(static::$MainNumbersName);
        $this->MainNum = (int) $MainNumber->getNextID();

        $nextNum = $MainNumber->generateNextID();

        return $this->add(static::$MainNumKey, $nextNum);
    }

    public function updateMainNum()
    {
        if (!isset(static::$MainNumbersName)) {
            return $this;
        }

        $MainNum = $this->MainNum + 1;
        MainNumbers::Update(static::$MainNumbersName, $MainNum);
    }

    public function generateTERM_ID()
    {
        return $this->add('TERM_ID', 1);
    }

    public function BuildQuery()
    {
        $this->clear();

        // $builder = new GenericBuilder(); 

        // $query = $builder->insert()
        //     ->setTable(static::$tablename)
        //     ->setValues($this->collection);

        // $sql = $builder->write($query);

        // return $sql;

        $columns = $values = [];
        foreach ($this->collection as $column => $value) {
            $columns[] = $column;
            if (is_string($value)) {
                $value = addslashes($value);
                $values[] = "'$value'";
            } elseif (is_null($value)) {
                $values[] = 'NULL';
            } else {
                $values[] = $value;
            }
        }
        $columns = implode(', ', $columns);
        $values = implode(', ', $values);

        $sql = '
            INSERT INTO '.static::$tablename." ($columns)
            VALUES ($values)
        ";

        return $sql;
    }

    public function clear()
    {
        $columns = $this->temp('COLUMNS_OF_TABLE') ?? Request::Run('SHOW COLUMNS FROM  `'.static::$tablename.'`');
        $this->temp('COLUMNS_OF_TABLE', $columns);

        foreach ($this->collection as $key => $value) {
            $foundKey = array_search($key, array_column($columns, 'Field'));
            if ($foundKey === false) {
                $this->unset($key);
            } else {
                if (is_null($value) && isset($columns[$foundKey]["Default"])) {
                    $this->unset($key);
                }
            }
        }
    }

    public function hasChild($name){
        if (isset(static::$hasChild)) {
            if (is_array(static::$hasChild)) {
               return in_array($name, static::$hasChild);
            } elseif (static::$hasChild === $name){
                return true;
            }
        }
        return false;
    }

    public function  __get($name) {

        // check if the named key exists in our array
        if(array_key_exists($name, $this->collection)) {
            // then return the value from the array
            return $this->collection[$name];
        } else {
            if ($this->hasChild($name)) {
                $result = Request::Run("
                    SELECT * FROM {$name} 
                    WHERE ".static::$foreignKey." = ".$this->collection[self::$primaryKey]." 
                ");
                return new Collection($result);
            }
        }
        return new Collection();
    }
}
