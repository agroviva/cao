<?php

namespace CAO\Verkauf;

use AgroEgw\DB;
use CAO\Core;
use CAO\Core\Adressen;
use CAO\Core\Mitarbeiter;
use CAO\Request;

class Rechnung
{
	public function __construct()
	{
		if (DEBUG_MODE) {
			//Core::$Settings['status_settings']['status_import'] = 2;
		}
	}

	public static function GroupByCao($KUNNUM)
	{
		$connections = (new DB("SELECT * FROM egw_addressbook_extra WHERE contact_name = 'cao kunden nr' AND contact_value = '$KUNNUM'"))->FetchAll();
		$name = '';
		$i = 0;
		foreach ($connections as $key => $connection) {
			$i++;
			$address = (new DB("SELECT * FROM egw_addressbook WHERE contact_id = '$connection[contact_id]'"))->Fetch();
			if ($i == 1) {
				$name = $address['n_fileas'];
			} else {
				$name .= ' <font color="red">|</font> '.$address['n_fileas'];
			}
		}

		return $name;
	}

	public static function isConnected($arrays, $assoc_key, $value)
	{
		foreach ($arrays as $key => $array) {
			if ($array[$assoc_key] === $value) {
				return true;
			}
		}

		return false;
	}

	public static function recieveTimesheets()
	{
		$categories = Core::$Settings['connection'];
		$status = Core::$Settings['status_settings']['status_import'];
		$where = "1=1 AND ts_status = '$status'";

		// if (count($categories) > 1) {
		// 	$i=1;
		// 	foreach ($categories as $key => $category) {
		// 		if ($i == 1) {
		// 			$where .= "AND (";
		// 		}
		// 		if ($i == count($categories)) {
		// 			$where .= "cat_id = '".$category['cat']."')";
		// 		} else {
		// 			$where .= "cat_id = '".$category['cat']."' OR ";
		// 		}
		// 		$i++;
		// 	}
		// }
		// $where .= " OR ts_status = '$status'";

		$sql = "SELECT * FROM egw_timesheet WHERE $where";
		// echo $sql;
		return (new DB($sql))->FetchAll() ?: [];
	}

