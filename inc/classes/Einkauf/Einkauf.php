<?php

namespace CAO\Einkauf;

use AgroEgw\DB;
use CAO\Core;
use CAO\Core\Adressen;
use CAO\Core\Collections\JournalCollection;
use CAO\Core\Collections\JournalPosCollection;
use CAO\Core\Filesystem;
use CAO\Core\Mitarbeiter;
use CAO\EventSource;
use CAO\Request;

class Einkauf extends EinkaufTrait
{
    public function __construct()
    {
        parent::__construct();
    }

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
            $JOURNAL = new JournalCollection();

            $KUNNUM = array_values($POSITIONS)[0]['KDNR_VOM_LIEFERANTEN'];
            $BELEGDATUM = date('Y-m-d', strtotime(array_values($POSITIONS)[0]['BELEGDATUM'])); // review
            $TERMIN = $BELEGDATUM;

            $JOURNAL->add('TERMIN', $TERMIN);
            $JOURNAL->setAddress(Adressen::Find($KUNNUM));

            $JOURNAL->add('MWST_1', 19.00);
            $JOURNAL->add('MWST_2', 7.00);
            $JOURNAL->add('MWST_3', 10.70);
            $JOURNAL->add('KM_STAND', -1);

            $JOURNAL->add('QUELLE', 15);
            $JOURNAL->add('QUELLE_SUB', 0);

            $JOURNAL->add('RDATUM', $BELEGDATUM);
            $JOURNAL->add('STADIUM', 0);
            $JOURNAL->add('WAEHRUNG', '€');

            $ERSTELLT = date('Y-m-d H:i:s');
            $JOURNAL->add('ERSTELLT', $BELEGDATUM);
            $JOURNAL->add('ERST_NAME', $MITARBEITER->getFullname());

            $JOURNAL->add('GEAEND', $ERSTELLT);
            $JOURNAL->add('GEAEND_NAME', $MITARBEITER->getFullname());

            $JOURNAL->add('ORGNUM', $BELEGNUMMER);
            $JOURNAL->add('PROJEKT', $BELEGNUMMER);

            $JOURNAL->add('FIRMA_ID', 1);

            $ZAHLART = $JOURNAL->get('KUN_ZAHLART');
            $storageKey = 'ZAHLUNGSART_'.$ZAHLART;
            $sql = "SELECT * FROM ZAHLUNGSARTEN WHERE REC_ID = '$ZAHLART'";
            $ZAHLUNGSART = Core::Temp($storageKey)
                        ?? Core::Temp($storageKey, Request::Run($sql)[0]);

            $LIEFART = $JOURNAL->get('KUN_LIEFART');
            $storageKey = 'LIEFERART_'.$LIEFART;
            $sql = "SELECT * FROM LIEFERARTEN WHERE REC_ID = '$LIEFART'";
            $LIEFERART = Core::Temp($storageKey)
                        ?? Core::Temp($storageKey, Request::Run($sql)[0]);

            $JOURNAL->add('ZAHLART_NAME', $ZAHLUNGSART['NAME'])
                    ->add('ZAHLART_KURZ', $ZAHLUNGSART['TEXT_KURZ'])
                    ->add('ZAHLART_LANG', $ZAHLUNGSART['TEXT_LANG'])
                    ->add('LIEFART_NAME', $LIEFERART['NAME']);

            $Query = $JOURNAL->BuildQuery();

            $JOURNAL_ID = Request::Run($Query, true);

            EventSource::Send([
                'status'   => 'onprogress',
                'progress' => (($PROGRESS++ / $progress_count) * 100),
            ]);

            $JOURNAL->updateMainNum();

