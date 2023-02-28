<?php

namespace CAO\Controllers;

use AgroEgw\DB;
use CAO\Core;
use CAO\Core\Artikel;
use EGroupware\Api\Vfs;

class ArtikelController
{
	public static function Connect()
	{
	}

	public static function Scan($class)
	{
		$ScanKey = strtoupper($class).'_DIRPATH';

		$sql = "SELECT * FROM egw_cao_meta WHERE meta_name LIKE '{$ScanKey}'";

		if ($dir_path = DB::Get($sql)) {
			$Files = self::ScanDir($ScanKey);
		} else {
			return json_encode(['ScanKey' => $ScanKey]);
		}

		$ARTIKEL = [];

		foreach ($Files as $key => $File) {
			if (DB::Get("SELECT * FROM egw_cao_meta WHERE meta_name = 'file_already_imported' AND meta_data = '".htmlspecialchars($File)."';")) {
				unset($Files[$key]);
			} else {
				$output = Core::readFile($File);

				$ArrayData = Core::CsvToArray($output);
				foreach ($ArrayData as $BELEGNUMMER => $DATASET) {
					foreach ($DATASET as $key => $VALUES) {
						$ARTIKEL[$VALUES['ARTIKELNUMMER']] = $VALUES['ARTIKELNUMMER'];
					}
				}
			}
		}
		$ARTIKEL_DATASET = Artikel::Get();
		foreach ($ARTIKEL as $VALUE) {
			if (DB::Get("SELECT * FROM egw_cao_meta WHERE meta_name LIKE 'HERST_ARTNUM' AND meta_connection_id = $VALUE")) {
				unset($ARTIKEL[$VALUE]);
			}
		}
		header('content-type: application/json; charset=UTF-8');

		return json_encode(['unconnected' => array_values($ARTIKEL), 'ARTIKEL' => $ARTIKEL_DATASET]);
	}

	private static function ScanDir($ScanKey, $path = '/', $recursive = false)
	{
		$Files = [];
		$sql = "SELECT * FROM egw_cao_meta WHERE meta_name LIKE '$ScanKey'";

		if (!$recursive && $dir_path = DB::Get($sql)) {
			$path = $dir_path['meta_data'];
			$path = rtrim($path, '/').'/';

			$is_dir = Vfs::is_dir($path);
			$is_file = Vfs::file_exists($path);

			if ($is_dir) {
				$files = [];
				foreach ((array) Vfs::scandir($path) as $file) {
					if (Vfs::is_readable($fpath = Vfs::concat($path, $file))) {
						$file = [
							'file' => end(explode('/', $fpath)),
						];
						$files[] = $file;
					}
				}

				foreach ($files as $key => $file) {
					$fileParts = explode('.', $file['file']);
					if (count($fileParts) > 1) {
						if (strtolower(end($fileParts)) == 'csv') {
							$Files[] = $path.$file['file'];
						}
					} else {
						self::ScanDir($ScanKey, $path.$file['file'].'/', true);
					}
				}
			}
		}

		return $Files;
	}
}
