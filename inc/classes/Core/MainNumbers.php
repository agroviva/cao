<?php

namespace CAO\Core;

use CAO\Request;

class MainNumbers
{
    private $MainNumbers;

    public function __construct($object)
    {
        $this->MainNumbers = $object;
    }

    public function getModel()
    {
        $parts = explode('"', $this->MainNumbers->VAL_CHAR);
        if (count($parts) == 3) {
            return $parts[2];
        }

        return $parts[0];
    }

    public function getPrefix()
    {
        $parts = explode('"', $this->MainNumbers->VAL_CHAR);
        if (count($parts) == 3) {
            return $parts[1];
        }

        return '';
    }

    public function getLength()
    {
        return strlen($this->getModel());
    }

    public function getNextID()
    {
        return $this->MainNumbers->VAL_INT2;
    }

    public function generateNextID()
    {
        return $this->getPrefix().''.sprintf('%0'.$this->getLength().'d', $this->getNextID());
    }

    public static function Find($NAME)
    {
        $MainNumbers = Request::Run("
			SELECT * FROM `REGISTRY` 
			WHERE `MAINKEY` LIKE CONVERT(_utf8 '%NUMBERS%' USING latin1) COLLATE latin1_swedish_ci
		");

        $object = (object) [];

        if (!empty($MainNumbers)) {
            $key = array_search($NAME, array_column($MainNumbers, 'NAME'));
            $object = (object) $MainNumbers[$key];
        }

        return new self($object);
    }

    public static function Update($NAME, $NEXTNUM)
    {
        Request::Run("
			UPDATE REGISTRY SET VAL_INT2 = '{$NEXTNUM}' 
			WHERE NAME LIKE '{$NAME}'
		", true);
    }
}
