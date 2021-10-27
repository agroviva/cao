<?php
/**
 * cao - hooks:.
 *
 * @link http://www.egroupware.org
 *
 * @author Enver Morinaj
 * @copyright (c) 2005-11 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 *
 * @version $Id: class.cao_ui.inc.php $
 */
use EGroupware\Api\Egw;
use EGroupware\Api\Link;

class cao_hooks
{
    /**
     * Hook called by link-class to include test in the appregistry of the linkage.
     *
     * @param array/string $location location and other parameters (not used)
     *
     * @return array with method-names
     */
    public static function search_link($location)
    {
        unset($location);

        return [
            'title'             => 'cao.cao_bo.link_title',
            'query'             => 'cao.cao_bo.link_query',
            'view'              => [
                'menuaction' => 'cao.cao_ui.index',
            ],
            'view_id'    => 'cao_id',
            'view_popup' => '850x590',
            'edit_popup' => '850x590',
            'index'      => [
                'menuaction' => 'cao.cao_ui.index',
            ],
            'edit' => [
                'menuaction' => 'cao.cao_ui.edit',
            ],
            'edit_id'    => 'cao_id',
            'name'       => 'cao',
        ];
    }

    // To register all hooks for the app. on the proper location
    public static function all_hooks($args)
    {
        //var_dump(debug_print_backtrace());
        $appname = 'cao';
        $title = lang($GLOBALS['egw_info']['apps'][$appname]['title']);
        $location = is_array($args) ? $args['location'] : $args;
        // echo "<p>ts_admin_prefs_sidebox_hooks::all_hooks(".print_r($args,True).") appname='$appname', location='$location'</p>\n";

        if ($location == 'sidebox_menu') {
            if (($GLOBALS['egw_info']['user']['apps']['admin'] && $location != 'admin') || ($GLOBALS['egw_info']['user']['account_id'] == '116')) {
                $file = [
                    'Adressen'			  => Egw::link('/egroupware/cao/graph/stammdaten/address.php'),
                    'Artikel' 			  => Egw::link('/egroupware/cao/graph/stammdaten/artikel.php'),
                    'Mitarbeiter'		=> Egw::link('/egroupware/cao/graph/stammdaten/employee.php'),
                ];

                display_sidebox($appname, 'Stammdaten', $file);

                $file = [
                    'Einkauf'			     => Egw::link('/egroupware/cao/graph/einkauf/purchase.php'),
                    'EK-Bestellung'		=> Egw::link('/egroupware/cao/graph/einkauf/purchasing-order.php'),
                ];

                display_sidebox($appname, 'Einkauf', $file);

                $file = [
                    'Lieferschein'		=> Egw::link('/egroupware/cao/graph/verkauf/delivery-note.php'),
                    'Rechnung'			   => Egw::link('/egroupware/cao/graph/verkauf/invoice.php'),
                ];

                display_sidebox($appname, 'Verkauf', $file);
            }

            if ($GLOBALS['egw_info']['user']['apps']['admin'] && $location != 'admin') {
                $file = [
                    'Einstellungen' 	=> Egw::link('/egroupware/cao/graph/settings.php'),
                    'Datenbank' 		   => Egw::link('/egroupware/cao/graph/datenbank.php'),
                    'Hochladen' 		   => Egw::link('/egroupware/cao/graph/upload.php'),
                ];
            }
            display_sidebox($appname, 'Einstellung', $file);
        }

        if ($GLOBALS['egw_info']['user']['apps']['admin'] && $location != 'preferences') {
            $file = [];

            if ($location == 'admin') {
                display_section($appname, $file);
            } else {
                display_sidebox($appname, lang('Admin'), $file);
            }
        }
    }
}
