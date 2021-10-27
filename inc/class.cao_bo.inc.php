<?php

class cao_bo
{
    public function __construct()
    {
        $this->db = clone $GLOBALS['egw']->db;
    }
}
