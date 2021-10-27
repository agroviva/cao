<?php
require_once dirname(__DIR__).'/api/cao.php';

class Graph
{
	public static function init_static(){
		$file = self::getRoute();

		require "theme/header.php";
		$path = __DIR__."/views/".$file;
		if (file_exists($path)) {
			require $path;
		}
		require "theme/footer.php";
	}

	public static function getRoute(){
		$route = $_SERVER['REQUEST_URI'];
		$arrayRoute = explode("graph", $route);

		$realRoute = $arrayRoute[1] ?? [];

		if (!empty($realRoute)) {
			$realRoute = trim($realRoute, "/");
			return $realRoute;
		}

		return false;
	}
}

Graph::init_static();