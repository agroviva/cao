<?php

namespace CAO\Core;

use AgroEgw\DB;
use EGroupware\Api\Vfs;

class Filesystem
{
	protected static $dir = '/apps/cao';

	protected static $activeDir = '/apps/cao/active';

	protected static $inactiveDir = '/apps/cao/inactive';

	public static function start()
	{
	}

	public static function init_static()
	{
		Vfs::$is_root = true;
		Vfs::$is_admin = true;

		if (!Vfs::is_dir(self::$activeDir)) {
			Vfs::mkdir(self::$activeDir, 0777, true);
		}

		if (!Vfs::is_dir(self::$inactiveDir)) {
			Vfs::mkdir(self::$inactiveDir, 0777, true);
		}

		// self::fwrite("start.csv", "hello");
	}

	public static function finish($path)
	{
		$errors = 0;
		$moved = [];
		Vfs::move_files([$path], self::$inactiveDir, $errors, $moved);
	}

	public static function upload($source, $destination = null, $isUploaded = true)
	{
		if (is_null($destination)) {
			$destination = self::$activeDir;
		}

		Vfs::copy_uploaded($source, $destination, null, $isUploaded);
	}

	public static function copy($source, $destination)
	{
		Vfs::copy_uploaded($source, $destination);
	}

	public static function scan($dir)
	{
		return Vfs::scandir($dir);
	}

	public static function fwrite($file, $content)
	{
		$file = Vfs::fopen(self::$activeDir.'/'.$file, 'w');
		fwrite($file, $content);
		fclose($file);
	}

	public static function move()
	{
	}

	public static function is_file($path)
	{
		return Vfs::is_file($path);
	}

	public static function is_dir($path)
	{
		return Vfs::is_dir($path);
	}

	public static function file_exists($path)
	{
		return Vfs::file_exists($path);
	}

	public static function getTempData($path)
	{
		$tempFile = [
			'type'     => 'text/csv',
			'tmp_name' => $path,
			'error'    => 0,
		];

		$filename = explode('/', $path);
		$filename = end($filename);
		$tempFile['name'] = $filename;
		$tempFile['size'] = filesize($path);

		return $tempFile;
	}

	public static function isImported($filename)
	{
		$result = DB::GetAll("SELECT * FROM egw_cao_meta WHERE meta_name = 'file_already_imported' AND meta_data = '".htmlspecialchars($filename)."'");

		if (empty($result)) {
			return false;
		}

		return true;
	}

	public static function markAsImported($filename)
	{
		if (!self::isImported($filename)) {
			(new DB("
                INSERT INTO egw_cao_meta (meta_name, meta_connection_id, meta_data) 
                VALUES ('file_already_imported', 0, '".htmlspecialchars($filename)."');
            "));
		}
	}
}

Filesystem::init_static();
