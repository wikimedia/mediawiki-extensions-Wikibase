<?php

/**
 * Maintenance scrtip for importing interlanguage links in Wikidata.
 *
 * @since 0.1
 *
 * @file importInterlang.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 */

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : dirname( __FILE__ ) . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class DeleteAllTheDatas extends Maintenance {
	protected $api;

	public function __construct() {
		$this->mDescription = 'Import interlanguage links in Wikidata.\n\nThe links may be created by extractInterlang.sql';

		$this->addArg( 'lang', 'Language code of the base wiki' );
		$this->addArg( 'filename', 'File with interlanguage links' );
		$this->addArg( 'api', 'Base API url' );

		parent::__construct();
	}

	public function execute() {
		$lang = $this->getArg( 0 );
		$filename = $this->getArg( 1 );
		$this->api = $this->getArg( 2 );

		$file = fopen( $filename, "r" );
		fgets( $file ); // We don't need the first line with column names.
		$current = ""; $current_id = false;
		while( $link = fgetcsv( $file, 0, "\t" ) ) {
			if( $link[0] !== $current ) {
				$current = $link[0];
echo "New current: $current\n\n\n\n";
				$current_id = $this->importLink( $lang, $current );
			}
			$this->importLink( $link[1], $link[2], $current_id );
		}

		echo "Done!\n";
	}

	protected function importLink( $lang, $link, $id = false ) {
		$link = strtr( $link, "_", " " );
		$label = preg_replace( "/ *[(].*[)]$/", "", $link ); // For the label we remove the disambiguator, which isn't 100% correct but it will do the job.

		if( !$id ) {
			$url = $this->api . "?action=wbsetlabel&format=php&item=add&site=" . urlencode( $lang ) . "&title=" . urlencode( $link ) . "&language=" . urlencode( $lang ) . "&label=" . urlencode( $label );
echo "add: $url\n";
			$api_response = Http::post( $url );
			$api_response = unserialize( $api_response );
print_r($api_response);
			if( isset( $api_response['error'] ) ) {
				if( $api_response['error']['code'] == 'add-exists' ) {
					return $api_response['error']['item']['id'];
				} else {
					throw new MWException( "Error: " . $api_response['error']['info'] . "\n" );
				}
			}
			if( isset( $api_response['success'] ) && $api_response['success'] ) {
				return $api_response['item']['id'];
			}
		} else {
			$url = $this->api . "?action=wblinksite&format=php&link=update&id=" . urlencode( $id ) . "&linksite=" . urlencode( $lang ) . "&linktitle=" . urlencode( $link );
echo "edit1: $url\n";
			$api_response = Http::post( $url );
			$api_response = unserialize( $api_response );
print_r($api_response);
			if( isset( $api_response['error'] ) ) {
				throw new MWException( "Error: " . $api_response['error']['info'] . "\n" );
			}

			$url = $this->api . "?action=wbsetlabel&format=php&item=update&id=" . urlencode( $id ) . "&language=" . urlencode( $lang ) . "&label=" . urlencode( $label );
echo "edit2: $url\n";
			$api_response = Http::post( $url );
			$api_response = unserialize( $api_response );
print_r($api_response);
			if( isset( $api_response['error'] ) ) {
				throw new MWException( "Error: " . $api_response['error']['info'] . "\n" );
			}
		}
	}

}

$maintClass = 'DeleteAllTheDatas';
require_once( RUN_MAINTENANCE_IF_MAIN );
