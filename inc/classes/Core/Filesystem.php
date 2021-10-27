<?php
namespace CAO\Core;

use EGroupware\Api\Vfs;
use AgroEgw\DB;

class Filesystem
{

    protected static $dir = "/apps/cao";

    protected static $activeDir = "/apps/cao/active";

    protected static $inactiveDir = "/apps/cao/inactive";

    static function start(){

    }

    static function init_static(){
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

    static function finish($path){
        $errors = 0;
        $moved = [];
        Vfs::move_files(array($path), self::$inactiveDir, $errors, $moved);
    }

    static function upload($source, $destination = null, $isUploaded = true){
        if (is_null($destination)) {
            $destination = self::$activeDir;
        }

        Vfs::copy_uploaded($source, $destination, null, $isUploaded);
    }

    static function copy($source, $destination){
        Vfs::copy_uploaded($source, $destination);
    }

    static function scan($dir){
        return Vfs::scandir($dir);
    }

    static function fwrite($file, $content) {
        $file = Vfs::fopen(self::$activeDir."/".$file, "w");
        fwrite($file, $content);
        fclose($file);
    }

    static function move(){

    }

    static function is_file($path){
        return Vfs::is_file($path);
    }

    static function is_dir($path){
        return Vfs::is_dir($path);
    }

    static function file_exists($path){
        return Vfs::file_exists($path);
    }

    static function getTempData($path) {
        $tempFile = [
            "type" => "text/csv",
            "tmp_name" => $path,
            "error" => 0
        ];

        $filename = explode("/", $path);
        $filename = end($filename);
        $tempFile["name"] = $filename;
        $tempFile["size"] = filesize($path);

        return $tempFile;
    }

    static function isImported($filename) {
        $result = DB::GetAll("SELECT * FROM egw_cao_meta WHERE meta_name = 'file_already_imported' AND meta_data = '".htmlspecialchars($filename)."'");

        if (empty($result)) {
            return false;
        }
        
        return true;
    }

    static function markAsImported($filename){

        if (!self::isImported($filename)) {
            (new DB("
                INSERT INTO egw_cao_meta (meta_name, meta_connection_id, meta_data) 
                VALUES ('file_already_imported', 0, '".htmlspecialchars($filename)."');
            "));        
        }
    }
    
}

Filesystem::init_static();