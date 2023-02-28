<?php

namespace CAO\Core\Collections;

class EKBestellCollection extends DefaultCollection
{
	protected static $MainNumbersName = 'EDIT';
	protected static $MainNumKey = 'BELEGNUM';

	protected static $tablename = 'EKBESTELL';
	protected static $hasChild = 'EKBESTELL_POS';
	protected static $foreignKey = 'EKBESTELL_ID';

	public function __construct(array $collection = [])
	{
		parent::__construct($collection);
	}
}
