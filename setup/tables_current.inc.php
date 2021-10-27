<?php
/**
 * eGroupWare - Setup
 * http://www.egroupware.org
 * Created by eTemplates DB-Tools written by ralfbecker@outdoor-training.de.
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 *
 * @version $Id$
 */
$phpgw_baseline = [
    'egw_cao' => [
        'fd' => [
            'id'   => ['type' => 'auto', 'nullable' => false],
            'data' => ['type' => 'MED', 'precision' => '255', 'nullable' => false],
        ],
        'pk' => ['id'],
        'fk' => [],
        'ix' => [],
        'uc' => [],
    ],
    'egw_cao_meta' => [
        'fd' => [
            'id'                 => ['type' => 'auto', 'precision' => '11', 'nullable' => false],
            'meta_name'          => ['type' => 'mediumtext', 'precision' => '65535', 'nullable' => false],
            'meta_connection_id' => ['type' => 'int', 'precision' => '11', 'nullable' => false],
            'meta_data'          => ['type' => 'longtext'],
        ],
        'pk' => ['id'],
        'fk' => [],
        'ix' => [],
        'uc' => [],
    ],
];
