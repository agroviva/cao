<?php
if (!CAO_API) {
	exit();
}

use AgroEgw\DB;
use CAO\Core\Mitarbeiter;
use CAO\UI;

if (!$ConfArr) {
	$ConfArr = [
		'class'   => "CAO\Verkauf\Lieferschein",
		'name'    => 'Lieferschein',
		'type'    => 'Verkauf||Lieferschein',
		'ask'     => 'Wollen Sie einen Lieferschein erstellen?',
		'success' => 'Der Lieferschein wurde erfolgreich in CAO erstellt!',
	];
}

if (!defined('TYPE')) {
	define('TYPE', $ConfArr['type']);
}

$GLOBALS['ConfArr'] = $ConfArr;

class FileRenderingUI
{
	public static function init_static()
	{
		self::header();
		self::content();
		self::footer();
	}

	public static function content()
	{
		$GlobName = $GLOBALS['ConfArr']['name'];
		$OBJECT = new $GLOBALS['ConfArr']['class']();

		if (!PREMISSION) {
			UI::Error('Sie habe keinen Zugriff auf dieser Seite');
		} elseif (!MA_ID) {
			UI::Error("Verknüpfen Sie die Mitarbeiter zuerst um {$GlobName} erstellen zu können!!");
		} else {
			$OBJECT->Files = [];
			$dir_path = (new DB("
				SELECT * FROM egw_cao_meta 
				WHERE meta_name LIKE '".strtoupper($GlobName)."_DIRPATH';
			"))->Fetch();

			if ($dir_path) {
				$OBJECT->ScanDir();
				foreach ($OBJECT->Files as $key => $File) {
					if ((new DB("
							SELECT * FROM egw_cao_meta 
							WHERE meta_name = 'file_already_imported' 
								AND meta_data = '".htmlspecialchars($File)."';
						"))->Fetch()) {
						unset($OBJECT->Files[$key]);
					}
				}
			}

			if (DEBUG_MODE) {
				UI::Warning('Warnung! Sie sind im Test Modus');
			}
			$conf = [
				[
					'title'   => 'Einstellungen',
					'onclick' => "(new Settings('Bill'))",
					'icon'    => 'fa-cog',
				],
				[
					'title'   => 'Artikel Scannen',
					'onclick' => "(new Settings('ART_SCAN'))",
					'icon'    => 'fa-file-text-o',
				],
			];
			UI::StickyNav($conf); ?>
			<div id="<?php echo $GlobName?>">
				<div class="container">
					<div class="header">
						<h1><?php echo $GlobName?> erstellen &#8226; Mitarbeiter: <?php echo Mitarbeiter::Find(MA_ID)->getName()?></h1>
					</div>
					<ul id="list" class="list">
						<?php foreach ($OBJECT->Files as $key => $File) { ?>
						<li id="file_<?php echo base64_encode(encryptIt($File))?>">
							<div class="main-level">
								<div class="box">
									<div class="head">
										<h6 class="name"><?php echo $File?></h6>
									</div>
									<div class="content" style="height: 68px;">
										<?php echo UI::$GlobName()?>
									</div>
								</div>
							</div>
						</li>
						<?php } ?>
						<?php if (empty($OBJECT->Files) || !$dir_path) { ?>
						<li id="file_<?php echo base64_encode(encryptIt($File))?>">
							<div class="main-level">
								<div class="box">
									<div class="head">
										<h6 class="name">Wir konnten leider keine Dateien finden</h6>
									</div>
									<div class="content" style="height: 68px;">
										Stellen Sie sicher, dass der Pfad stimmt und Dateien beinhaltet!</br>
										Klicken Sie auf das Icon rechts um den Pfad einzugeben! 
									</div>
								</div>
							</div>
						</li>
						<?php } ?>
					</ul>
				</div>
			</div>
			<div id="modal_container">
				<div id="modal" class="cao_modal"></div>
			</div>
			<?php
		}
	}

	public static function header()
	{
		?>
			<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/cao.css">
			<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/nprogress.css">
			<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/table.css">
			<link rel="stylesheet" href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome-font-awesome.min.css">
			<script type="text/javascript" src="/egroupware/cao/js/lib/jquery.js"></script>
			<script type="text/javascript" src="/egroupware/cao/js/lib/nprogress.js"></script>
			<script type="text/javascript" src="/egroupware/cao/js/lib/jquery-ui.js"></script>
			<script type="text/javascript" src="/egroupware/cao/js/lib/sweetalert.min.js"></script>
		<?php
		require APPDIR.'/graph/views/check.php';
	}

	public static function footer()
	{
		?>
		<script type="text/javascript">
			var NAME = "<?php echo $GLOBALS['ConfArr']['name']?>";
			var TYPE = "<?php echo $GLOBALS['ConfArr']['type']?>";
			var ASK = "<?php echo $GLOBALS['ConfArr']['ask']?>";
			var SUCCESS = "<?php echo $GLOBALS['ConfArr']['success']?>";
		</script>
		<script type="text/javascript" src="/egroupware/cao/js/FileSaving.js"></script>
		<?php
	}
}

FileRenderingUI::init_static();
