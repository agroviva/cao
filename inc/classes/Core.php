<?php

namespace CAO;

use AgroEgw\DB;
use EGroupware\Api\Vfs;

class Core
{
    public static $Settings;

    protected static $temp = [];

    public static function init_static()
    {
        self::$Settings = json_decode((new DB('
			SELECT * FROM egw_cao ORDER BY id DESC
		'))->Fetch()['data'], true);
        // Dump(self::$Settings);
    }

    public static function CatToArt($cat_id)
    {
        foreach (self::$Settings['connection'] as $key => $category) {
            if ($category['cat'] == $cat_id) {
                return $category['art'];
            }
        }

        throw new \Exception("ARTIKEL_NUMMER wasn't found", 1);
    }

    public static function CatToMeEnheit($cat_id)
    {
        foreach (self::$Settings['connection'] as $key => $category) {
            if ($category['cat'] == $cat_id) {
                return $category['me_einheit'];
            }
        }

        throw new \Exception("Mengeneinheit wasn't found", 1);
    }

    public static function ChangeStatus($timesheets)
    {
        $status = self::$Settings['status_settings']['status_finish'];
        if (!empty($timesheets) && is_array($timesheets)) {
            $myID = $GLOBALS['egw_info']['user']['account_id'];
            $unixtime = time();
            foreach ($timesheets as $key => $timesheet) {
                (new DB("UPDATE egw_timesheet SET ts_status = '$status', ts_modifier = $myID, ts_modified = $unixtime WHERE ts_id = '$timesheet[ts_id]'"));
                $timestamp = date('Y-m-d H:i:s');
                (new DB(
                    "
					INSERT INTO egw_history_log 
					(history_record_id, history_appname, history_owner, history_status, history_new_value, history_old_value, history_timestamp) 
					VALUES
					('$timesheet[ts_id]', 'timesheet', $myID, 'ts_status', '$status', '$timesheet[ts_status]', '$timestamp');"
                ));
                if ($timesheet['ts_modifier'] != $myID) {
                    (new DB(
                        "
						INSERT INTO egw_history_log 
						(history_record_id, history_appname, history_owner, history_status, history_new_value, history_old_value, history_timestamp) 
						VALUES
						('$timesheet[ts_id]', 'timesheet', $myID, 'ts_modifier', '$myID', '$timesheet[ts_modifier]', '$timestamp');"
                    ));
                }
            }
        }
    }

    public static function KUNNUM($contact_id)
    {
        $relationship = (new DB("
			SELECT * FROM egw_addressbook_extra 
			WHERE contact_id = '$contact_id'
		"))->Fetch();

        if ($relationship && !empty($relationship['contact_value'])) {
            $KUNNUM = $relationship['contact_value'];

            return $KUNNUM;
        }

        throw new \Exception('No relationship was found', 1);
    }

    public static function readFile($File)
    {
        $stream = (new Vfs\StreamWrapper());

        $f = fopen($stream->resolve_url($File), 'rb');
        $output = '';
        if (is_resource($f)) {
            while (!feof($f)) {
                $line = fgets($f);
                $output .= $line;
            }

            fclose($f);
        }

        return $output;
    }

    public static function CsvToArray($csv_content, $rows_match = "\n", $cells_match = ';')
    {
        $Array = $secondArray = $finalArray = [];

        $csv_content = str_replace('"', '', $csv_content);
        $rows = explode($rows_match, $csv_content);
        $row_count = 0;

        foreach ($rows as $row) {
            if ($row) {
                $row = trim($row);
                if ($row_count == 0) {
                    $cells = explode($cells_match, $row);
                    $count = 0;
                    foreach ($cells as $cell) {
                        $Array[] = [
                            'name' 	  => FileImport::escape($cell),
                            'dataset' => [],
                        ];
                        $count++;
                    }
                    $row_count++;
                    continue;
                }

                $cells = explode($cells_match, $row);
                $i = 0;
                foreach ($cells as $cell) {
                    if ($i < $count) {
                        $Array[$i]['dataset'][] = FileImport::escape($cell);
                    }

                    $i++;
                }
                $row_count++;
            }
        }

        $secondArray = [];
        foreach ($Array as $key => $value) {
            $secondArray[strtoupper($value['name'])] = $value['dataset'];
        }

        if (!empty($secondArray['BELEGNUMMER'])) {
            foreach ($secondArray['BELEGNUMMER'] as $key => $value) {
                foreach ($secondArray as $key_name => $keyValue) {
                    switch ($key_name) {
                        case 'BELEGNUMMER':
                            break;
                        case 'BRUTTOUMSATZ':
                            $secondArray[$key_name][$key] = floatval(str_replace(',', '.', str_replace('.', '', $secondArray[$key_name][$key])));

                            $finalArray[$value][$key]['NETTOUMSATZ'] = round($secondArray[$key_name][$key] / floatval('1.'.$secondArray['MWSTCODE'][$key]), 2);

                            $finalArray[$value][$key][$key_name] = $secondArray[$key_name][$key];
                            break;
                        case 'ARTIKELNUMMER':
                        case 'MWSTCODE':
                        case 'MENGE':
                        case 'KDNR_VOM_LIEFERANTEN':
                            $finalArray[$value][$key][$key_name] = intval($secondArray[$key_name][$key]);
                            break;
                        case 'STUECKPREIS':
                            $finalArray[$value][$key][$key_name] = floatval(str_replace(',', '.', str_replace('.', '', $secondArray[$key_name][$key])));
                            break;
                        default:
                            $finalArray[$value][$key][$key_name] = $secondArray[$key_name][$key];
                            break;
                    }
                }
            }
        }

        return $finalArray;
    }

    public static function CsvToTable($csv_content, $rows_match = "\n", $cells_match = ';')
    {
        echo '<table id="csv_output" class="table table-striped table-bordered" cellspacing="0" width="100%">';
        $csv_content = str_replace('"', '', $csv_content);
        $rows = explode($rows_match, $csv_content);
        $row_count = 0;

        foreach ($rows as $row) {
            if ($row) {
                $row = trim($row);
                if ($row_count == 0) {
                    echo '<thead><tr>';
                    $cells = explode($cells_match, $row);
                    $count = 0;
                    foreach ($cells as $cell) {
                        echo '<th>'.FileImport::escape($cell).'</th>';
                        $count++;
                    }
                    echo '</tr></thead>';
                    echo "\n"; // just for presentation
                    $row_count++;
                    continue;
                }
                if ($row_count == 1) {
                    echo '<tbody>';
                }
                echo '<tr>';
                $cells = explode($cells_match, $row);
                $i = 0;
                foreach ($cells as $cell) {
                    if ($i < $count) {
                        echo '<td>'.FileImport::escape($cell).'</td>';
                    }
                    $i++;
                }
                echo '</tr>';
                echo "\n"; // just for presentation
                $row_count++;
            }
        }
        echo '</tbody>';
        echo '</table>';
    }

    public static function CategoryExists($cat_id)
    {
        $connections = Core::$Settings['connection'];

        foreach ($connections as $key => $connection) {
            if ($connection['cat'] == $cat_id) {
                return true;
            }
        }

        return false;
    }

    public static function Temp($key = null, $value = null)
    {
        if (is_null($key)) {
            return self::$temp;
        } elseif (!is_null($value)) {
            self::$temp[$key] = $value;
        }

        return self::$temp[$key];
    }

    public static function Search($array, $haystack, $needle)
    {
        $needle = strtolower($needle);
        foreach ($array as $key => $val) {
            $searchIn = strtolower($val[$haystack]);
            if (strpos($searchIn, $needle) !== false) {
                return $key;
            }
        }

        return false;
    }
}

Core::init_static();
