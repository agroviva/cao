<?php
use AgroEgw\Api\Enqueue;
use AgroEgw\DB;
use CAO\Core;
use CAO\Core\Adressen;
use CAO\Core\Artikel;
use CAO\Core\Csv;
use CAO\Core\Filesystem;
use CAO\Einkauf\Einkauf;
use CAO\Einkauf\EKBestellung;
use CAO\FileImport;
use CAO\Request;
use CAO\Settings;
use CAO\Verkauf\Lieferschein;
use CAO\Verkauf\Rechnung;

require_once __DIR__.'/../api/cao.php';

if (empty($_REQUEST['type'])) {
	exit('Type was not defined');
}

define('TYPE', htmlentities($_REQUEST['type']));

class cao_ui
{
	public $public_functions = [
		'init'		   => true,
		'settings'	=> true,
		'request' 	=> true,
	];

	public function __construct()
	{
		$this->me = $GLOBALS['egw_info']['user'];
	}

	public function init()
	{
		Enqueue::Script('/cao/js/lib/nprogress.js');
		Enqueue::Script('/cao/js/Settings.js');

		switch (TYPE) {
			case 'Einkauf||EKBestellung':
			case 'Einkauf||Einkauf':
			case 'Verkauf||Lieferschein':
				if ($_REQUEST['action'] == 'SetDir') {
					Lieferschein::SetDir();
					exit;
				}
				break;
			case 'settings':
				if (!PREMISSION) {
					?>
					<h3>Sie habe keinen Zugriff auf dieser Seite</h3>
					<?php
					break;
				}

				switch ($_REQUEST['action']) {
					case 'Bill':
						header('content-type: application/json; charset=UTF-8');
						ob_start();
						?>
						<iframe src="/egroupware/index.php?menuaction=cao.cao_ui.init&type=file_import" style="width: 100%;height: 400px;padding: 10px;"></iframe>
						<?php
						$html = ob_get_clean();

						echo json_encode(['html' => $html, 'request' => $_REQUEST]);
						exit;
						break;

					default:
						// code...
						break;
				}

				ob_start();
				$this->create_header();
				$header = ob_get_clean();
				$header = str_replace('/pixelegg/css/pixelegg.css', '/egroupware/cao/css/settings.css', $header);
				echo $header;
				(new Settings())->UI();
				$this->create_footer();
				break;
			case 'check':
				$this->CheckIfOk();
				break;
			case 'ajax':
				$this->HandleAjax();
				break;
			case 'file_import':
				(new FileImport());
				break;
			default:
				$this->create_header();
				?>
				<h1>404: Seite nicht gefunden</h1>
				<?php
				$this->create_footer();
				break;
		}
	}

