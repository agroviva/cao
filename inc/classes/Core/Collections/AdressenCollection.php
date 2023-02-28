<?php

namespace CAO\Core\Collections;

class AdressenCollection extends DefaultCollection
{
	protected static $tablename = 'ADRESSEN';

	protected static $hasChild = ['ADRESSEN_LIEF'];
	protected static $foreignKey = 'ADDR_ID';
	protected static $searchKeys = ['KUNNUM1', 'KUNNUM2'];

	public function __construct($collection = [])
	{
		parent::__construct($collection);
	}
}
