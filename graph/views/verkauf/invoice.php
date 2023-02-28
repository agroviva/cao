<?php if (!CAO_API) {
	exit();
}
use AgroEgw\Api\Categories;
use AgroEgw\DB;
use CAO\Core;
use CAO\Core\Adressen;
use CAO\Core\Mitarbeiter;
use CAO\UI;
use CAO\Verkauf\Rechnung;

?>
<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/cao.css">
<?php
	require APPDIR.'/graph/views/check.php';

	$CAO_Addresses = (new Adressen())->GetAdresses();
	$CAO_CONNECTIONS = [];
	$warning_timesheets = [];
	if (DEBUG_MODE) {
		//Core::$Settings['status_settings']['status_import'] = 2;
	}

	$warn_display = 'none';
	foreach ($CAO_Addresses as $key => $value) {
		//Dump($value['cao_conn']['KUNNUM1']);
	}

	$rechnungen = [];
	$rechnungen['keine']['address'] = 'Fehlerhafte Stundenzettel!';
	$rechnungen['keine']['children'] = [];
	foreach (Rechnung::recieveTimesheets() as $key => $timesheet) {
		$verknuepfung_adr = \EGroupware\Api\Link\Storage::get_links('timesheet', $timesheet['ts_id'], 'addressbook');

		// boolean, checks if there is an address linked with the timesheet
		if ($toCheck = count($verknuepfung_adr) < 1) {

			// we fetch here the links between the timesheet and the project
			$verknuepfung_proj = \EGroupware\Api\Link\Storage::get_links('timesheet', $timesheet['ts_id'], 'projectmanager');

			// if the connection exists
			if ($pr_id = array_values($verknuepfung_proj)[0]) {

				// we check if there is an address linked with the project
				$verknuepfung_adr = \EGroupware\Api\Link\Storage::get_links('projectmanager', $pr_id, 'addressbook');

				// if there is an address linked with the project we set $toCheck to false
				// in order to mark this dataset as linked with an address
				if (count($verknuepfung_adr) < 1) {
					$toCheck = false;
				}
			}
		}

		if (!is_numeric($timesheet['cat_id'])) {
			$toCheck = true;
		} elseif (!Core::CategoryExists($timesheet['cat_id'])) {
			$toCheck = true;
		}

		if ($toCheck) {
			$rechnungen['keine']['children'][] = $timesheet;
		} else {
			$address_id = array_values($verknuepfung_adr)[0];
			$KUNNUM = DB::Get("SELECT * FROM egw_addressbook_extra WHERE contact_name = 'cao kunden nr' AND contact_id = '$address_id'")['contact_value'];

			$address = DB::Get("SELECT * FROM egw_addressbook WHERE contact_id = '$address_id'");
			if (Rechnung::isConnected($CAO_Addresses, 'contact_id', $address_id)) {
				$rechnungen[$KUNNUM]['cao'] = true;
				$rechnungen[$KUNNUM]['address'] = Rechnung::GroupByCao($KUNNUM);
				$rechnungen[$KUNNUM]['address_id'] = $address_id;
				$rechnungen[$KUNNUM]['children'][] = $timesheet;
			} else {
				$rechnungen['egw_'.$address_id]['cao'] = false;
				$rechnungen['egw_'.$address_id]['address'] = $address['n_fileas'];
				$rechnungen['egw_'.$address_id]['address_id'] = $address_id;
				$rechnungen['egw_'.$address_id]['children'][] = $timesheet;
			}
		}
	}
	// Dump($CAO_CONNECTIONS);

	foreach ($rechnungen as $key => $rechnung) {
		$gesamt_netto = 0;
		foreach ($rechnung['children'] as $timesheet) {
			$quantity = ($timesheet['ts_duration'] ? (($timesheet['ts_duration'] / 60).'h') : $timesheet['ts_quantity']);
			$gesamt_netto += (floatval($quantity) * $timesheet['ts_unitprice']);
			$ts_id = "t_$timesheet[ts_id]";
			if (empty($timesheet['ts_unitprice'])) {
				$warning_timesheets[] = $timesheet['ts_id'];
				$warn_count++;
				$warn_display = 'block';
			}
		}
		$rechnungen[$key]['gesamt_netto'] = $gesamt_netto;
	}
	unset($rechnung);
?>
<script type="text/javascript" src="/egroupware/cao/js/lib/jquery.js"></script>
<script type="text/javascript" src="/egroupware/cao/js/lib/jquery-ui.js"></script>
<script type="text/javascript" src="/egroupware/cao/js/lib/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/cao.css">
<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/table.css">

