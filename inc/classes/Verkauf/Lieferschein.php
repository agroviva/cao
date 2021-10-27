<?php

namespace CAO\Verkauf;

use AgroEgw\DB;
use CAO\Core;
use CAO\Core\Collections\AdressenCollection;
use CAO\Core\Collections\ArtikelCollection;
use CAO\Core\Collections\LieferscheinCollection;
use CAO\Core\Collections\LieferscheinPosCollection;
use CAO\Core\Csv;
use CAO\Core\Filesystem;
use CAO\Core\Mitarbeiter;
use CAO\Einkauf\EinkaufTrait;
use CAO\EventSource;
use CAO\Request;

class Lieferschein extends EinkaufTrait
{
    protected static $config = [];

    public function __construct()
    {
        parent::__construct();
        if (DEBUG_MODE) {
            $content = Csv::parseFile('/apps/cao/inactive/Abfüllung_Heumilch 2019-03-18_20190315_140948.csv');
            $output = $content->primaryKey('BESTELLNUMMERKUNDE')->dump();

            // foreach (self::$config["artikel"] as $artikel => $subartikel) {
            //     $collection = ArtikelCollection::Find($artikel, "ARTNUM", "LIKE");
            //     Dump($collection->get("LANGNAME"));
            //     foreach ($subartikel as $artikel) {
            //         $collection = ArtikelCollection::Find($artikel, "ARTNUM", "LIKE");
            //         Dump($collection->get("LANGNAME"));
            //     }
            //     echo '<br>';
            // }
        }
    }

    public static function init_static()
    {
        self::$config['artikel'] = [
            // EDEKA
            '100665' => [ //Milch 1.8%
                '100671',
            ],
            '100664' => [ //Milch 3.8%
                '100671',
            ],
            '100666' => [ //Joghurt
                '100672',
            ],
            '100709' => [ //Quark
                '100720',
            ],

            // Feine Linie
            '100668' => [ //Milch
                '100671',
            ],
            '100667' => [ //Joghurt
                '100672',
            ],
            '100728' => [ //Quark
                '100720',
            ],

            // Weiling
            '100670' => [ //Milch
                '100671',
            ],
        ];
    }

    public static function filterData($data)
    {
        $artikelConfig = self::$config['artikel'];

        foreach ($data as $key => $POSITIONS) {
            foreach ($POSITIONS as $posKey => $Position) {
                $artikelNR = $Position['ARTIKELNUMMER'];
                if (!empty($artikelConfig[$artikelNR])) {
                    foreach ($artikelConfig[$artikelNR] as $artikel) {
                        $newPosition = $Position;
                        $newPosition['ARTIKELNUMMER'] = $artikel;
                        $data[$key][] = $newPosition;
                    }
                }
            }
        }

        return $data;
    }

