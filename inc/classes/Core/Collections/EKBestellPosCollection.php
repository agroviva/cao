<?php

namespace CAO\Core\Collections;

class EKBestellPosCollection extends DefaultCollection
{
    protected static $tablename = 'EKBESTELL_POS';
    protected static $belongsTo = 'EKBESTELL';
    protected static $foreignKey = 'EKBESTELL_ID';

    public function __construct(array $collection = [])
    {
        parent::__construct($collection);
    }
}
