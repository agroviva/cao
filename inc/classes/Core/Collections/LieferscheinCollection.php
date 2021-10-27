<?php

namespace CAO\Core\Collections;

class LieferscheinCollection extends DefaultCollection
{
    protected static $MainNumbersName = 'EDIT';
    protected static $MainNumKey = 'VLSNUM';

    protected static $tablename = 'LIEFERSCHEIN';
    protected static $hasChild = "LIEFERSCHEIN_POS";
    protected static $foreignKey = "LIEFERSCHEIN_ID";
    public function __construct(array $collection = [])
    {
        parent::__construct($collection);
    }
}
