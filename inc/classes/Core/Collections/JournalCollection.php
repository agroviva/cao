<?php

namespace CAO\Core\Collections;

class JournalCollection extends DefaultCollection
{
    protected static $MainNumbersName = 'EDIT';
    protected static $MainNumKey = 'VRENUM';

    protected static $tablename = 'JOURNAL';
    protected static $hasChild = 'JOURNALPOS';
    protected static $foreignKey = 'JOURNAL_ID';

    public function __construct(array $collection = [])
    {
        parent::__construct($collection);
    }
}