    public static function Create($data, $filename)
    {
        $data = self::filterData($data);

        EventSource::setHeaders();
        $MITARBEITER = Mitarbeiter::Find(MA_ID);

        $progress_count = count($data);
        foreach ($data as $key => $value) {
            $progress_count += count($value);
        }
        $PROGRESS = 0;

        foreach ($data as $BELEGNUMMER => $POSITIONS) {
            $LIEFERSCHEIN = new LieferscheinCollection();

            $FirstPosition = array_values($POSITIONS)[0];

            $KUNNUM = $FirstPosition['KUNDENNUMMER'];
            $BELEGDATUM = date('Y-m-d', strtotime($FirstPosition['LIEFERDATUM (TERMIN)'])); // review
            $TERMIN = $BELEGDATUM;

            $ADRESSE = AdressenCollection::Find("%{$KUNNUM}%", '', 'LIKE');
            $ADRESSE_ID = $ADRESSE->get('REC_ID');

            $LIEFERSCHEIN->add('EDI_FLAG', 'Y');
            $LIEFERSCHEIN->add('TERMIN', $TERMIN);
            $LIEFERSCHEIN->setAddress($ADRESSE);

            if ($FirstPosition['LAGERORT']) {
                $CloneAdresse = clone $ADRESSE;
                $ADRESSEN_LIEF = $CloneAdresse->ADRESSEN_LIEF->array();
                $key = Core::Search($ADRESSEN_LIEF, 'ORT', $FirstPosition['LAGERORT']);

                EventSource::Send([
                    'status'   => 'onprogress',
                    'key'      => $ADRESSEN_LIEF[$key]['REC_ID'] ?? 'Not Found',
                ]);

                if (isset($ADRESSEN_LIEF[$key]['REC_ID'])) {
                    $LIEFERSCHEIN->add('LIEF_ADDR_ID', $ADRESSEN_LIEF[$key]['REC_ID']);
                }
            }

            $LIEFERSCHEIN->add('MWST_1', 19.00);
            $LIEFERSCHEIN->add('MWST_2', 7.00);
            $LIEFERSCHEIN->add('MWST_3', 10.70);
            $LIEFERSCHEIN->add('KM_STAND', -1);

            $LIEFERSCHEIN->add('QUELLE', 15);
            $LIEFERSCHEIN->add('QUELLE_SUB', 0);

            $LIEFERSCHEIN->add('RDATUM', date('Y-m-d H:i:s')); // Erstellungsdatum
            $LIEFERSCHEIN->add('STADIUM', 0);
            $LIEFERSCHEIN->add('WAEHRUNG', '€');

            $ERSTELLT = date('Y-m-d H:i:s');
            $LIEFERSCHEIN->add('LDATUM', $ERSTELLT);
            $LIEFERSCHEIN->add('ERSTELLT', $ERSTELLT);
            $LIEFERSCHEIN->add('ERST_NAME', $MITARBEITER->getFullname());

            $LIEFERSCHEIN->add('GEAEND', $ERSTELLT);
            $LIEFERSCHEIN->add('GEAEND_NAME', $MITARBEITER->getFullname());

            $LIEFERSCHEIN->add('ORGNUM', $BELEGNUMMER); // review
            $LIEFERSCHEIN->add('FIRMA_ID', 1);

            $ZAHLART = $LIEFERSCHEIN->get('KUN_ZAHLART');
            $storageKey = 'ZAHLUNGSART_'.$ZAHLART;
            $sql = "SELECT * FROM ZAHLUNGSARTEN WHERE REC_ID = '$ZAHLART'";
            $ZAHLUNGSART = Core::Temp($storageKey)
                        ?? Core::Temp($storageKey, Request::Run($sql)[0]);

            $LIEFART = $LIEFERSCHEIN->get('KUN_LIEFART');
            $storageKey = 'LIEFERART_'.$LIEFART;
            $sql = "SELECT * FROM LIEFERARTEN WHERE REC_ID = '$LIEFART'";
            $LIEFERART = Core::Temp($storageKey)
                        ?? Core::Temp($storageKey, Request::Run($sql)[0]);

            $LIEFERSCHEIN->add('ZAHLART_NAME', $ZAHLUNGSART['NAME'])
                    ->add('ZAHLART_KURZ', $ZAHLUNGSART['TEXT_KURZ'])
                    ->add('ZAHLART_LANG', $ZAHLUNGSART['TEXT_LANG'])
                    ->add('LIEFART_NAME', $LIEFERART['NAME']);

            $Query = $LIEFERSCHEIN->BuildQuery();

            $LIEFERSCHEIN_ID = Request::Run($Query, true);

            EventSource::Send([
                'status'   => 'onprogress',
                'progress' => (($PROGRESS++ / $progress_count) * 100),
            ]);

            $LIEFERSCHEIN->updateMainNum();

            if ($LIEFERSCHEIN_ID) {
                $LIEFERSCHEIN_ID = Request::Run('
                    SELECT * FROM LIEFERSCHEIN 
                    ORDER BY REC_ID DESC 
                    LIMIT 1
                ')[0]['REC_ID'];

                $NUM_POSITION = 0;
                $BRUTTO_SUMME = $M_SUMME = $NETTO_SUMME = $GESAMT_ROHGEWINN = 0;

                foreach ($POSITIONS as $key => $POSITION) {
                    $NUM_POSITION++;
                    $STADIUM = 0;

                    $MENGE = $POSITION['MENGE'];

                    $storageKey = 'ARTIKEL_NR_'.$POSITION['ARTIKELNUMMER'];
                    $sql = "
                        SELECT A.*, B.ME 
                            FROM ARTIKEL AS A LEFT JOIN ARTIKEL_LOG as B 
                            ON A.REC_ID = B.ARTIKEL_ID 
                        WHERE A.ARTNUM = '{$POSITION[ARTIKELNUMMER]}' 
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

                    $storageKey = 'ARTIKEL_PREIS_'.$POSITION['ARTIKELNUMMER'].'_'.$ADRESSE_ID;
                    $sql = "
                        SELECT B.PREIS AS PREIS
                            FROM ARTIKEL AS A LEFT JOIN ARTIKEL_PREIS as B 
                            ON A.REC_ID = B.ARTIKEL_ID 
                        WHERE A.ARTNUM = '{$POSITION[ARTIKELNUMMER]}' 
                            AND B.ADRESS_ID = $ADRESSE_ID
                        GROUP BY REC_ID
                    ";
                    $ARTIKEL_PREIS = Core::Temp($storageKey)
                            ?? Core::Temp($storageKey, Request::Run($sql)[0]);
                    $ITEM_PREIS = $ARTIKEL['VK1'];
                    if (!empty($ARTIKEL_PREIS['PREIS'])) {
                        $ITEM_PREIS = $ARTIKEL_PREIS['PREIS'];
                    }

                    $LIEFERSCHEINPOS = new LieferscheinPosCollection();
                    $LIEFERSCHEINPOS->add('POSITION', $NUM_POSITION);
                    $LIEFERSCHEINPOS->add('LVPOS', $POSITION['MHD' ?? '']);
                    $LIEFERSCHEINPOS->add('VRENUM', $LIEFERSCHEIN->get('VRENUM'));
                    $LIEFERSCHEINPOS->add('VLSNUM', $LIEFERSCHEIN->get('VLSNUM'));
                    $LIEFERSCHEINPOS->add('QUELLE', $LIEFERSCHEIN->get('QUELLE'));
                    $LIEFERSCHEINPOS->add('QUELLE_SUB', $LIEFERSCHEIN->get('QUELLE_SUB'));
                    $LIEFERSCHEINPOS->add('LIEFERSCHEIN_ID', $LIEFERSCHEIN_ID);
                    $LIEFERSCHEINPOS->add('ADDR_ID', $LIEFERSCHEIN->get('ADDR_ID'));
                    $LIEFERSCHEINPOS->add('ARTNUM', $ARTIKEL['ARTNUM']);
                    $LIEFERSCHEINPOS->add('WARENGRUPPE', 1);
                    $LIEFERSCHEINPOS->add('WARENGRUPPENNAME', 'Rohstoff');
                    $LIEFERSCHEINPOS->add('ARTIKELTYP', $ARTIKEL['ARTIKELTYP']);
                    $LIEFERSCHEINPOS->add('ARTIKEL_ID', $ARTIKEL['REC_ID']);
                    $LIEFERSCHEINPOS->add('MATCHCODE', addslashes($ARTIKEL['MATCHCODE']));

                    $LIEFERSCHEINPOS->add('ME_EINHEIT', $ARTIKEL['ME']);
                    $LIEFERSCHEINPOS->add('PR_EINHEIT', $ARTIKEL['PR_EINHEIT']);
                    $LIEFERSCHEINPOS->add('MENGE', $MENGE);
                    $LIEFERSCHEINPOS->add('VPE', $ARTIKEL['VPE']);

                    $storageKey = 'STEUERCODE_'.$ARTIKEL['STEUER_CODE'];
                    $sql = "
                        SELECT * FROM REGISTRY 
                        WHERE MAINKEY LIKE '%MAIN%MWST' 
                            AND NAME = '$ARTIKEL[STEUER_CODE]';
                    ";
                    $STEUER = Core::Temp($storageKey)
                            ?? Core::Temp($storageKey, Request::Run($sql)[0]);

                    $EPREIS = round($ITEM_PREIS, 4);
                    $EK_PREIS = $EPREIS;
                    $CALC_FAKTOR = round(($EPREIS / $EK_PREIS), 5);
                    $GPREIS = round(($MENGE * $EPREIS), 2);
                    $BRUTTO_SUMME += round($GPREIS * ($STEUER['VAL_DOUBLE'] / 100 + 1), 2);
                    $NETTO_SUMME += $GPREIS;
                    $E_RGEWINN = round(($EPREIS - $EK_PREIS), 4);
                    $G_RGEWINN = round(($E_RGEWINN * $MENGE), 2);
                    $GESAMT_ROHGEWINN += $G_RGEWINN;

                    $LIEFERSCHEINPOS->add('EPREIS', $EPREIS);
                    $LIEFERSCHEINPOS->add('EK_PREIS', $EK_PREIS);
                    $LIEFERSCHEINPOS->add('CALC_FAKTOR', $CALC_FAKTOR);
                    $LIEFERSCHEINPOS->add('GPREIS', $GPREIS);
                    $LIEFERSCHEINPOS->add('E_RGEWINN', $E_RGEWINN);
                    $LIEFERSCHEINPOS->add('G_RGEWINN', $G_RGEWINN);

                    $LIEFERSCHEINPOS->add('STEUER_CODE', $ARTIKEL['STEUER_CODE']);

                    // Brutto-Preis im Netto-Preis umwandeln
                    // Notiz: das ist nicht mehr BRUTTO
                    // $GPREIS = round($GPREIS / floatval("1.".$STEUER['VAL_DOUBLE']), 2);

                    $LIEFERSCHEINPOS->add('GEGENKTO', $ARTIKEL['ERLOES_KTO']);
                    $LIEFERSCHEINPOS->add('BEZEICHNUNG', $ARTIKEL['KURZNAME']);

                    $Query = $LIEFERSCHEINPOS->BuildQuery();

                    $resultID = Request::Run($Query, true);

                    try {
                        self::InsertChildrenOf($LIEFERSCHEINPOS);
                    } catch (\Exception $e) {
                        EventSource::Send([
                            'status'        => 'onprogress',
                            'progress'      => (($PROGRESS / $progress_count) * 100),
                            'error_message' => $e->getMessage(),
                        ]);
                    }

                    EventSource::Send([
                        'status'   => 'onprogress',
                        'progress' => (($PROGRESS++ / $progress_count) * 100),
                    ]);

                    if (!$resultID) {
                        EventSource::Send([
                            'status' => 'error',
                            'query'  => 'The LIEFERSCHEIN Position is not saved',
                        ]);
                        exit;
                    }
                }

                $M_SUMME = round($BRUTTO_SUMME - $NETTO_SUMME, 2);
                $UPDATE_QUERY = "
                    UPDATE LIEFERSCHEIN 
                    SET BSUMME = '$BRUTTO_SUMME', BSUMME_1 = '$BRUTTO_SUMME', NSUMME = '$NETTO_SUMME', NSUMME_1 = '$NETTO_SUMME', MSUMME = '$M_SUMME', MSUMME_1 = '$M_SUMME', ROHGEWINN = '$GESAMT_ROHGEWINN', WARE = '$NETTO_SUMME', WERT_NETTO = '$NETTO_SUMME'
                    WHERE REC_ID = '$LIEFERSCHEIN_ID'
                ";
                $updateLIEFERSCHEIN = Request::Run($UPDATE_QUERY, true);

                if (!$updateLIEFERSCHEIN) {
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
                // (new DB("
                //     INSERT INTO egw_cao_meta (meta_name, meta_connection_id, meta_data)
                //     VALUES ('file_already_imported', 0, '".htmlspecialchars($filename)."');
                // "));
                Filesystem::finish($filename);
            } catch (Exception $e) {
                throw new Exception('Error Processing Request: ', $e->getMessage(), 1);
            }
        }

        EventSource::Send([
            'status'     => 'done',
            'settings'   => $settings,
            'timesheets' => $Inserted_Timesheets,
            'DEBUG_MODE' => DEBUG_MODE,
        ]);
    }

    public static function InsertChildrenOf(LieferscheinPosCollection $LIEFERSCHEINPOS)
    {
        $LIEFERSCHEIN_ID = $LIEFERSCHEINPOS->get('LIEFERSCHEIN_ID');

        $sql = "SELECT * FROM LIEFERSCHEIN_POS WHERE LIEFERSCHEIN_ID = $LIEFERSCHEIN_ID ORDER BY REC_ID DESC";

        $PARENT_LIEFERSCHEIN = Request::Run($sql)[0];

        if (empty($PARENT_LIEFERSCHEIN)) {
            return;
        }
        $TOP_POS_ID = $PARENT_LIEFERSCHEIN['REC_ID'];

        $ARTIKEL_ID = $LIEFERSCHEINPOS->get('ARTIKEL_ID');
        $storageKey = 'ARTIKEL_STÜCKLISTE_'.$ARTIKEL_ID;
        $sql = "SELECT * FROM  `ARTIKEL_STUECKLIST` WHERE REC_ID = $ARTIKEL_ID AND ARTIKEL_ART = 'STL'";
        $ARTIKEL_STUECKLISTE = Core::Temp($storageKey)
                ?? Core::Temp($storageKey, Request::Run($sql));

        if (empty($ARTIKEL_STUECKLISTE)) {
            return;
        }

        foreach ($ARTIKEL_STUECKLISTE as $ITEM) {
            $ARTIKEL_ID = $ITEM['ART_ID'];
            $ARTIKEL = ArtikelCollection::Find($ARTIKEL_ID)->array();

            if (!empty($ARTIKEL)) {
                $CHILD_LIFERSCHEINPOS = clone $LIEFERSCHEINPOS;
                $CHILD_LIFERSCHEINPOS
                          ->add('TOP_POS_ID', $TOP_POS_ID)
                          ->add('POSITION', 0)
                          ->add('ARTNUM', $ARTIKEL['ARTNUM'])
                          ->add('ARTIKELTYP', $ARTIKEL['ARTIKELTYP'])
                          ->add('ARTIKEL_ID', $ARTIKEL['REC_ID'])
                          ->add('MATCHCODE', addslashes($ARTIKEL['MATCHCODE']))
                          ->add('BEZEICHNUNG', $ARTIKEL['KURZNAME'])
                          ->add('ME_EINHEIT', $ARTIKEL['ME'])
                          ->add('PR_EINHEIT', $ARTIKEL['PR_EINHEIT'])
                          ->add('MENGE', ((float) $ITEM['MENGE'] * (float) $LIEFERSCHEINPOS->get('MENGE')))
                          ->add('VPE', $ARTIKEL['VPE']);

                EventSource::Send([
                    'status'   => 'onprogress',
                    'child'    => $CHILD_LIFERSCHEINPOS->array(),
                ]);

                $Query = $CHILD_LIFERSCHEINPOS->BuildQuery();
                Request::Run($Query, true);
            }
        }
    }
}

Lieferschein::init_static();
