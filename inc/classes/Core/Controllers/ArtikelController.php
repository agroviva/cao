<?php

namespace CAO\Core\Controllers;

class ArtikelController
{
    protected $ARTIKEL;

    public function __construct($object)
    {
        $this->ARTIKEL = (object) $object;
    }
}