<?php if (DEBUG_MODE) { ?>
	<?php echo UI::Warning('Warnung! Sie sind im Test Modus')?>
<?php } ?>
<link rel="stylesheet" href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome-font-awesome.min.css">
<?php UI::StickyNav(); ?>
<div id="Rechnung">
	<div class="container">
		<div class="header">
			<h1>CAO-Rechnung erstellen &#8226; Mitarbeiter: <?php echo Mitarbeiter::Find(MA_ID)->getName()?></h1>
			<div class="notification warning show-count" style="display: <?php echo $warn_display?>;" data-count="<?php echo $warn_count?>">
				<div class="dropdown-menu animated fadeInDown" id="notification-dropdown">
					<div class="dropdown-cheader">
		              <div class="dropdown-header">
		                <span class="triangle"></span>
		              </div>
		              <div class="dropdown-body">
		              	<?php foreach ($warning_timesheets as $key => $warn_timesheet) { ?>
	    					<div class="notificationng-scope" onclick="event.stopPropagation(); GoShowTimesheet('<?php echo $warn_timesheet?>')">
			                  <span>#<?php echo $warn_timesheet?> Stundesatz fehlt!</span>
			                </div>
			            <?php } ?>
		              </div>
		          	</div>
            </div>
			</div>
		</div>
		<ul id="list" class="list">
			<?php foreach ($rechnungen as $key => $rechnung) { ?>
				<li id="address_<?php echo base64_encode(encryptIt($rechnung['address_id']))?>">
					<div class="main-level">
						<!-- Avatar -->
						
						<!-- Contenedor del Comentario -->
						<div class="box">
							<div class="head">
								<h6 class="name"><?php echo $rechnung['address']?></h6>
								<?php if ($rechnung['cao'] || $key == 'keine') { ?>
									
								<?php } else { ?>
									<button class="no_cao_connection">CAO-Verknüpfung!</button>
								<?php } ?>
							</div>
							<div class="content" style="height: 68px;">
								<?php if ($key != 'keine') { ?>
									<?php echo UI::Rechnung()?>
								<?php } ?>
								<img title="Zeige Positionen" src="/egroupware/cao/templates/default/images/icon-more.png" class="pos_toggle">
								<div class="address_group timesheet_data">
									<div class="netto-price">
										<span>Nettopreis: <?php echo round($rechnung['gesamt_netto'], 2)?>€</span>
									</div><div class="brutto-price">
										<span>Bruttopreis: <?php echo round($rechnung['gesamt_netto'] * 1.19, 2)?>€</span>
									</div>	
								</div>
							</div>
						</div>
					</div>
					<!-- Respuestas de los comentarios -->
					<ul class="list reply-list" <?php echo ($key == 'keine') ? 'style="display: block;"' : ''?>>
						<?php
						foreach ($rechnung['children'] as $key => $timesheet) {
							$verknuepfung = \EGroupware\Api\Link\Storage::get_links('timesheet', $timesheet['ts_id'], 'addressbook');
							$user = DB::Get("SELECT * FROM egw_addressbook WHERE account_id = '".$timesheet['ts_owner']."'");

							if (!is_numeric($timesheet['cat_id'])) {
								$categoryName = 'Keine Kategorie!';
								$categoryColor = '#e5e5e5';
							} else {
								$categoryName = Categories::getName($timesheet['cat_id']);
								$categoryColor = Categories::getColor($timesheet['cat_id']);
							}

							switch ($categoryName) {
								case 'Arbeitszeit':
									$unit = ' pro Stunde'; // per hour
									$type = lang('Dauer');
									$quantity = ($timesheet['ts_duration'] ? (($timesheet['ts_duration'] / 60).'h') : $timesheet['ts_quantity']);
									break;
								case 'Hosting':
								case 'Sonstiges':
								case 'Hardware':
									$unit = ' pro Stück'; // per piece
									$type = lang('Menge');
									$quantity = ($timesheet['ts_duration'] ? (($timesheet['ts_duration'] / 60).'h') : $timesheet['ts_quantity'].'stk');
									break;
								case 'Fahrtkosten':
									$unit = ' pro Kilometer'; // per kilometer
									$type = lang('Länge');
									$quantity = ($timesheet['ts_duration'] ? (($timesheet['ts_duration'] / 60).'h') : $timesheet['ts_quantity'].'km');
									break;
								default:
									$unit = ' pro Stunde'; // per hour
									$type = lang('Dauer');
									$quantity = ($timesheet['ts_duration'] ? (($timesheet['ts_duration'] / 60).'h') : $timesheet['ts_quantity']);
									break;
							}
							$price = $timesheet['ts_unitprice'].'€'.$unit;
							$netto_price = (floatval($quantity) * $timesheet['ts_unitprice']).'€';
							if (empty($timesheet['ts_unitprice'])) {
								echo '<li class="errorBubble" >';
							} else {
								echo '<li>';
							} ?>
									<div id="t_<?php echo $timesheet['ts_id']?>" class="box">
										<div class="head">
											<h6 class="name<?php echo count($verknuepfung) >= 1 ? '' : ' no-address'?>"><?php echo $user['n_fn']?></h6>
											<span><?php echo date('d.m.Y', $timesheet['ts_start'])?></span>
											<div class="timesheet_data">
												<div class="quantity">
													<span><?php echo $type?>: <?php echo $quantity?></span>
												</div>
												<div class="price">
													<span><?php echo lang('Preis')?>: <?php echo $price?></span>
												</div>
												<div class="netto-price">
													<span><?php echo lang('Nettopreis')?>: <?php echo $netto_price?></span>
												</div>
												<div class="edit">
													<span class="popup" data-url="/egroupware/index.php?menuaction=timesheet.timesheet_ui.edit&ts_id=<?php echo $timesheet['ts_id']?>"><?php echo lang('edit')?></span>
												</div>
											</div>
										</div>
										<div class="content">
											<div class="metadata">
												<span class="ts_id">#<?php echo $timesheet['ts_id']?></span><br />
												<span class="cat_id" style="background: <?php echo $categoryColor?>;"><?php echo $categoryName?></span>
											</div>
											<div class="ts_title">
												<?php echo date('d.m.Y', $timesheet['ts_start']).' '?>
												<?php echo $timesheet['ts_project'] ? $timesheet['ts_project']."\n<br />" : ''?>
												<?php echo '#'.$timesheet['ts_id'].' '.$timesheet['ts_title']?>
											</div>
										</div>
									</div>
								</li>
							<?php
						}
						?>
					</ul>
				</li>
			<?php } ?>
		</ul>
	</div>
	<script type="text/javascript" src="/egroupware/cao/js/Rechnung.js"></script>
</div>
<div id="modal_container">
	<div id="modal" class="cao_modal"></div>
</div>