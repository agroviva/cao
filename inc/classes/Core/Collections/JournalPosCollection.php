<?php

namespace CAO\Core\Collections;

class JournalPosCollection extends DefaultCollection
{
    protected static $tablename = 'JOURNALPOS';
    protected static $belongsTo = 'JOURNAL';
    protected static $foreignKey = 'JOURNAL_ID';

    public function __construct(array $collection = [])
    {
        parent::__construct($collection);
    }
}