	public function Create($contact_id, $bill_type)
	{
		$LastBill = Request::Run("SELECT * FROM  `REGISTRY` WHERE  `NAME` LIKE 'EDIT'")[0];
		$Last_Rechnungsnummer = $LastBill['VAL_INT2'];

		$VRENUM = sprintf('%06d', $Last_Rechnungsnummer);

		$NEXTRENNUM = $Last_Rechnungsnummer + 1;
		// Dump($VRENUM);
		// exit;

		// if ($Last_Rechnungsnummer[0] == "0") {
		// 	//"Rechnungsnummer inkrementiert auf 1"; // Achtung die erste Ziffer "0" beim Inkrementieren

		// 	$VRENUM = (int)$Last_Rechnungsnummer + 1;
		// 	$VRENUM = "0".$VRENUM;
		// } else {
		// 	$VRENUM = (int)$Last_Rechnungsnummer + 1;
		// }

		$KUNNUM = (new DB("SELECT * FROM egw_addressbook_extra WHERE contact_id = '$contact_id' AND contact_name = 'cao kunden nr'"))->Fetch()['contact_value'];
		$CAO_ADDRESS = Adressen::Find($KUNNUM)->toArray();

		if ($bill_type == 'exists') {
			$Bill = self::exists($contact_id, true)[0];
			if ($Bill && is_array($Bill)) {
				$JOURNAL_ID = $Bill['REC_ID'];
				$VRENUM = $Bill['VRENUM'];
			} else {
				throw new \Exception("Couldn't find the bill", 1);
			}
		} else {
			$ADDR_ID = $CAO_ADDRESS['REC_ID']; //"Kunden ID in der Datenbank";
			$VERTRETER_ID = $CAO_ADDRESS['VERTRETER_ID']; //"VERTRETER_ID von der Adresse"; // VERTRETER_ID
			$GLOBRABATT = $CAO_ADDRESS['GRABATT']; //"GLOBRABATT von der Adresse"; // GRABATT
			$PR_EBENE = $CAO_ADDRESS['PR_EBENE']; //"Preisebene von der Adresse"; // PR_EBENE
			$LIEFART = $CAO_ADDRESS['KUN_LIEFART']; //"Lieferart von der Adresse"; // KUN_LIEFART
			$ZAHLART = $CAO_ADDRESS['KUN_ZAHLART']; //"Zahlungsart von der Adresse" // KUN_ZAHLART
			$MWST_1 = 19.00;
			$MWST_2 = 7.00;
			$MWST_3 = 10.70;

			$TERM_ID = 1; //$LastBill['TERM_ID']; // "Terminal (Windowsitzung)";
			$MA_ID = MA_ID; //"Mitarbeiter ID"; // default ist -1
			$QUELLE = 13;
			$QUELLE_SUB = 0;
			$RDATUM = date('Y-m-d H:i:s'); // Erstellungsdatum

			$WAEHRUNG = '€';
			$GEGENKONTO = $CAO_ADDRESS['DEB_NUM'];
			// $SOLL_NTAGE = $CAO_ADDRESS['BRT_TAGE'];
			// $SOLL_SKONTO = $CAO_ADDRESS['NET_SKONTO'];
			// $SOLL_STAGE = $CAO_ADDRESS['NET_TAGE'];

			$SOLL_RATINTERVALL = 1;
			$ERSTELLT = date('Y-m-d H:i:s');

			$MITARBEITER = Request::Run("SELECT * FROM MITARBEITER WHERE MA_ID = '$MA_ID'");
			$ERST_NAME = $MITARBEITER[0]['ANZEIGE_NAME'];
			$GEAEND = $ERSTELLT;
			$GEAEND_NAME = $ERST_NAME;
			$KUN_ANREDE = $CAO_ADDRESS['ANREDE'];
			$KUN_NAME1 = $CAO_ADDRESS['NAME1'];
			$KUN_NAME2 = $CAO_ADDRESS['NAME2'];
			$KUN_NAME3 = $CAO_ADDRESS['NAME3'];
			$KUN_ABTEILUNG = $CAO_ADDRESS['ABTEILUNG'];
			$KUN_STRASSE = $CAO_ADDRESS['STRASSE'];
			$KUN_LAND = $CAO_ADDRESS['LAND'];
			$KUN_PLZ = $CAO_ADDRESS['PLZ'];
			$KUN_ORT = $CAO_ADDRESS['ORT'];
			$FIRMA_ID = 1;

			$ZAHLUNGSART = Request::Run("SELECT * FROM  ZAHLUNGSARTEN WHERE REC_ID = '$ZAHLART'")[0];
			$LIEFERART = Request::Run("SELECT * FROM  LIEFERARTEN WHERE REC_ID = '$LIEFART'")[0];

			$ZAHLART_NAME = $ZAHLUNGSART['NAME'];
			$ZAHLART_KURZ = $ZAHLUNGSART['TEXT_KURZ'];
			$ZAHLART_LANG = $ZAHLUNGSART['TEXT_LANG'];
			$LIEFART_NAME = $LIEFERART['NAME'];

			$Query = "INSERT INTO JOURNAL (TERM_ID, MA_ID, QUELLE, QUELLE_SUB, ADDR_ID, KUN_NUM, VRENUM, KM_STAND, VERTRETER_ID, GLOBRABATT, RDATUM, PR_EBENE, LIEFART, ZAHLART, MWST_1, MWST_2, MWST_3, WAEHRUNG, GEGENKONTO, SOLL_RATINTERVALL, ERSTELLT, ERST_NAME, GEAEND, GEAEND_NAME, KUN_ANREDE, KUN_NAME1, KUN_NAME2, KUN_NAME3, KUN_ABTEILUNG, KUN_STRASSE, KUN_LAND, KUN_PLZ, KUN_ORT, FIRMA_ID, ZAHLART_NAME, ZAHLART_KURZ, ZAHLART_LANG, LIEFART_NAME) VALUES ('$TERM_ID', '$MA_ID', '$QUELLE', '$QUELLE_SUB', '$ADDR_ID', '$KUNNUM', '$VRENUM', -1 , '$VERTRETER_ID', '$GLOBRABATT', '$RDATUM', '$PR_EBENE', '$LIEFART', '$ZAHLART', '$MWST_1', '$MWST_2', '$MWST_3', '$WAEHRUNG', '$GEGENKONTO', '$SOLL_RATINTERVALL', '$ERSTELLT', '$ERST_NAME', '$GEAEND', '$GEAEND_NAME', '$KUN_ANREDE', '$KUN_NAME1', '$KUN_NAME2', '$KUN_NAME3', '$KUN_ABTEILUNG', '$KUN_STRASSE', '$KUN_LAND', '$KUN_PLZ', '$KUN_ORT', '$FIRMA_ID', '$ZAHLART_NAME', '$ZAHLART_KURZ', '$ZAHLART_LANG', '$LIEFART_NAME');";

			$JOURNAL_ID = Request::Run($Query, true);

			$RechnungsnummerUpdate = Request::Run("UPDATE REGISTRY SET VAL_INT2 = '$NEXTRENNUM' WHERE NAME LIKE 'EDIT'", true);
		}

		if ($JOURNAL_ID) {
			$Bill = self::exists($contact_id, true)[0];
			if ($Bill && is_array($Bill)) {
				$JOURNAL_ID = $Bill['REC_ID'];
			}
			$Inserted_Timesheets = $this->AddPositions($JOURNAL_ID, $contact_id, $CAO_ADDRESS, $VRENUM);

			if (!DEBUG_MODE) {
				Core::ChangeStatus($Inserted_Timesheets);
			}

			return [
				'status'     => 'done',
				'query'	     => $Query,
				'settings'   => Core::$Settings,
				'timesheets' => $Inserted_Timesheets,
				'DEBUG_MODE' => DEBUG_MODE,
			];
		} else {
			return [
				'status' => 'error',
				'query'	 => $Query,
			];
		}
	}

