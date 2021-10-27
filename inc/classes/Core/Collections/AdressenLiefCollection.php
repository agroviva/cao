<?php

namespace CAO\Core\Collections;

class AdressenLiefCollection extends DefaultCollection
{
    protected static $tablename = 'ADRESSEN_LIEF';

    public function __construct($collection = [])
    {
        parent::__construct($collection);
    }
}