            if ($JOURNAL_ID) {
                $JOURNAL_ID = Request::Run('
					SELECT * FROM JOURNAL 
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

                    $JOURNALPOS = new JournalPosCollection();
                    $JOURNALPOS->add('POSITION', $NUM_POSITION);
                    $JOURNALPOS->add('VRENUM', $JOURNAL->get('VRENUM'));
                    $JOURNALPOS->add('QUELLE', $JOURNAL->get('QUELLE'));
                    $JOURNALPOS->add('QUELLE_SUB', $JOURNAL->get('QUELLE_SUB'));
                    $JOURNALPOS->add('JOURNAL_ID', $JOURNAL_ID);
                    $JOURNALPOS->add('ADDR_ID', $JOURNAL->get('ADDR_ID'));
                    $JOURNALPOS->add('ARTNUM', $ARTIKEL['ARTNUM']);
                    $JOURNALPOS->add('WARENGRUPPE', 1);
                    $JOURNALPOS->add('WARENGRUPPENNAME', 'Rohstoff');
                    $JOURNALPOS->add('ARTIKELTYP', $ARTIKEL['ARTIKELTYP']);
                    $JOURNALPOS->add('ARTIKEL_ID', $ARTIKEL['REC_ID']);
                    $JOURNALPOS->add('MATCHCODE', addslashes($ARTIKEL['MATCHCODE']));

                    $JOURNALPOS->add('ME_EINHEIT', $ARTIKEL['ME']);
                    $JOURNALPOS->add('PR_EINHEIT', $ARTIKEL['PR_EINHEIT']);
                    $JOURNALPOS->add('MENGE', $MENGE);
                    $JOURNALPOS->add('VPE', $ARTIKEL['VPE']);

                    $EPREIS = round($POSITION['STUECKPREIS'], 4);
                    $EK_PREIS = floatval($ARTIKEL['EK_PREIS']);
                    $CALC_FAKTOR = round(($EPREIS / $EK_PREIS), 5);
                    $GPREIS = round(($MENGE * $EPREIS), 2);
                    $BRUTTO_SUMME += round($GPREIS * ($POSITION['MWSTCODE'] / 100 + 1), 2);
                    $NETTO_SUMME += $GPREIS;
                    $E_RGEWINN = round(($EPREIS - $EK_PREIS), 4);
                    $G_RGEWINN = round(($E_RGEWINN * $MENGE), 2);
                    $GESAMT_ROHGEWINN += $G_RGEWINN;

                    $JOURNALPOS->add('EPREIS', $EPREIS);
                    $JOURNALPOS->add('EK_PREIS', $EK_PREIS);
                    $JOURNALPOS->add('CALC_FAKTOR', $CALC_FAKTOR);
                    $JOURNALPOS->add('GPREIS', $GPREIS);
                    $JOURNALPOS->add('E_RGEWINN', $E_RGEWINN);
                    $JOURNALPOS->add('G_RGEWINN', $G_RGEWINN);

                    $storageKey = 'STEUER_'.$POSITION['MWSTCODE'];
                    $sql = "
                        SELECT * FROM REGISTRY 
                        WHERE MAINKEY LIKE '%MAIN%MWST' 
                            AND VAL_DOUBLE = '$POSITION[MWSTCODE]';
                    ";
                    $STEUER = Core::Temp($storageKey)
                            ?? Core::Temp($storageKey, Request::Run($sql)[0]);

                    $JOURNALPOS->add('STEUER_CODE', $STEUER['NAME']);

                    // Brutto-Preis im Netto-Preis umwandeln
                    // Notiz: das ist nicht mehr BRUTTO
                    // $GPREIS = round($GPREIS / floatval("1.".$POSITION['MWSTCODE']), 2);

                    $JOURNALPOS->add('GEGENKTO', $ARTIKEL['ERLOES_KTO']);
                    $JOURNALPOS->add('BEZEICHNUNG', $ARTIKEL['KURZNAME']);

                    $Query = $JOURNALPOS->BuildQuery();

                    $resultID = Request::Run($Query, true);

                    EventSource::Send([
                        'status'   => 'onprogress',
                        'progress' => (($PROGRESS++ / $progress_count) * 100),
                    ]);

                    if (!$resultID) {
                        EventSource::Send([
                            'status' => 'error',
                            'query'  => 'The JOURNAL Position is not saved',
                        ]);
                        exit;
                    }
                }

                $M_SUMME = round($BRUTTO_SUMME - $NETTO_SUMME, 2);
                $UPDATE_QUERY = "
					UPDATE JOURNAL 
					SET BSUMME = '$BRUTTO_SUMME', BSUMME_1 = '$BRUTTO_SUMME', NSUMME = '$NETTO_SUMME', NSUMME_1 = '$NETTO_SUMME', MSUMME = '$M_SUMME', MSUMME_1 = '$M_SUMME', ROHGEWINN = '$GESAMT_ROHGEWINN', WARE = '$NETTO_SUMME' 
					WHERE REC_ID = '$JOURNAL_ID'
				";
                $updateJOURNAL = Request::Run($UPDATE_QUERY, true);

                if (!$updateJOURNAL) {
                    EventSource::Send([
                        'status' => 'error',
                        'query'	 => $UPDATE_QUERY,
                    ]);
                    exit;
                }
            } else {
                EventSource::Send([
                    'status' => 'error',
                    'query'	 => $UPDATE_QUERY,
                ]);
                exit;
            }
        }

        if (!DEBUG_MODE) {
            try {
                Filesystem::markAsImported($filename);
            } catch (Exception $e) {
                throw new Exception('Error Processing Request: ', $e->getMessage(), 1);
            }
        }

        EventSource::Send([
            'status'     => 'done',
            'settings'   => $settings,
            'data'       => $data,
            'DEBUG_MODE' => DEBUG_MODE,
        ]);
    }
}