	public function AddPositions($JOURNAL_ID, $contact_id, $CAO_ADDRESS, $VRENUM)
	{
		$QUELLE = 13;
		$QUELLE_SUB = 0;
		$JOURNAL_ID = $JOURNAL_ID;
		$KUNNUM = (new DB("SELECT * FROM egw_addressbook_extra WHERE contact_id = '$contact_id' AND contact_name = 'cao kunden nr'"))->Fetch()['contact_value'];
		$ADDR_ID = $CAO_ADDRESS['REC_ID']; //"Kunden ID in der Datenbank";

		$timesheets_all = self::recieveTimesheets();

		foreach ($timesheets_all as $key => $timesheet_value) {
			$verknuepfung = \EGroupware\Api\Link\Storage::get_links('timesheet', $timesheet_value['ts_id'], 'addressbook');
			$KUNNUM = (new DB("SELECT * FROM egw_addressbook_extra WHERE contact_name = 'cao kunden nr' AND contact_id = '$contact_id'"))->Fetch()['contact_value'];
			if (count($verknuepfung) >= 1) {
				$address_id = array_values($verknuepfung)[0];
				$CHECK_KUNNUM = (new DB("SELECT * FROM egw_addressbook_extra WHERE contact_name = 'cao kunden nr' AND contact_id = '$address_id'"))->Fetch()['contact_value'];
				if (($address_id == $contact_id) || ($KUNNUM == $CHECK_KUNNUM)) {
					$timesheets[] = $timesheet_value;
				}
			}
		}
		unset($timesheets_all);
		$LAST_POSITION = $this->getLastPosition($JOURNAL_ID);
		$BRUTTO_SUMME = $M_SUMME = $NETTO_SUMME = $GESAMT_ROHGEWINN = 0;
		$POSITIONS_OF_BILL = Request::Run("SELECT * FROM JOURNALPOS WHERE JOURNAL_ID = '$JOURNAL_ID'");
		foreach ($timesheets as $key => $timesheet) {
			$cat_id = $timesheet['cat_id'];
			$ARTNUM = Core::CatToArt($cat_id);
			$ARTIKEL = Request::Run("SELECT * FROM ARTIKEL WHERE ARTNUM = '$ARTNUM'")[0];
			// Artikelbezogen
			$WARENGRUPPE = 300; // or find it from artikel  $ARTIKEL['WARENGRUPPE']
			$WARENGRUPPENNAME = 'Systemhaus'; // get it from warengruppe
			$ARTIKELTYP = $ARTIKEL['ARTIKELTYP'];
			$ARTIKEL_ID = $ARTIKEL['REC_ID'];
			$POSITION = $LAST_POSITION + 1 + $key; // key von der Schleife
			$MATCHCODE = addslashes($ARTIKEL['MATCHCODE']);
			$EXTRA_MENGENEINHEIT = (new DB("SELECT * FROM egw_timesheet_extra WHERE ts_id = '$timesheet[ts_id]' AND ts_extra_name = 'Mengeneinheit';"))->Fetch()['ts_extra_value'];
			if (!$EXTRA_MENGENEINHEIT || $EXTRA_MENGENEINHEIT == 'default') {
				$ME_EINHEIT = addslashes(Core::CatToMeEnheit($cat_id));
			} else {
				$ME_EINHEIT = addslashes($EXTRA_MENGENEINHEIT);
			}
			if ($ME_EINHEIT == 'Stück' || $ME_EINHEIT == 'km') {
				$MENGE = round($timesheet['ts_quantity'], 3);
			} elseif ($ME_EINHEIT == 'Stunden') {
				$MENGE = round((($timesheet['ts_duration'] ?? $timesheet['ts_quantity']) / 60), 3);
			} else {
				$MENGE = round(($timesheet['ts_quantity'] ?? $timesheet['ts_duration']), 3);
			}

			$PR_EINHEIT = $ARTIKEL['PR_EINHEIT'];
			$VPE = $ARTIKEL['VPE'];
			$EPREIS = round($timesheet['ts_unitprice'], 4);
			$EK_PREIS = floatval($ARTIKEL['EK_PREIS']);
			$CALC_FAKTOR = round(($EPREIS / $EK_PREIS), 5);
			$GPREIS = round(($MENGE * $EPREIS), 2);
			$NETTO_SUMME += $GPREIS;
			$E_RGEWINN = round(($EPREIS - $EK_PREIS), 4);
			$G_RGEWINN = round(($E_RGEWINN * $MENGE), 2);
			$GESAMT_ROHGEWINN += $G_RGEWINN;
			$STEUER_CODE = 1;
			$GEGENKTO = $ARTIKEL['ERLOES_KTO'];
			$BEZEICHNUNG = addslashes(date('d.m.Y', $timesheet['ts_start']).' '.($timesheet['ts_project'] ? $timesheet['ts_project']."\n".'#'.$timesheet['ts_id'].' '.$timesheet['ts_title'] : '#'.$timesheet['ts_id'].' '.$timesheet['ts_title'])); // + id

			$Query = "INSERT INTO JOURNALPOS (VRENUM, QUELLE, QUELLE_SUB, JOURNAL_ID, ADDR_ID, ARTNUM, WARENGRUPPE, WARENGRUPPENNAME, ARTIKELTYP, ARTIKEL_ID, POSITION, MATCHCODE, ME_EINHEIT, MENGE, PR_EINHEIT, VPE, EPREIS, EK_PREIS, E_RGEWINN, CALC_FAKTOR, GPREIS, G_RGEWINN, STEUER_CODE, GEGENKTO, BEZEICHNUNG) VALUES ('$VRENUM', '$QUELLE', '$QUELLE_SUB', '$JOURNAL_ID', '$ADDR_ID', '$ARTNUM', '$WARENGRUPPE', '$WARENGRUPPENNAME', '$ARTIKELTYP', '$ARTIKEL_ID', '$POSITION', '$MATCHCODE', '$ME_EINHEIT', '$MENGE', '$PR_EINHEIT', '$VPE', '$EPREIS', '$EK_PREIS', '$E_RGEWINN', '$CALC_FAKTOR', '$GPREIS', '$G_RGEWINN', '$STEUER_CODE', '$GEGENKTO', '$BEZEICHNUNG');";

			$resultID = Request::Run($Query, true);

			if (!$resultID) {
				throw new \Exception('The Bill Position is not saved', 1);
			}

			// if ($ME_EINHEIT == "Stunden") {
			// 	break;
			// }
		}
		if (is_array($POSITIONS_OF_BILL) && !empty($POSITIONS_OF_BILL)) {
			foreach ($POSITIONS_OF_BILL as $key => $BILL_POS) {
				$NETTO_SUMME += round(($BILL_POS['MENGE'] * $BILL_POS['EPREIS']), 2);
			}
		}
		$BRUTTO_SUMME = round($NETTO_SUMME * 1.19, 2);
		$M_SUMME = round($BRUTTO_SUMME - $NETTO_SUMME, 2);
		$UPDATE_QUERY = "UPDATE JOURNAL SET BSUMME = '$BRUTTO_SUMME', BSUMME_1 = '$BRUTTO_SUMME', NSUMME = '$NETTO_SUMME', NSUMME_1 = '$NETTO_SUMME', MSUMME = '$M_SUMME', MSUMME_1 = '$M_SUMME', ROHGEWINN = '$GESAMT_ROHGEWINN', WARE = '$NETTO_SUMME' WHERE REC_ID = '$JOURNAL_ID'";
		$updateJournal = Request::Run($UPDATE_QUERY, true);
		//Dump($UPDATE_QUERY);
		return $timesheets;
	}

	public function getLastPosition($JOURNAL_ID)
	{
		$lastPosition = Request::Run("
			SELECT * FROM  JOURNALPOS 
			WHERE JOURNAL_ID = '$JOURNAL_ID' 
			ORDER BY POSITION DESC
		");

		return is_array($lastPosition) ? ($lastPosition[0]['POSITION'] ?? 0) : 0;
	}

	public static function exists($contact_id, $return = false)
	{
		$KUNNUM = Core::KUNNUM($contact_id);
		$out = Request::Run("
			SELECT * FROM JOURNAL 
			WHERE (QUELLE = '13' AND QUELLE_SUB = '0') 
				AND KUN_NUM = '$KUNNUM' 
				ORDER BY REC_ID DESC

		");

		if ($return) {
			return $out;
		}

		if (!empty($out)) {
			return true;
		}

		return false;
	}
}

