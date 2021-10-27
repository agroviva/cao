<?php
namespace CAO\Controllers;

use AgroEgw\DB;
use EGroupware\Api\Cache;

class SaveSettings
{
	public static function upload(){
		$Data = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name LIKE 'settings'"))->Fetch();

        if (!empty($_POST)) {
            header('Content-Type: application/json');
            $attr = $_POST;
            $settings = json_encode($attr);

            if (empty($Data)) {
                (new DB("INSERT INTO egw_cao_meta (meta_name, meta_connection_id, meta_data) VALUES ('settings', 0, '$settings')"));
            } else {
                (new DB("UPDATE egw_cao_meta SET meta_data = '$settings' WHERE meta_name LIKE 'settings'"));
            }

            Cache::unsetCache(Cache::INSTANCE, 'cao', 'SETTINGS');

            $Data = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name LIKE 'settings'"))->Fetch();
            if (!empty($Data)) {
                echo json_encode(array(
                    "responde" => "success",
                    "data"  => json_encode($Data)
                ));
            } else {
                echo json_encode(array(
                    "responde" => "failure",
                    "data"  => json_encode($Data)
                ));
            }	
            exit;
        }
	}
}