<?php

namespace CAO\Core\Collections;

class ArtikelCollection extends DefaultCollection
{
    protected static $tablename = 'ARTIKEL';

    public function __construct($collection = [])
    {
        parent::__construct($collection);

        $this->ARTIKEL = $this->object();
    }

    public function Show()
    {
        return $this->ARTIKEL;
    }

    public function getId()
    {
        return (int) $this->ARTIKEL->REC_ID;
    }

    public function getName(string $form = 'LANG')
    {
        $form = strtoupper($form).'NAME';

        return $this->ARTIKEL->$form;
    }

    public function getMatchcode()
    {
        return $this->ARTIKEL->MATCHCODE;
    }

    public function getPrice()
    {
        return round($this->ARTIKEL->EK_PREIS, 4);
    }

    public function getSteuerCode()
    {
        return $this->ARTIKEL->STEUER_CODE;
    }

    public function createdFrom()
    {
        return $this->ARTIKEL->ERST_NAME;
    }

    public function createdAt()
    {
        return $this->ARTIKEL->ERSTELLT;
    }

    public function updatedFrom()
    {
        return $this->ARTIKEL->ERST_NAME;
    }

    public function updatedAt()
    {
        return $this->ARTIKEL->ERSTELLT;
    }
}
