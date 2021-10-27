<?php

namespace CAO\Einkauf;

use AgroEgw\DB;
use CAO\Core\Filesystem;
use EGroupware\Api\Vfs;

abstract class EinkaufTrait
{
    protected static $ScanKey;

    protected static $Config;

    public function __construct()
    {
        Filesystem::start();
        $Config = $GLOBALS['ConfArr'];
        if (!empty($Config)) {
            self::$Config = $Config;
            self::$ScanKey = strtoupper($Config['name']).'_DIRPATH';
        }
    }

    public function ScanDir($path = '/', $recursive = false)
    {
        if (!$recursive && $dir_path = (new DB("
			SELECT * FROM egw_cao_meta 
			WHERE meta_name LIKE '".self::$ScanKey."';
		"))->Fetch()) {
            $path = $dir_path['meta_data'];
            $path = rtrim($path, '/').'/';
        }

        $is_dir = Vfs::is_dir($path);
        $is_file = Vfs::file_exists($path);

        if ($is_dir) {
            $files = [];
            foreach (Vfs::scandir($path) as $file) {
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
                        $this->Files[] = $path.$file['file'];
                    }
                } else {
                    $this->ScanDir($path.$file['file'].'/', true);
                }
            }
        }

        return $this;
    }

    public static function SetDir($dir_path = '')
    {
        self::$ScanKey = strtoupper(explode('||', TYPE)[1]).'_DIRPATH';

        header('content-type: application/json; charset=UTF-8');
        if (empty($dir_path)) {
            $dir_path = $_REQUEST['dir_path'];
        }

        if (!empty($dir_path)) {
            $check = (new DB("
				SELECT * FROM egw_cao_meta 
				WHERE meta_name LIKE '".self::$ScanKey."';
			"))->FetchAll();
            if ($check) {
                (new DB("
					UPDATE egw_cao_meta SET meta_data = '$dir_path' 
					WHERE meta_name LIKE '".self::$ScanKey."';
				"));
            } else {
                (new DB("
					INSERT INTO egw_cao_meta (meta_name, meta_connection_id, meta_data) 
					VALUES ('".self::$ScanKey."', 0, '$dir_path');
				"));
            }
            echo json_encode(['success']);

            return;
        }
        echo json_encode(['error']);
    }

    abstract public static function Create($data, $filename);
}
