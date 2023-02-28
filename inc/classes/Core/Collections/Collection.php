<?php

namespace CAO\Core\Collections;

class Collection
{
	protected $collection = [];
	protected $temp = [];

	public function __construct($collection = [])
	{
		if (is_array($collection)) {
			$this->add($collection);
		}
	}

	public function get($key)
	{
		return $this->collection[$key];
	}

	public function add($key, $value = '')
	{
		if (is_array($key)) {
			$collection = $key;
			foreach ($collection as $key => $value) {
				$this->collection[$key] = $value;
			}
		} else {
			$this->collection[$key] = $value;
		}

		return $this;
	}

	public function unset($key)
	{
		unset($this->collection[$key]);
	}

	public function delete($key)
	{
		$this->unset($key);
	}

	public function remove($key)
	{
		$this->unset($key);
	}

	public function toArray()
	{
		return $this->collection ?? [];
	}

	public function array()
	{
		return $this->toArray();
	}

	public function object()
	{
		return (object) $this->collection;
	}

	public function each($callback)
	{
		foreach ($this->collection as $index => $value) {
			call_user_func_array($callback, [$index, $value]);
		}
	}

	public function temp($key, $value = null)
	{
		if (!is_null($value)) {
			$this->temp[$key] = $value;
		}

		return $this->temp[$key];
	}

	public function empty()
	{
		return empty($this->collection);
	}

	public function count()
	{
		return count($this->collection);
	}

	public function dump()
	{
		Dump($this->collection);

		return $this;
	}

	public function diff($collection)
	{
		if ($collection instanceof Collection) {
			$collection = $collection->array();
		} elseif (!is_array($collection)) {
			$collection = [];
		}

		$array = ['first' => [], 'second' => []];

		foreach ($collection as $key => $item) {
			if (empty($this->collection[$key]) || $collection[$key] != $this->collection[$key]) {
				$array['first'][$key] = $this->collection[$key];
				$array['second'][$key] = $collection[$key];
			}
		}

		return $array;
	}
}
