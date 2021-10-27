<?php

namespace CAO\Core\Controllers;

class MitarbeiterController
{
    protected $WORKER;

    public function __construct($object)
    {
        $this->WORKER = (object) $object;
    }

    public function Show()
    {
        return $this->WORKER;
    }

    public function getId()
    {
        return (int) $this->WORKER->MA_ID;
    }

    public function getNumber()
    {
        return $this->WORKER->MA_NUMMER;
    }

    public function getUsername()
    {
        return $this->WORKER->LOGIN_NAME;
    }

    public function getFullname()
    {
        return $this->WORKER->ANZEIGE_NAME;
    }

    public function getName()
    {
        return $this->getFullname();
    }

    public function getFirstname()
    {
        return $this->WORKER->VNAME;
    }

    public function getSurname()
    {
        return $this->WORKER->NAME;
    }

    public function getEmail()
    {
        return $this->WORKER->EMAIL;
    }
}
