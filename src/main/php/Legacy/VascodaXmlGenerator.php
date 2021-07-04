<?php

namespace SubHH\Linkshare\OAI\Legacy;

/*
DESC Generierung des Vascoda XML-Formats
*/
class VascodaXmlGenerator {

/*
PROP private array $xmlZeilen
*/
	private $xml = null;
	
/*
METH public void __construct()
*/
	public function __construct() {
		$this->xml = array();
	}

/*
METH public void startVascodaXml()
*/
	public function startVascodaXml() {
		$this->xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
		$this->xml[] = '<dcx:descriptionSet xmlns:dcx="http://purl.org/dc/xml/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:lss="http://www.academic-linkshare.de/lss/1.5/" xmlns:vap="http://www.vascoda.de/vap/2.0/" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';
	}

/*
METH public void finishVascodaXml()
*/
	public function finishVascodaXml() {
		$this->xml[] = '</dcx:descriptionSet>';
	}

/*
METH private int findLastIndex( string $searchValue , int $spaces = 0 )
*/
	private function findLastIndex( $searchValue , $spaces = 0 ) {
		$keyList = array_keys( $this->xml , $this->getSpace( $spaces ).$searchValue );
		return ( count( $keyList ) > 0 ) ? intval( array_pop( $keyList ) ) : false;
	}

/*
METH public array getVascodaXml()
*/
	public function getVascodaXml() {
		$xml = $this->xml;
		$this->xml = array();
		return $xml;
	}

/*
METH private string getSpace( int $count )
*/
	private function getSpace( $count ) {
		$baseSpace = "  ";
		$space = '';
		for ( $index = 0 ; $index < $count ; $index++ ) {
			$space .= $baseSpace;
		}
		return $space;
	}

/*
METH public void addElement( int $id )
*/
	public function addElement( $id ) {
		if ( ( $index = $this->findLastIndex( '</dcx:description>' , 1 ) ) === false ) {
			$index = $this->findLastIndex( '</dcx:descriptionSet>' ) - 1;
			$index = count( $this->xml );
		}
		array_splice( $this->xml , ( $index + 1 ) , 0 , array( $this->getSpace( 1 ).'<dcx:description xmlns:dcx="http://purl.org/dc/xml/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:lss="http://www.academic-linkshare.de/lss/1.5/" xmlns:vap="http://www.vascoda.de/vap/2.0/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" vap:localId="'.$id.'" vap:collectionId="info:sid/cld.vascoda.de:modul-X" vap:clusterId="info:sid/cld.vascoda.de:cluster-X">' , $this->getSpace( 1 ).'</dcx:description>' ) );
	}

/*
METH public void addVapProperty( string $property , string $vocabScheme = null )
*/
	public function addVapProperty( $property , $vocabScheme = null ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		if ( is_null( $vocabScheme ) ) {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<vap:'.$property.'>' ) );
		} else {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<vap:'.$property.' dcx:vocabEncSchemeQName="'.$vocabScheme.'">' ) );
		}
		array_splice( $this->xml , $index + 1 , 0 , array( $this->getSpace( 2 ).'</vap:'.$property.'>' ) );
	}

/*
METH public void addDcProperty( string $property , string $vocabScheme = null , string $vapNotation = null , string $vapEntity = null )
*/
	public function addDcProperty( $property , $vocabScheme = null , $vapNotation = null , $vapEntity = null ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		if ( is_null( $vocabScheme ) ) {
			if ( is_null( $vapEntity ) ) {
				array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.'>' ) );
			} else {
				array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.' vap:entity="'.$vapEntity.'">' ) );
			}
		} else {
			if ( is_null( $vapEntity ) ) {
				if ( is_null( $vapNotation ) ) {
					array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.' dcx:vocabEncSchemeQName="'.$vocabScheme.'">' ) );
				} else {
					array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.' dcx:vocabEncSchemeQName="'.$vocabScheme.'" vap:notation="'.$vapNotation.'">' ) );
				}
			} else {
				array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.' dcx:vocabEncSchemeQName="'.$vocabScheme.'" vap:entity="'.$vapEntity.'" vap:notation="'.$vapNotation.'">' ) );
			}
		}
		array_splice( $this->xml , $index + 1 , 0 , array( $this->getSpace( 2 ).'</dc:'.$property.'>' ) );
	}

/*
METH public void addDdcProperty( string $property , string $vocabScheme , string $vapDdc , string $vapDdc2 = null , string $vapNotation )
*/
	public function addDdcProperty( $property , $vocabScheme , $vapDdc , $vapDdc2 = null , $vapNotation ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		if ( ! is_null( $vapDdc2 ) ) {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.' dcx:vocabEncSchemeQName="'.$vocabScheme.'" vap:ddc2="'.$vapDdc2.'" vap:notation="'.$vapNotation.'" vap:ddc="'.$vapDdc.'">' ) );
		} else {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.' dcx:vocabEncSchemeQName="'.$vocabScheme.'" vap:notation="'.$vapNotation.'" vap:ddc="'.$vapDdc.'">' ) );
		}
		array_splice( $this->xml , $index + 1 , 0 , array( $this->getSpace( 2 ).'</dc:'.$property.'>' ) );
	}


