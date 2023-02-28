<?php

namespace CAO\Core;

use CAO\Core;
use CAO\Core\Collections\Collection;
use CAO\FileImport;

class Csv extends Collection
{
	protected $collection = [];

	private $content;

	public function __construct($content)
	{
		$this->content = $content;
		$this->convertToArray();
		parent::__construct();
	}

	public function convertToArray()
	{
		$content = str_replace('"', '', $this->content);
		$csv = explode("\n", $content);

		foreach ($csv as $key => $line) {
			$cells = explode(';', $line);
			foreach ($cells as $cellKey => $value) {
				$value = FileImport::escape($value);
				$cells[$cellKey] = $value;
				if (empty($cells[$cellKey])) {
					unset($cells[$cellKey]);
				}
			}
			$count = count($cells);
			if (isset($lastCount) && $count != $lastCount) {
				unset($csv[$key]);
				continue;
			}
			$lastCount = $count;

			$csv[$key] = $cells;
		}
		$this->collection = $csv ?? [];
	}

	public function header()
	{
		$header = $this->get(0) ?? [];

		return new Collection(array_map('strtoupper', $header));
	}

	public function primaryKey($key)
	{
		$header = $this->header()->array();
		foreach ($header as $indexKey => $value) {
			if (strtoupper($key) === strtoupper($value)) {
				$this->unset(0);
				break;
			}
		}

		$array = $this->array();
		$outputArray = [];
		foreach ($array as $key => $value) {
			$outputArray[$value[$indexKey]][] = array_combine($header, $value);
		}

		return new Collection($outputArray);
	}

	public static function parse($content)
	{
		return new self($content);
	}

	public static function parseFile($file)
	{
		$content = Core::readFile($file);

		return self::parse($content);
	}
}
