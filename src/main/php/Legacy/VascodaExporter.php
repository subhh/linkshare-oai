<?php

// declare(strict_types=1);

/**
 * This file is part of Linkshare OAI Webservice.
 *
 * Linkshare OAI Webservice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Linkshare OAI Webservice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Linkshare OAI Webservice.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace SubHH\Linkshare\OAI\Legacy;

use SubHH\Linkshare\OAI\Serializer;

use PDO;

/**
 * Export to Vascoda XML.
 *
 * @author David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright Copyright (c) 2021 by Staats- und Universitätsbibliothek Hamburg
 */
class VascodaExporter implements Serializer
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var VascodaXmlGenerator
     */
    private $vascodaXmlGenerator;

    /**
     * @var array
     */
    private $vascodaXmlMap = [
        'africa_swd' => ['vocabScheme' => 'lss:AfricaSWD', 'vapNotation' => ''],
        'bbi' => ['vocabScheme' => 'lss:INFODATA', 'vapNotation' => ''],
        'bk' => ['vocabScheme' => 'vap:BK', 'vapNotation' => 'strukturmerkmal'],
        'ddc' => ['vocabScheme' => 'dcterms:DDC', 'vapNotation' => 'strukturmerkmal'],
        'fs_b2i' => ['vocabScheme' => 'lss:b2i', 'vapNotation' => 'strukturmerkmal'],
        'fs_branchen' => ['vocabScheme' => 'lss:Bra', 'vapNotation' => 'strukturmerkmal'],
        'fs_bwl' => ['vocabScheme' => 'lss:Biz', 'vapNotation' => 'strukturmerkmal'],
        'fs_chron' => ['vocabScheme' => 'lss:Chron', 'vapNotation' => 'strukturmerkmal'],
        'fs_edz' => ['vocabScheme' => 'lss:EDZ', 'vapNotation' => 'strukturmerkmal'],
        'fs_fid_romanistik' => ['vocabScheme' => 'lss:Romanistik', 'vapNotation' => 'strukturmerkmal'],
        'fs_frieden' => ['vocabScheme' => 'lss:Pea', 'vapNotation' => 'strukturmerkmal'],
        'fs_gok' => ['vocabScheme' => 'lss:GOK', 'vapNotation' => 'strukturmerkmal'],
        'fs_ilissa' => ['vocabScheme' => 'lss:ilissA', 'vapNotation' => 'strukturmerkmal'],
        'fs_musik' => ['vocabScheme' => 'lss:Music', 'vapNotation' => 'strukturmerkmal'],
        'fs_politik' => ['vocabScheme' => 'lss:Pol', 'vapNotation' => 'strukturmerkmal'],
        'fs_prop' => ['vocabScheme' => 'lss:Propylaeum', 'vapNotation' => 'strukturmerkmal'],
        'fs_recht' => ['vocabScheme' => 'lss:Law', 'vapNotation' => 'strukturmerkmal'],
        'fs_subhh_lb' => ['vocabScheme' => 'lss:SUBHH', 'vapNotation' => 'strukturmerkmal'],
        'fs_vifarom' => ['vocabScheme' => 'lss:Rom', 'vapNotation' => 'strukturmerkmal'],
        'fs_vwl' => ['vocabScheme' => 'lss:Eco', 'vapNotation' => 'strukturmerkmal'],
        'fs_zeitung' => ['vocabScheme' => 'lss:News', 'vapNotation' => 'strukturmerkmal'],
        'fs_zew' => ['vocabScheme' => 'lss:ZEW', 'vapNotation' => 'strukturmerkmal'],
        'gnd' => ['vocabScheme' => 'vap:GND', 'vapNotation' => 'strukturmerkmal'],
        'iblk' => ['vocabScheme' => 'lss:IBLK-T', 'vapNotation' => ''],
        'jel' => ['vocabScheme' => 'lss:JEL', 'vapNotation' => 'strukturmerkmal'],
        'mpifge' => ['vocabScheme' => 'lss:MPIfGE', 'vapNotation' => 'strukturmerkmal'],
        'plique' => ['vocabScheme' => 'lss:PliQue', 'vapNotation' => 'strukturmerkmal'],
        'rom' => ['vocabScheme' => 'lss:GuideRom', 'vapNotation' => 'strukturmerkmal'],
        'slav' => ['vocabScheme' => 'lss:Slavistik', 'vapNotation' => ''],
        'stw' => ['vocabScheme' => 'lss:STW', 'vapNotation' => 'strukturmerkmal'],
        'swd' => ['vocabScheme' => 'vap:SWD', 'vapNotation' => 'strukturmerkmal'],
        'tsw' => ['vocabScheme' => 'lss:TSW', 'vapNotation' => ''],
    ];

    public function __construct (PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->vascodaXmlGenerator = new VascodaXmlGenerator();
    }

    public function serialize (int $localIdentifier) : string
    {
        $this->exportQuelle($localIdentifier);
        $data = $this->vascodaXmlGenerator->getVascodaXml(); 
        return implode(PHP_EOL, $data);
    }

    //

    private function collectExportData( $quelle , $type ) {
        $dataList = array();
        if ( $quelle > 0 ) {
            if ( $type == 'definition' ) {
                $query = "SELECT quellendefinition.quellendefinition AS id , quellenmerkmal.bezeichnung AS quellenmerkmal , merkmalsnorm.bezeichnung AS merkmalsnorm , quellendefinition.wert , quellendefinition.sprache , quellendefinition.elterndefinition FROM quellendefinition LEFT JOIN quellenmerkmal USING ( quellenmerkmal ) LEFT JOIN merkmalsnorm USING ( merkmalsnorm ) WHERE ( quellendefinition.gueltig IS NOT NULL OR quellenmerkmal.pflichtfeld IS NOT NULL ) AND quellendefinition.geloescht IS NULL AND quellendefinition.quelle = ".$quelle." ORDER BY quellenmerkmal.quellenmerkmal DESC , quellendefinition.quellendefinition";
            } elseif ( $type == 'systematik' ) {
                $query = "SELECT IF( kd1.klassendefinition IS NULL , CONCAT( systematikklasse.systematik , '-' , systematikklasse.systematikklasse ) , kd1.klassendefinition ) AS id , systematik.bezeichnung AS systematik , systematikklasse.systematikklasse , systematikklasse.bezeichnung AS bezeichnung , systematikklasse.strukturmerkmal , IF( kd1.wert IS NULL , systematikklasse.bezeichnung  , kd1.wert ) AS text , kd2.wert AS ddc , kd3.wert AS ddc_22 , kd1.sprache FROM systematik , ( ( systematikklasse LEFT JOIN ( klassendefinition kd1 , klassenmerkmal km1 ) ON ( kd1.systematikklasse = systematikklasse.systematikklasse AND km1.klassenmerkmal = kd1.klassenmerkmal AND km1.bezeichnung = 'text' ) ) LEFT JOIN ( klassendefinition kd2 , klassenmerkmal km2 ) ON ( kd2.systematikklasse = systematikklasse.systematikklasse AND km2.klassenmerkmal = kd2.klassenmerkmal AND km2.bezeichnung = 'ddc' ) ) LEFT JOIN ( klassendefinition kd3 , klassenmerkmal km3 ) ON ( kd3.systematikklasse = systematikklasse.systematikklasse AND km3.klassenmerkmal = kd3.klassenmerkmal AND km3.bezeichnung = 'ddc_22' ) , quelle_systematikklasse WHERE systematikklasse.systematik = systematik.systematik AND quelle_systematikklasse.systematikklasse = systematikklasse.systematikklasse AND quelle_systematikklasse.quelle = ".$quelle." ORDER BY systematik.bezeichnung , systematikklasse.systematikklasse";
            }
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            if ($stmt !== false) {
                foreach ($stmt->fetchAll() as $row) {
                    $dataList[$row['id']] = $row;
                }
            }
            return $dataList;
        }
        return null;
    }

    /*
      METH private array collectAdminData( int $quelle )
    */
    private function collectAdminData( $quelle ) {
        if ( $quelle > 0 ) {
            $query = "SELECT DATE( quelle.erstellt ) AS erstellt , DATE( quelle.freigeschaltet ) AS freigeschaltet , IFNULL( MAX( quellendefinition.erstellt ) , quelle.erstellt ) AS geaendert , quelle.quellenstatus - 1 AS status , quelle.wiedervorlage , IF( qd1.wert = 'j' , '1' , '0' ) AS newsletter , IF ( sk1.systematikklasse IS NULL , '0' , '1' ) AS geo_check , IF ( sk2.systematikklasse IS NULL , '0' , '1' ) AS res_check FROM quellendefinition , quelle LEFT JOIN quellendefinition qd1 ON ( qd1.quelle = quelle.quelle AND qd1.quellenmerkmal = 11 ) LEFT JOIN ( quelle_systematikklasse qsk1 , systematikklasse sk1 ) ON ( qsk1.quelle = quelle.quelle AND qsk1.systematikklasse = sk1.systematikklasse AND sk1.systematik IN ( 43 , 44 ) ) LEFT JOIN ( quelle_systematikklasse qsk2 , systematikklasse sk2 ) ON ( qsk2.quelle = quelle.quelle AND qsk2.systematikklasse = sk2.systematikklasse AND sk2.systematik = 41 ) WHERE quellendefinition.quelle = quelle.quelle AND quelle.quelle = ".$quelle." GROUP BY quelle.quelle";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            if ($stmt !== false) {
                return $stmt->fetch();
            }
        }
        return null;
    }

    /*
      METH private boolean exportQuelle( int $quelle )
    */
    private function exportQuelle( $quelle ) {
        if ( $quelle > 0 ) {
            $this->vascodaXmlGenerator->addElement( $quelle );
            $this->vascodaXmlGenerator->addDcProperty( 'identifier' );
            $this->vascodaXmlGenerator->addPropertyString( 'dc:identifier' , $quelle , 'vls:LSS' );
            $this->vascodaXmlGenerator->addDcProperty( 'type' , 'vap:objekttypen' );
            $this->vascodaXmlGenerator->addPropertyString( 'dc:type' , 'Internetressource' );
            $quellenDefinitionData = $this->collectExportData( $quelle , 'definition' );
            $personIds = array();
            $koerperschaftIds = array();
            $koerperschaftEmail = '';
            $id = '';
            foreach ( $quellenDefinitionData as $quellendefinition => $data ) {
                $data['wert'] = trim( strtr( $data['wert'] , array( '<br />' => "\n" , '<br/>' => "\n" , '<br>' => "\n" ) ) );
                //				$data['wert'] = strtr( $data['wert'] , array( '<' => '&lt;' , '>' => '&gt;' ) );
                $data['wert'] = htmlspecialchars( $data['wert'] , ENT_QUOTES , 'UTF-8' );
                if ( $data['quellenmerkmal'] == 'url' ) {
                    if ( $data['merkmalsnorm'] == 'webarchiv' ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'identifier' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:identifier' , $data['wert'] , 'Web-Archiv' );
                    } else {
                        $this->vascodaXmlGenerator->addDcURI( 'identifier' , $data['wert'] );
                    }
                } elseif ( $data['quellenmerkmal'] == 'identnummer' ) {
                    if ( $data['merkmalsnorm'] == 'urn' ) {
                        $this->vascodaXmlGenerator->addDcURI( 'identifier' , $data['wert'] );
                    } elseif ( $data['merkmalsnorm'] == 'isbn' || $data['merkmalsnorm'] == 'issn' ) {
                        $this->vascodaXmlGenerator->addDcURI( 'identifier' , 'urn:'.strtoupper( $data['merkmalsnorm'] ).':'.$data['wert'] );
                    } elseif ( $data['merkmalsnorm'] == 'dbis-id' || $data['merkmalsnorm'] == 'ppn' ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'identifier' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:identifier' , $data['wert'] , 'lss:'.strtoupper( $data['merkmalsnorm'] ) );
                    } elseif ( $data['merkmalsnorm'] == 'clio-id' ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'identifier' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:identifier' , $data['wert'] , 'CLIO' );
                    } elseif ( $data['merkmalsnorm'] == 'ezb-id' ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'identifier' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:identifier' , $data['wert'] , 'lss:EZB-ID' );
                    } elseif ( $data['merkmalsnorm'] == 'zdb-id' ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'identifier' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:identifier' , $data['wert'] , 'vap:ZDBID' );
                    } elseif ( $data['merkmalsnorm'] == 'edz-doc' ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'identifier' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:identifier' , $data['wert'] , 'lss_edz' );
                    } elseif ( $data['merkmalsnorm'] == 'edz-kat' ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'identifier' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:identifier' , $data['wert'] , ':kat' );
                    }
                } elseif ( $data['quellenmerkmal'] == 'titel' ) {
                    if ( $id != $data['quellenmerkmal'] ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'title' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:title' , $data['wert'] , null , $data['sprache'] );
                    } else {
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:title' , $data['wert'] , null , $data['sprache'] );
                    }
                } elseif ( $data['quellenmerkmal'] == 'titel_weitere' || $data['quellenmerkmal'] == 'titel_uebersetzt' ) {
                    if ( $id != $data['quellenmerkmal'] ) {
                        $this->vascodaXmlGenerator->addDctermsProperty( 'alternative' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:alternative' , $data['wert'] , null , $data['sprache'] );
                    } else {
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:alternative' , $data['wert'] , null , $data['sprache'] );
                    }
                } elseif ( $data['quellenmerkmal'] == 'abstract' ) {
                    if ( $data['merkmalsnorm'] == 'url' ) {
                        $this->vascodaXmlGenerator->addDctermsURI( 'abstract' , $data['wert'] , $data['sprache'] );
                    } else {
                        if ( preg_match( '/^(.+)\[([^\[\]]+)\]$/s' , $data['wert'] , $matches ) ) {
                            $this->vascodaXmlGenerator->addDctermsProperty( 'abstract' , $matches[2] );
                            $this->vascodaXmlGenerator->addPropertyString( 'dcterms:abstract' , $matches[1] , null , $data['sprache'] );
                        } else {
                            if ( $id != $data['quellenmerkmal'] ) {
                                $this->vascodaXmlGenerator->addDctermsProperty( 'abstract' );
                                $this->vascodaXmlGenerator->addPropertyString( 'dcterms:abstract' , $data['wert'] , null , $data['sprache'] );
                            } else {
                                $this->vascodaXmlGenerator->addPropertyString( 'dcterms:abstract' , $data['wert'] , null , $data['sprache'] );
                            }
                        }
                    }
                } elseif ( $data['quellenmerkmal'] == 'inhalt' ) {
                    if ( $data['merkmalsnorm'] == 'url' ) {
                        $this->vascodaXmlGenerator->addDctermsURI( 'tableOfContents' , $data['wert'] , $data['sprache'] );
                    } else {
                        if ( $id != $data['quellenmerkmal'] ) {
                            $this->vascodaXmlGenerator->addDctermsProperty( 'tableOfContents' );
                            $this->vascodaXmlGenerator->addPropertyString( 'dcterms:tableOfContents' , $data['wert'] , null , $data['sprache'] );
                        } else {
                            $this->vascodaXmlGenerator->addPropertyString( 'dcterms:tableOfContents' , $data['wert'] , null , $data['sprache'] );
                        }
                    }
                } elseif ( $data['quellenmerkmal'] == 'person_pnd' ) {
                    $property = ( $quellenDefinitionData[$data['elterndefinition']]['merkmalsnorm'] == 'Urheber' ) ? 'creator' : ( ( $quellenDefinitionData[$data['elterndefinition']]['merkmalsnorm'] == 'Verleger' ) ? 'publisher' : 'contributor' );
                    $personName = htmlspecialchars( $quellenDefinitionData[$data['elterndefinition']]['wert'] );
                    $personIds[] = $data['elterndefinition'];
                    $this->vascodaXmlGenerator->addDcProperty( $property , 'vap:PND' , $data['wert'] , 'p' );
                    $this->vascodaXmlGenerator->addPropertyString( 'dc:'.$property , $personName );
                } elseif ( $data['quellenmerkmal'] == 'person' && ! in_array( $quellendefinition , $personIds ) ) {
                    $property = ( $data['merkmalsnorm'] == 'Urheber' ) ? 'creator' : ( ( $data['merkmalsnorm'] == 'Verleger' ) ? 'publisher' : 'contributor' );
                    $this->vascodaXmlGenerator->addDcProperty( $property , null , null , 'p' );
                    $this->vascodaXmlGenerator->addPropertyString( 'dc:'.$property , $data['wert'] );
                } elseif ( $data['quellenmerkmal'] == 'koerperschaft_email' ) {
                    $koerperschaftEmail = $data['wert'];
                } elseif ( $data['quellenmerkmal'] == 'koerperschaft_gkd' ) {
                    $property = ( $quellenDefinitionData[$data['elterndefinition']]['merkmalsnorm'] == 'Urheber' ) ? 'creator' : ( ( $quellenDefinitionData[$data['elterndefinition']]['merkmalsnorm'] == 'Verleger' ) ? 'publisher' : ( ( $data['merkmalsnorm'] == 'Mitarbeiter' ) ? 'contributor' : 'host' ) );
                    $koerperschaftName = htmlspecialchars( $quellenDefinitionData[$data['elterndefinition']]['wert'] );
                    $koerperschaftIds[] = $data['elterndefinition'];
                    if ( $property == 'host' ) {
                        $this->vascodaXmlGenerator->addVapProperty( 'host' , 'vap:GKD' );
                        $this->vascodaXmlGenerator->addPropertyString( 'vap:'.$property , $koerperschaftName.' ; '.$data['wert'].' E-Mail: '.$koerperschaftEmail );
                    } else {
                        $this->vascodaXmlGenerator->addDcProperty( $property , 'vap:GKD' , $data['wert'] , 'c' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:'.$property , $koerperschaftName );
                    }
                    $koerperschaftEmail = '';
                } elseif ( $data['quellenmerkmal'] == 'koerperschaft' && ! in_array( $quellendefinition , $koerperschaftIds ) ) {
                    $property = ( $data['merkmalsnorm'] == 'Urheber' ) ? 'creator' : ( ( $data['merkmalsnorm'] == 'Verleger' ) ? 'publisher' : ( ( $data['merkmalsnorm'] =='Mitarbeiter' ) ? 'contributor' : 'host' ) );
                    if ( $property == 'host' ) {
                        $this->vascodaXmlGenerator->addVapProperty( 'host' );
                        $this->vascodaXmlGenerator->addPropertyString( 'vap:'.$property , $data['wert'] );
                    } else {
                        $this->vascodaXmlGenerator->addDcProperty( $property , null , null , 'c' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:'.$property , $data['wert'] );
                    }
                } elseif ( $data['quellenmerkmal'] == 'kommentar' ) {
                    $this->vascodaXmlGenerator->addLssProperty( 'comment' );
                    $this->vascodaXmlGenerator->addPropertyString( 'lss:comment' , $data['wert'] );
                } elseif ( $data['quellenmerkmal'] == 'publikationsdatum' ) {
                    $this->vascodaXmlGenerator->addDctermsProperty( 'issued' );
                    $this->vascodaXmlGenerator->addPropertyString( 'dcterms:issued' , $data['wert'] , 'dcterms:W3CDTF' );
                } elseif ( $data['quellenmerkmal'] == 'rechtliches' ) {
                    $this->vascodaXmlGenerator->addDcProperty( 'rights' );
                    $this->vascodaXmlGenerator->addPropertyString( 'dc:rights' , $data['wert'] );
                } elseif ( $data['quellenmerkmal'] == 'zeitabdeckung' ) {
                    $this->vascodaXmlGenerator->addDctermsProperty( 'temporal' );
                    $period = str_replace( array( 'ab ' , 'von ' ) , 'start=' , $data['wert'] );
                    $period = str_replace( ' bis ' , '; end=' , $period );
                    $period = str_replace( 'bis ' , 'end=' , $period );
                    $this->vascodaXmlGenerator->addPropertyString( 'dcterms:temporal' , $period , 'dcterms:Period' );
                } elseif ( $data['quellenmerkmal'] == 'umfang' ) {
                    $this->vascodaXmlGenerator->addDctermsProperty( 'extent' );
                    if ( $data['merkmalsnorm'] == 'kB' ) {
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:extent' , ( 1024 * (int)$data['wert'] ) , 'xsd:byte' );
                    } else {
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:extent' , $data['wert'] , 'lss:tPages' );
                    }
                } elseif ( $data['quellenmerkmal'] == 'beziehung' ) {
                    if ( $data['merkmalsnorm'] == 'url' ) {
                        $this->vascodaXmlGenerator->addDcURI( 'relation' , $data['wert'] );
                    } elseif ( $data['merkmalsnorm'] == 'isbn' ) {
                        $this->vascodaXmlGenerator->addDcURI( 'relation' , 'urn:ISBN:'.$data['wert'] );
                    } elseif ( $data['merkmalsnorm'] == 'issn' ) {
$this->vascodaXmlGenerator->addDcURI( 'relation' , 'urn:ISSN:'.$data['wert'] );
                    } elseif ( $data['merkmalsnorm'] == 'vi' ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'relation' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:relation' , $data['wert'] , 'VI' );
                    } else {
                        $this->vascodaXmlGenerator->addDcProperty( 'relation' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:relation' , $data['wert'] );
                    }
                } elseif ( $data['quellenmerkmal'] == 'beziehung_teil' || $data['quellenmerkmal'] == 'beziehung_enthaelt' ) {
                    $property = ( $data['quellenmerkmal'] == 'beziehung_teil' ) ? 'isPartOf' : 'hasPart';
                    if ( $data['merkmalsnorm'] == 'url' ) {
                        $this->vascodaXmlGenerator->addDctermsURI( $property , $data['wert'] );
                    } elseif ( $data['merkmalsnorm'] == 'isbn' ) {
                        $this->vascodaXmlGenerator->addDctermsURI( $property , 'urn:ISBN:'.$data['wert'] );
                    } elseif ( $data['merkmalsnorm'] == 'issn' ) {
                        $this->vascodaXmlGenerator->addDctermsURI( $property , 'urn:ISSN:'.$data['wert'] );
                    } elseif ( $data['merkmalsnorm'] == 'vi' ) {
                        $this->vascodaXmlGenerator->addDctermsProperty( $property );
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:'.$property , $data['wert'] , 'VI' );
                    } else {
                        $this->vascodaXmlGenerator->addDctermsProperty( $property );
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:'.$property , $data['wert'] );
                    }
                } elseif ( $data['quellenmerkmal'] == 'slav_keyword' ) {
                    $this->vascodaXmlGenerator->addDcProperty( 'subject' );
                    $this->vascodaXmlGenerator->addPropertyString( 'dc:subject' , $data['wert'] , 'sg:keywordtag' );
                } elseif ( $data['quellenmerkmal'] == 'slav_description' ) {
                    $this->vascodaXmlGenerator->addDcProperty( 'subject' );
                    $this->vascodaXmlGenerator->addPropertyString( 'dc:subject' , $data['wert'] , 'sg:descriptiontag' );
                } elseif ( $data['quellenmerkmal'] == 'slav_descriptionsuma' ) {
                    $this->vascodaXmlGenerator->addDcProperty( 'subject' );
                    $this->vascodaXmlGenerator->addPropertyString( 'dc:subject' , $data['wert'] , 'sg:descriptionsuma' );
                }
                $id = $data['quellenmerkmal'];
            }
            $quellenSystematikData = $this->collectExportData( $quelle , 'systematik' );
            $sprachsystematikSet = '';
            foreach ( $quellenSystematikData as $index => $data ) {
                $data['text'] = htmlspecialchars( $data['text'] , ENT_QUOTES , 'UTF-8' );
                $data['strukturmerkmal'] = htmlspecialchars( $data['strukturmerkmal'] , ENT_QUOTES , 'UTF-8' );
                if ( $data['systematik'] == 'sprachsystematik' ) {
                    if ( $data['strukturmerkmal'] != $sprachsystematikSet ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'language' , 'dcterms:ISO639-2' );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:language' , $data['strukturmerkmal'] );
                        $sprachsystematikSet = $data['strukturmerkmal'];
                    }
                } elseif ( $data['systematik'] == 'formatsystematik' ) {
                    $this->vascodaXmlGenerator->addDcProperty( 'format' , 'dcterms:IMT' );
                    $this->vascodaXmlGenerator->addPropertyString( 'dc:format' , $data['bezeichnung'] );
                } elseif ( $data['systematik'] == 'laendersystematik' ) {
                    if ( $id != $data['systematikklasse'] ) {
                        $this->vascodaXmlGenerator->addSpatialProperty( 'spatial' , 'lss:Geo' , $data['ddc_22'] , $data['strukturmerkmal'] );
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:spatial' , $data['text'] , null , $data['sprache'] );
                    } else {
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:spatial' , $data['text'] , null , $data['sprache'] );
                    }
                } elseif ( $data['systematik'] == 'standortsystematik' ) {
                    if ( $id != $data['systematikklasse'] ) {
                        $this->vascodaXmlGenerator->addSpatialProperty( 'subject' , 'lss:Loc' , $data['ddc_22'] , $data['strukturmerkmal'] );
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:subject' , $data['text'] , null , $data['sprache'] );
                    } else {
                        $this->vascodaXmlGenerator->addPropertyString( 'dcterms:subject' , $data['text'] , null , $data['sprache'] );
                    }
                } elseif ( $data['systematik'] == 'zeitsystematik' ) {
                    if ( $id != $data['systematikklasse'] ) {
                        $this->vascodaXmlGenerator->addDdcProperty( 'subject' , 'lss:Time' , $data['ddc'] , $data['ddc'] , $data['strukturmerkmal'] );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:subject' , $data['text'] , null , $data['sprache'] );
                    } else {
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:subject' , $data['text'] , null , $data['sprache'] );
                    }
                } elseif ( $data['systematik'] == 'ressourcentyp' ) {
                    if ( $id != $data['systematikklasse'] ) {
                        $this->vascodaXmlGenerator->addDcProperty( 'type' , 'lss:Res2' , $data['strukturmerkmal'] );
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:type' , $data['text'] , null , $data['sprache'] );
                    } else {
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:type' , $data['text'] , null , $data['sprache'] );
                    }
                } elseif ( isset( $this->vascodaXmlMap[$data['systematik']] ) ) {
                    $map = $this->vascodaXmlMap[$data['systematik']];
                    if ( $id != $data['systematikklasse'] ) {
                        if ( isset( $map['vapNotation'] ) ) {
                            if ( isset( $data['ddc'] ) && ! is_null( $data['ddc'] ) ) {
                                $this->vascodaXmlGenerator->addDdcProperty( 'subject' , $map['vocabScheme'] , $data['ddc'] , null , $data[$map['vapNotation']] );
                            } else {
                                $this->vascodaXmlGenerator->addDcProperty( 'subject' , $map['vocabScheme'] , $data[$map['vapNotation']] );
                            }
                        } else {
                            $this->vascodaXmlGenerator->addDcProperty( 'subject' , $map['vocabScheme'] );
                        }
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:subject' , $data['text'] , null , $data['sprache'] );
                    } else {
                        $this->vascodaXmlGenerator->addPropertyString( 'dc:subject' , $data['text'] , null , $data['sprache'] );
                    }
                }
                $id = $data['systematikklasse'];
            }
            $this->vascodaXmlGenerator->addAdminTag( $this->collectAdminData( $quelle ) );
        }
    }
}
