<?php

namespace CAO;

use AgroEgw\DB;
use EGroupware\Api\Cache;
use GuzzleHttp\Client;

class Request
{
    private static $Client;

    public static function OLDRUN(string $Query, $LastID = false)
    {
        if (DEBUG_MODE) {
            return self::Query($Query, $LastID);
        }

        if (!self::$Client) {
            self::$Client = new Client();
        }

        if ($LastID) {
            return (int) self::$Client->request('POST', CAO_URL, [
                'form_params' => [
                    'token'	=> \encryptIt(json_encode([
                        'query'	=> $Query,
                    ])),
                    'instance'    => INSTANCE,
                    'get_last_id' => true,
                    'debug_mode'  => DEBUG_MODE,
                ],
                'headers' => [
                    'Authorization' => 'Basic '.CAO_CREDENTIALS,
                ],
            ])->getBody();
        }

        $result = self::$Client->request('POST', CAO_URL, [
            'form_params' => [
                'token'	=> \encryptIt(json_encode([
                    //"query"	=> "SELECT * FROM ".self::TABLE." WHERE QUELLE = '13' AND QUELLE_SUB = '0' ORDER BY REC_ID DESC;",
                    'query'	=> $Query,
                ])),
                'instance'   => INSTANCE,
                'debug_mode' => DEBUG_MODE,
            ],
            'headers' => [
                'Authorization' => 'Basic '.CAO_CREDENTIALS,
            ],
        ])->getBody();

        // echo $result;

        $output = json_decode($result, true);

        return $output;
    }

    public static function Run(string $Query, $InsertID = false)
    {
        $Settings = json_decode(Cache::getCache(Cache::INSTANCE, 'CAO', 'SETTINGS'), true);
        if (empty($Settings)) {
            $Data = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name LIKE 'settings'"))->Fetch();
            Cache::setCache(Cache::INSTANCE, 'cao', 'SETTINGS', $Data['meta_data'], 3600);
            $Settings = json_decode($Data['meta_data'], true);
        }

        if (!empty($Settings)) {
            $mysqli = new \mysqli($Settings['MySQLServer'], $Settings['MySQLUsername'], $Settings['MySQLPassword'], $Settings['MySQLDatabase']);

            if ($mysqli->connect_errno) {
                echo 'Failed to connect to MySQL: '.$mysqli->connect_error;
                exit();
            }

            if (!$InsertID) {
                $result = $mysqli->query($Query);
                if (!empty($result)) {
                    $result = $result->fetch_all(MYSQLI_ASSOC);
                }
            } else {
                $mysqli->query($Query);
                $result = (int) $mysqli->insert_id;

                // On Update insert id is 0
                if (strpos($Query, 'UPDATE') !== false) {
                    $result = $mysqli->affected_rows;
                }
            }

            $mysqli->close();
        } else {
            $result = null;
        }

        return $result;
    }
}
