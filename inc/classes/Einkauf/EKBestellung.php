<?php

namespace CAO\Einkauf;

use AgroEgw\DB;
use CAO\Core;
use CAO\Core\Adressen;
use CAO\Core\Collections\EKBestellCollection;
use CAO\Core\Collections\EKBestellPosCollection;
use CAO\Core\Mitarbeiter;
use CAO\EventSource;
use CAO\Request;

class EKBestellung extends EinkaufTrait
{
	public static function Create($data, $filename)
	{
		EventSource::setHeaders();
		$MITARBEITER = Mitarbeiter::Find(MA_ID);

		$progress_count = count($data);
		foreach ($data as $key => $value) {
			$progress_count += count($value);
		}
		$PROGRESS = 0;

		foreach ($data as $BELEGNUMMER => $POSITIONS) {
			$EKBESTELL = new EKBestellCollection();

			$KUNNUM = array_values($POSITIONS)[0]['KDNR_VOM_LIEFERANTEN'];
			$BELEGDATUM = date('Y-m-d', strtotime(array_values($POSITIONS)[0]['BELEGDATUM'])); // review
			$TERMIN = $BELEGDATUM;

			$EKBESTELL->add('BELEGDATUM', date('Y-m-d'));
			// $EKBESTELL->add("TERMIN", $TERMIN);
			$EKBESTELL->setAddress(Adressen::Find($KUNNUM));

			$EKBESTELL->add('MWST_1', 19.00);
			$EKBESTELL->add('MWST_2', 7.00);
			$EKBESTELL->add('MWST_3', 10.70);
			$EKBESTELL->add('KM_STAND', -1);

			$EKBESTELL->add('QUELLE', 15);
			$EKBESTELL->add('QUELLE_SUB', 0);

			$EKBESTELL->add('RDATUM', date('Y-m-d H:i:s')); // Erstellungsdatum
			$EKBESTELL->add('STADIUM', 0);
			$EKBESTELL->add('WAEHRUNG', '€');

			$ERSTELLT = date('Y-m-d H:i:s');
			$EKBESTELL->add('ERSTELLT', $ERSTELLT);
			$EKBESTELL->add('ERST_NAME', $MITARBEITER->getFullname());

			$EKBESTELL->add('GEAEND', $ERSTELLT);
			$EKBESTELL->add('GEAEND_NAME', $MITARBEITER->getFullname());

			$EKBESTELL->add('ORGNUM', $BELEGNUMMER); // review
			$EKBESTELL->add('FIRMA_ID', 1);

			$ZAHLART = $EKBESTELL->get('KUN_ZAHLART');
			$storageKey = 'ZAHLUNGSART_'.$ZAHLART;
			$sql = "SELECT * FROM ZAHLUNGSARTEN WHERE REC_ID = '$ZAHLART'";
			$ZAHLUNGSART = Core::Temp($storageKey)
						?? Core::Temp($storageKey, Request::Run($sql)[0]);

			$LIEFART = $EKBESTELL->get('KUN_LIEFART');
			$storageKey = 'LIEFERART_'.$LIEFART;
			$sql = "SELECT * FROM LIEFERARTEN WHERE REC_ID = '$LIEFART'";
			$LIEFERART = Core::Temp($storageKey)
						?? Core::Temp($storageKey, Request::Run($sql)[0]);

			$EKBESTELL->add('ZAHLART_NAME', $ZAHLUNGSART['NAME'])
					->add('ZAHLART_KURZ', $ZAHLUNGSART['TEXT_KURZ'])
					->add('ZAHLART_LANG', $ZAHLUNGSART['TEXT_LANG'])
					->add('LIEFART_NAME', $LIEFERART['NAME']);

			$Query = $EKBESTELL->BuildQuery();

			$EKBESTELL_ID = Request::Run($Query, true);

			EventSource::Send([
				'status'   => 'onprogress',
				'progress' => (($PROGRESS++ / $progress_count) * 100),
			]);

			$EKBESTELL->updateMainNum();

			if ($EKBESTELL_ID) {
				$EKBESTELL_ID = Request::Run('
                    SELECT * FROM EKBESTELL 
                    ORDER BY REC_ID DESC 
                    LIMIT 1
                ')[0]['REC_ID'];

				$NUM_POSITION = 0;
				$BRUTTO_SUMME = $M_SUMME = $NETTO_SUMME = $GESAMT_ROHGEWINN = 0;

				foreach ($POSITIONS as $key => $POSITION) {
					$NUM_POSITION++;
					$STADIUM = 0;

					$storageKey = 'ARTIKEL_ID_'.$POSITION['ARTIKELNUMMER'];
					$sql = "
                        SELECT * FROM egw_cao_meta 
                        WHERE meta_connection_id = {$POSITION[ARTIKELNUMMER]}
                    ";
					$ARTIKEL_ID = Core::Temp($storageKey)
							   ?? Core::Temp($storageKey, (new DB($sql))->Fetch()['meta_data']);

					if (empty($ARTIKEL_ID)) {
						EventSource::Send([
							'status'   => 'onprogress',
							'progress' => (($PROGRESS++ / $progress_count) * 100),
							'info'     => 'Leerlauf',
						]);
						// return $ARTIKEL_ID;
						continue;
					}

					$MENGE = $POSITION['MENGE'];

					$storageKey = 'ARTIKEL_'.$ARTIKEL_ID;
					$sql = "
                        SELECT A.*, B.ME 
                            FROM ARTIKEL AS A LEFT JOIN ARTIKEL_LOG as B 
                            ON A.REC_ID = B.ARTIKEL_ID 
                        WHERE A.REC_ID = '$ARTIKEL_ID' 
                        GROUP BY REC_ID
                    ";
					$ARTIKEL = Core::Temp($storageKey)
							?? Core::Temp($storageKey, Request::Run($sql)[0]);

					if (empty($ARTIKEL)) {
						EventSource::Send([
							'status'   => 'onprogress',
							'progress' => (($PROGRESS++ / $progress_count) * 100),
							'info'     => 'Leerlauf',
						]);
						// return $ARTIKEL;
						continue;
					}

					$EKBESTELLPOS = new EKBestellPosCollection();
					$EKBESTELLPOS->add('POSITION', $NUM_POSITION);
					$EKBESTELLPOS->add('VRENUM', $EKBESTELL->get('VRENUM'));
					$EKBESTELLPOS->add('QUELLE', $EKBESTELL->get('QUELLE'));
					$EKBESTELLPOS->add('QUELLE_SUB', $EKBESTELL->get('QUELLE_SUB'));
					$EKBESTELLPOS->add('EKBESTELL_ID', $EKBESTELL_ID);
					$EKBESTELLPOS->add('ADDR_ID', $EKBESTELL->get('ADDR_ID'));
					$EKBESTELLPOS->add('ARTNUM', $ARTIKEL['ARTNUM']);
					$EKBESTELLPOS->add('WARENGRUPPE', 1);
					$EKBESTELLPOS->add('WARENGRUPPENNAME', 'Rohstoff');
					$EKBESTELLPOS->add('ARTIKELTYP', $ARTIKEL['ARTIKELTYP']);
					$EKBESTELLPOS->add('ARTIKEL_ID', $ARTIKEL['REC_ID']);
					$EKBESTELLPOS->add('MATCHCODE', addslashes($ARTIKEL['MATCHCODE']));

					$EKBESTELLPOS->add('ME_EINHEIT', $ARTIKEL['ME']);
					$EKBESTELLPOS->add('PR_EINHEIT', $ARTIKEL['PR_EINHEIT']);
					$EKBESTELLPOS->add('MENGE', $MENGE);
					$EKBESTELLPOS->add('VPE', $ARTIKEL['VPE']);

					$EPREIS = round($POSITION['STUECKPREIS'], 4);
					$EK_PREIS = floatval($ARTIKEL['EK_PREIS']);
					$CALC_FAKTOR = round(($EPREIS / $EK_PREIS), 5);
					$GPREIS = round(($MENGE * $EPREIS), 2);
					$BRUTTO_SUMME += round($GPREIS * ($POSITION['MWSTCODE'] / 100 + 1), 2);
					$NETTO_SUMME += $GPREIS;
					$E_RGEWINN = round(($EPREIS - $EK_PREIS), 4);
					$G_RGEWINN = round(($E_RGEWINN * $MENGE), 2);
					$GESAMT_ROHGEWINN += $G_RGEWINN;

					$EKBESTELLPOS->add('EPREIS', $EPREIS);
					$EKBESTELLPOS->add('EK_PREIS', $EK_PREIS);
					$EKBESTELLPOS->add('CALC_FAKTOR', $CALC_FAKTOR);
					$EKBESTELLPOS->add('GPREIS', $GPREIS);
					$EKBESTELLPOS->add('E_RGEWINN', $E_RGEWINN);
					$EKBESTELLPOS->add('G_RGEWINN', $G_RGEWINN);

					$storageKey = 'STEUER_'.$POSITION['MWSTCODE'];
					$sql = "
                        SELECT * FROM REGISTRY 
                        WHERE MAINKEY LIKE '%MAIN%MWST' 
                            AND VAL_DOUBLE = '$POSITION[MWSTCODE]';
                    ";
					$STEUER = Core::Temp($storageKey)
							?? Core::Temp($storageKey, Request::Run($sql)[0]);

					$EKBESTELLPOS->add('STEUER_CODE', $STEUER['NAME']);

					// Brutto-Preis im Netto-Preis umwandeln
					// Notiz: das ist nicht mehr BRUTTO
					// $GPREIS = round($GPREIS / floatval("1.".$POSITION['MWSTCODE']), 2);

					$EKBESTELLPOS->add('GEGENKTO', $ARTIKEL['ERLOES_KTO']);
					$EKBESTELLPOS->add('BEZEICHNUNG', $ARTIKEL['KURZNAME']);

					$Query = $EKBESTELLPOS->BuildQuery();

					$resultID = Request::Run($Query, true);

					EventSource::Send([
						'status'   => 'onprogress',
						'progress' => (($PROGRESS++ / $progress_count) * 100),
					]);

					if (!$resultID) {
						EventSource::Send([
							'status' => 'error',
							'query'  => 'The EKBESTELL Position is not saved',
						]);
						exit;
					}
				}

				$M_SUMME = round($BRUTTO_SUMME - $NETTO_SUMME, 2);
				$UPDATE_QUERY = "
                    UPDATE EKBESTELL 
                    SET BSUMME = '$BRUTTO_SUMME', BSUMME_1 = '$BRUTTO_SUMME', NSUMME = '$NETTO_SUMME', NSUMME_1 = '$NETTO_SUMME', MSUMME = '$M_SUMME', MSUMME_1 = '$M_SUMME'
                    WHERE REC_ID = '$EKBESTELL_ID'
                ";
				$updateEKBESTELL = Request::Run($UPDATE_QUERY, true);

				if (!$updateEKBESTELL) {
					EventSource::Send([
						'status' => 'error',
						'query'  => $UPDATE_QUERY,
					]);
					exit;
				}
			} else {
				EventSource::Send([
					'status' => 'error',
					'query'  => $UPDATE_QUERY,
				]);
				exit;
			}
		}

		if (!DEBUG_MODE) {
			try {
				(new DB("
                    INSERT INTO egw_cao_meta (meta_name, meta_connection_id, meta_data) 
                    VALUES ('file_already_imported', 0, '".htmlspecialchars($filename)."');
                "));
			} catch (Exception $e) {
				throw new Exception('Error Processing Request: ', $e->getMessage(), 1);
			}
		}

		EventSource::Send([
			'status'     => 'done',
			'settings'   => $settings,
			// 'query'      => $UPDATE_QUERY,
			'timesheets' => $Inserted_Timesheets,
			'DEBUG_MODE' => DEBUG_MODE,
		]);
	}
}
