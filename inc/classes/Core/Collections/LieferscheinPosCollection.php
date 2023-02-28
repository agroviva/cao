<?php

namespace CAO\Core\Collections;

class LieferscheinPosCollection extends DefaultCollection
{
	protected static $tablename = 'LIEFERSCHEIN_POS';
	protected static $belongsTo = 'LIEFERSCHEIN';
	protected static $foreignKey = 'LIEFERSCHEIN_ID';

	public function __construct(array $collection = [])
	{
		parent::__construct($collection);
	}
}
