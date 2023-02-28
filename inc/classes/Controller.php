<?php

namespace CAO;

class Controller
{
	public static function Route()
	{
		if (DEBUG_MODE) {
			$config = $_REQUEST;
		} else {
			$config = $_POST;
		}

		$controller = $config['config'];
		$args = $config['args'] ?? [];

		header('content-type: application/json; charset=UTF-8');
		echo self::Render($controller, $args);
	}

	public static function Render($config, $args = [])
	{
		$parts = explode('@', $config);
		$controller = $parts[0];
		$method = $parts[1];

		$class = "\\CAO\\Controllers\\{$controller}";
		if (class_exists($class) && method_exists($class, $method)) {
			return call_user_func_array([$class, $method], [$args]);
		}

		return json_encode([
			'response' 	=> 'error',
			'msg'		     => 'No Controller or method was found!',
		]);
	}
}