	public function HandleAjax()
	{
		//Send the character encoding header
		header('content-type: application/json; charset=UTF-8');

		if (!empty($_REQUEST['check_artikel'])) {
			$result = [];
			foreach ($_REQUEST['check_artikel'] as $key => $artikel_nummer) {
				$out = Request::Run("SELECT * FROM ARTIKEL WHERE ARTNUM = '".$artikel_nummer."'");
				if (!empty($out) && !empty($out[0])) {
					$result[$artikel_nummer] = 'Y';
				} else {
					$result[$artikel_nummer] = 'N';
				}
			}
			echo json_encode($result);
			exit();
		} elseif ($_REQUEST['save'] == 'settings' && !empty($_REQUEST['data'])) {
			$data = $_REQUEST['data'];
			(new DB("INSERT INTO egw_cao SET data = '{$data}'"));
			echo $_REQUEST['data'];
			exit();
		} elseif ($_REQUEST['save'] == 'conn_cao' && !empty($_REQUEST['contact_id'])) {
			$contact_id = decryptIt(base64_decode($_REQUEST['contact_id']));
			$address = (new DB("SELECT * FROM egw_addressbook WHERE contact_id = '$contact_id'"))->Fetch();
			$searchText = str_replace('-', ' ', $address['n_fileas']);

			$search = Adressen::Search($searchText);

			ob_start(); ?>
			<table class="table-fill">
				<thead>
					<tr>
						<th>Matchcode</th>
						<th>Kontoinhaber</th>
						<th>Name (Kunden-Nr.)</th>
						<th></th>
					</tr>
				</thead>
				<tbody class="table-hover">
					<?php foreach ($search as $key => $adr) { ?>
						<?php
						$KUNNUM = ($adr['KUNNUM1'] ? $adr['KUNNUM1'] : $adr['KUNNUM2']);
						$data_id = base64_encode(encryptIt(json_encode([
							'num'			     => $KUNNUM,
							'contact_id'	=> $contact_id,
						])));
						?>
						<tr>
							<td><?php echo $adr['MATCHCODE']?></td>
							<td><?php echo $adr['KTO_INHABER']?></td>
							<td><?php echo $adr['NAME1'].' '.$adr['NAME2'].' '.$adr['NAME3'].' ('.$KUNNUM.')'?></td>
							<td>
								<button data-id="<?php echo $data_id?>" class="save_btn_turkis">Verknüpfen</button>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php
			$html = ob_get_clean();
			unset($address['contact_jpegphoto']);
			echo json_encode(
				[
					$_REQUEST,
					'address' => $address,
					'search'  => $search,
					'html'    => $html,
				]
			);
			exit();
		} elseif ($_REQUEST['send'] == 'conn' && !empty($_REQUEST['data'])) {
			$success = Adressen::Connect($_REQUEST['data']);
			if ($success) {
				echo json_encode(['status'=>'ok']);
			} else {
				echo json_encode(['status'=>'error']);
			}
			exit();
		} elseif ($_REQUEST['check'] == 'address' && !empty($_REQUEST['contact_id'])) {
			$contact_id = decryptIt(base64_decode($_REQUEST['contact_id']));

			if (Rechnung::exists($contact_id)) {
				$status = 'exists';
			} else {
				$status = 'new';
			}
			echo json_encode(['bill'=>$status]);
			exit();
		} elseif ($_REQUEST['add'] == 'mitarbeiter') {
			$cao_id = $_REQUEST['cao_id'];
			$account_id = $_REQUEST['account_id'];
			$check = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name = 'mitarbeiter' AND meta_connection_id = '$account_id' AND meta_data = '$cao_id'"))->Fetch();
			if ($check) {
				(new DB("DELETE FROM egw_cao_meta WHERE id = '$check[id]'"));
			} else {
				(new DB())->Query("
				INSERT INTO 
				egw_cao_meta (meta_name, meta_connection_id, meta_data)
				VALUES ('mitarbeiter', '$account_id', '$cao_id');
				");
			}
			echo json_encode(['ok']);
			exit();
		} elseif ($_REQUEST['create'] == 'bill' && !empty($_REQUEST['contact_id'])) {
			$contact_id = decryptIt(base64_decode($_REQUEST['contact_id']));
			$bill_type = $_REQUEST['bill_type'];
			echo json_encode(
				(new Rechnung())->Create($contact_id, $bill_type)
			);
			exit();
		} elseif ($_REQUEST['create'] == 'EKBestellung') {
			$filename = $_REQUEST['filename'];
			if (!empty($filename)) {
				$filename = decryptIt(base64_decode($filename));
				$output = Core::readFile($filename);

				$return = EKBestellung::Create(Core::CsvToArray($output), $filename);
				echo json_encode($return);
			}
			exit;
		} elseif ($_REQUEST['create'] == 'Einkauf') {
			$filename = $_REQUEST['filename'];
			if (!empty($filename)) {
				$filename = decryptIt(base64_decode($filename));
				$output = Core::readFile($filename);

				$return = Einkauf::Create(Core::CsvToArray($output), $filename);
				echo json_encode($return);
			}
			exit;
		} elseif ($_REQUEST['create'] == 'Lieferschein') {
			$filename = $_REQUEST['filename'];
			if (!empty($filename)) {
				$filename = decryptIt(base64_decode($filename));

				$content = Csv::parseFile($filename);
				$output = $content->primaryKey('BESTELLNUMMERKUNDE')->array();

				$return = Lieferschein::Create($output, $filename);
				echo json_encode($return);
			}
			exit;
		} elseif ($_REQUEST['add'] == 'HERST_ARTNUM') {
			$HERST_ARTNUM = (int) htmlspecialchars($_REQUEST['HERST_ARTNUM']);
			$REC_ID = intval($_REQUEST['REC_ID']);
			if ($FOUND_ALREADY = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name LIKE 'HERST_ARTNUM' AND meta_connection_id = $HERST_ARTNUM"))->Fetch()) {
				(new DB("UPDATE egw_cao_meta SET meta_data = $REC_ID WHERE meta_connection_id = $HERST_ARTNUM;"));
				echo json_encode(['update']);
				Artikel::UnsetCache();
				exit;
			} else {
				(new DB("INSERT INTO egw_cao_meta (meta_name, meta_connection_id, meta_data) VALUES ('HERST_ARTNUM', '$HERST_ARTNUM', '$REC_ID');"));
				echo json_encode(['insert']);
				Artikel::UnsetCache();
				exit;
			}

			throw new Exception('Error Processing Request', 1);
		} elseif ($_REQUEST['remove' == 'file']) {
			$filename = $_REQUEST['filename'];
			if (!empty($filename)) {
				$filename = decryptIt(base64_decode($filename));
				Filesystem::markAsImported($filename);
			}
		}
	}

	public function CheckIfOk()
	{

		// if (!Core::$Settings) {
		// 	$this->create_header();
		// 	(new Settings())->UI();
		// 	$this->create_footer();
		// } else {
		$GLOBALS['egw']->redirect_link('/index.php', 'menuaction=cao.cao_ui.init&type=rechnung');
		// }
		exit();
	}
}
