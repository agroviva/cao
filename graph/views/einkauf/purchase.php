<?php

$ConfArr = [
    'class'   => "CAO\Einkauf\Einkauf",
    'name'    => 'Einkauf',
    'type'    => 'Einkauf||Einkauf',
    'ask'     => 'Wollen Sie einen Einkauf erstellen?',
    'success' => 'Der Einkauf wurde erfolgreich in CAO erstellt!',
];

use AgroEgw\DB;
use altayalp\FtpClient\FileFactory;
use altayalp\FtpClient\Servers\SftpServer;
use CAO\Core\Filesystem;

class SchrozbergEK
{
    public static $serverFiles = [];
    public static $remoteFiles = [];

    public static $remoteDir = '';
    public static $serverDir = '';

    public function __construct()
    {
    }

    public static function Run()
    {
        self::$serverFiles = self::onlyCSV(Filesystem::scan(self::$serverDir));

        $Data = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name LIKE 'settings'"))->Fetch();

        if (!empty($Data)) {
            $metaData = json_decode($Data['meta_data'], true);
            $server = new SftpServer($metaData['SFTPServer']);
            $server->login($metaData['SFTPUsername'], $metaData['SFTPPassword']);

            self::$remoteDir = $metaData['SFTPPath'];
            self::$serverDir = $metaData['SFTPPath'];
        }

        $file = FileFactory::build($server);
        $list = $file->ls(trim(self::$remoteDir, '/'));
        self::$remoteFiles = self::onlyCSV($list);

        // var_dump($result);

        foreach (self::$remoteFiles as $filename) {
            if (!self::remoteFolderContains($filename)) {
                $file->download(self::$remoteDir.'/'.$filename, sys_get_temp_dir().'/'.$filename);
                Filesystem::upload(Filesystem::getTempData(sys_get_temp_dir().'/'.$filename), static::$remoteDir, false);
            }
        }
    }

    public static function onlyCSV(array $remoteFiles)
    {
        $files = [];
        if (!empty($remoteFiles)) {
            foreach ($remoteFiles as $file) {
                $type = explode('.', $file);
                if (strtolower(end($type)) == 'csv') {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    public static function remoteFolderContains(string $file)
    {
        if (in_array($file, self::$serverFiles)) {
            return true;
        }

        return false;
    }
}

if ($_SERVER['SERVER_NAME'] == 'e01.agroviva.net') {
    SchrozbergEK::Run();
}

include_once dirname(__DIR__).'/verkauf/delivery-note.php';
