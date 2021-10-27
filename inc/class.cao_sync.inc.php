<?php
/**
 * CAO - SYNC:.
 *
 * @link http://www.agroviva.de
 *
 * @author Enver Morinaj
 * @copyright (c) 2015-16 by Agroviva GmbH <info@agroviva.de>
 * @license ---- GPL - GNU General Public License
 *
 * @version $Id: class.cao_sync.inc.php $
 */
include_once __DIR__.'/../api/cao.php';

use AgroEgw\DB;
use altayalp\FtpClient\Servers\SftpServer;
use altayalp\FtpClient\FileFactory;

class cao_sync
{


    public function __construct()
    {

    }

    public function synchron()
    {
        $Data = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name LIKE 'settings'"))->Fetch();

        if (!empty($Data)) {
            $metaData = json_decode($Data["meta_data"], true);
            $server = new SftpServer($metaData["SFTPServer"]);
            $server->login($metaData["SFTPUsername"], $metaData["SFTPPassword"]);

            $file = FileFactory::build($server);
            $list = $file->ls($metaData["SFTPPath"]);
        }
    }

}
