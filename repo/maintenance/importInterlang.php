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

class importInterlang extends Maintenance {
	protected $api;
	protected $verbose = false;

	public function __construct() {
		$this->mDescription = 'Import interlanguage links in Wikidata.\n\nThe links may be created by extractInterlang.sql';

		$this->addOption( 'verbose', "Print API requests and responses" );
		$this->addArg( 'lang', "Language code of the base wiki", true );
		$this->addArg( 'filename', "File with interlanguage links", true );
		$this->addArg( 'api', "Base API url", true );

		parent::__construct();
	}

	public function execute() {
		$this->verbose = (bool)$this->getOption( 'verbose' );
		$lang = $this->getArg( 0 );
		$filename = $this->getArg( 1 );
		$this->api = $this->getArg( 2 );

		$file = fopen( $filename, "r" );
		fgets( $file ); // We don't need the first line with column names.
		$current = ""; $current_id = false;
		while( $link = fgetcsv( $file, 0, "\t" ) ) {
			if( $link[0] !== $current ) {
				$current = $link[0];
				$this->maybePrint( "New item: $current" );
				$current_id = $this->createItem( $lang, $current );
			}
			$this->addLink( $link[1], $link[2], $current_id );
		}

		echo "Done!\n";
	}

	protected function createItem( $lang, $link ) {
		$link = self::niceLink( $link );

		$api_response = $this->callAPI( $this->api . "?action=wbgetitemid&format=php&site=" . urlencode( $lang ) . "&title=" . urlencode( $link ) );
		if( isset( $api_response['error'] ) ) {
			if( $api_response['error']['code'] !== 'no-such-item' ) {
				throw new MWException( "Error: " . $api_response['error']['info'] . "\n" );
			}
		} else {
			if( isset( $api_response['success'] ) && $api_response['success'] ) {
				$this->addLink( $lang, $link, $api_response['item']['id'] );
				return $api_response['item']['id'];
			} else {
				throw new MWException( "Error: no success\n" );
			}
		}

		// We only reach this if we have received an error, and the error was no-such-item
		$api_response = $this->callAPI( $this->api . "?action=wbsetitem&data=%7B%7D&format=php" );
		if( isset( $api_response['error'] ) ) {
			throw new MWException( "Error: " . $api_response['error']['info'] . "\n" );
		}
		if( isset( $api_response['success'] ) && $api_response['success'] ) {
			$this->addLink( $lang, $link, $api_response['item']['id'] );
			return $api_response['item']['id'];
		} else {
			throw new MWException( "Error: no success\n" );
		}
	}

	protected function addLink( $lang, $link, $id ) {
		$link = self::niceLink( $link );
		$label = self::makeLabel( $link );

		$api_response = $this->callAPI( $this->api . "?action=wblinksite&format=php&link=add&id=" . urlencode( $id ) . "&linksite=" . urlencode( $lang ) . "&linktitle=" . urlencode( $link ) );
		if( isset( $api_response['error'] ) ) {
			throw new MWException( "Error: " . $api_response['error']['info'] . "\n" );
		}

		$api_response = $this->callAPI( $this->api . "?action=wbsetlanguageattribute&format=php&item=set&id=" . urlencode( $id ) . "&language=" . urlencode( $lang ) . "&label=" . urlencode( $label ) );
		if( isset( $api_response['error'] ) ) {
			throw new MWException( "Error: " . $api_response['error']['info'] . "\n" );
		}
	}

	/**
	 * Call the API, return the results.
	 */
	protected function callAPI( $url ) {
		$this->maybePrint( $url );
		$api_response = Http::post( $url );
		$api_response = unserialize( $api_response );
		$this->maybePrint( $api_response );
		return $api_response;
	}

	/**
	 * Make a nicer link (convert _ to  ).
	 */
	protected static function niceLink( $link ) {
		return strtr( $link, "_", " " );
	}

	/**
	 * Make a label from a link: remove the disambiguator, which isn't 100% right but should do the job.
	 */
	protected static function makeLabel( $link ) {
		return preg_replace( "/ *[(].*[)]$/", "", $link );
	}

	/**
	 * Print a string, array or object if --verbose option is set.
	 */
	protected function maybePrint( $a ) {
		if( $this->verbose ) {
			if( is_scalar( $a ) ) {
				echo $a . (substr( $a, -1 ) != "\n"? "\n": "");
			} else {
				print_r( $a );
			}
		}
	}

}

$maintClass = 'importInterlang';
require_once( RUN_MAINTENANCE_IF_MAIN );