/*
METH public void addDctermsProperty( string $property , string $vapEdt = null )
*/
	public function addDctermsProperty( $property , $vapEdt = null ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		if ( is_null( $vapEdt ) ) {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dcterms:'.$property.'>' ) );
		} else {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dcterms:'.$property.' vap:edt="'.$vapEdt.'">' ) );
		}
		array_splice( $this->xml , $index + 1 , 0 , array( $this->getSpace( 2 ).'</dcterms:'.$property.'>' ) );
	}

/*
METH public void addSpatialProperty( string $property , string $vocabScheme , string $lssDdc2 , string $vapNotation )
*/
	public function addSpatialProperty( $property , $vocabScheme , $lssDdc2 , $vapNotation ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dcterms:'.$property.' dcx:vocabEncSchemeQName="'.$vocabScheme.'" lss:ddc2="'.$lssDdc2.'" vap:notation="'.$vapNotation.'">' ) );
		array_splice( $this->xml , $index + 1 , 0 , array( $this->getSpace( 2 ).'</dcterms:'.$property.'>' ) );
	}

/*
METH public void addLssProperty( string $property )
*/
	public function addLssProperty( $property ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<lss:'.$property.'>' ) );
		array_splice( $this->xml , $index + 1 , 0 , array( $this->getSpace( 2 ).'</lss:'.$property.'>' ) );
	}

/*
METH public void addPropertyString( string $fullProperty , string $string , string $syntaxScheme = null , string $language = null )
*/
	public function addPropertyString( $fullProperty , $string , $syntaxScheme = null , $language = null ) {
		$index = $this->findLastIndex( '</'.$fullProperty.'>' , 2 );
		if ( is_null( $syntaxScheme ) ) {
			if ( is_null( $language ) ) {
				array_splice( $this->xml , $index , 0 , array( $this->getSpace( 3 ).'<dcx:valueString>'.$string.'</dcx:valueString>' ) );
			} else {
				array_splice( $this->xml , $index , 0 , array( $this->getSpace( 3 ).'<dcx:valueString xml:lang="'.$language.'">'.$string.'</dcx:valueString>' ) );
			}
		} else {
			if ( is_null( $language ) ) {
				array_splice( $this->xml , $index , 0 , array( $this->getSpace( 3 ).'<dcx:valueString dcx:syntaxEncSchemeQName="'.$syntaxScheme.'">'.$string.'</dcx:valueString>' ) );
			} else {
				array_splice( $this->xml , $index , 0 , array( $this->getSpace( 3 ).'<dcx:valueString dcx:syntaxEncSchemeQName="'.$syntaxScheme.'" xml:lang="'.$language.'">'.$string.'</dcx:valueString>' ) );
			}
		}
	}

/*
METH public void addDcURI( string $property , string $uri , string $language = null )
*/
	public function addDcURI( $property , $uri , $language = null ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		if ( ! is_null( $language ) ) {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.' dcx:valueURI="'.$uri.'" xml:lang="'.$language.'" />' ) );
		} else {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dc:'.$property.' dcx:valueURI="'.$uri.'" />' ) );
		}
	}

/*
METH public void addDctermsURI( string $property , string $uri , string $language = null )
*/
	public function addDctermsURI( $property , $uri , $language = null ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		if ( ! is_null( $language ) ) {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dcterms:'.$property.' dcx:valueURI="'.$uri.'" xml:lang="'.$language.'" />' ) );
		} else {
			array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).'<dcterms:'.$property.' dcx:valueURI="'.$uri.'" />' ) );
		}
	}

/*
METH public void addAdminTag( array $adminData )
*/
	public function addAdminTag( $adminData ) {
		$index = $this->findLastIndex( '</dcx:description>' , 1 );
		$adminTag = '<lss:admin lss:geo_check="'.$adminData['geo_check'].'" lss:activated="'.$adminData['freigeschaltet'].'" lss:status="'.$adminData['status'].'" lss:res_check="'.$adminData['res_check'].'" lss:created="'.$adminData['erstellt'].'" lss:resubmission="'.$adminData['wiedervorlage'].'" lss:updated="'.$adminData['geaendert'].'" lss:subscription="'.$adminData['newsletter'].'" vap:access="" vap:main_rec_id="" />';
		array_splice( $this->xml , $index , 0 , array( $this->getSpace( 2 ).$adminTag ) );
	}

}

?>
