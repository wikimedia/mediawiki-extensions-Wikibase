<?php

/**
 * Maintenance script for importing interlanguage links in Wikidata.
 *
 * For using it with the included simple-elements.csv and fill the database with chemical elements, use it thusly:
 *
 * php importInterlang.php --verbose --ignore-errors simple simple-elements.csv http://$HOST/w/api.php
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';
require_once $basePath . '/includes/Exception.php';

class importInterlangException extends MWException {}

class importInterlang extends Maintenance {
	protected $api;
	protected $verbose = false;
	protected $ignore_errors = false;

	public function __construct() {
		$this->mDescription = "Import interlanguage links in Wikidata.\n\nThe links may be created by extractInterlang.sql";

		$this->addOption( 'skip', "Skip number of entries in the import file" );
		$this->addOption( 'only', "Only import the specific entry from the import file" );
		$this->addOption( 'verbose', "Print API requests and responses" );
		$this->addOption( 'ignore-errors', "Ignore API errors" );
		$this->addArg( 'lang', "Language code of the base wiki", true );
		$this->addArg( 'filename', "File with interlanguage links", true );
		$this->addArg( 'api', "Base API url", true );

		parent::__construct();
	}

	public function execute() {
		$this->verbose = (bool)$this->getOption( 'verbose' );
		$this->ignore_errors = (bool)$this->getOption( 'ignore-errors' );
		$this->skip = (int)$this->getOption( 'skip' );
		$this->only = (int)$this->getOption( 'only' );
		$lang = $this->getArg( 0 );
		$filename = $this->getArg( 1 );
		$this->api = $this->getArg( 2 );

		$file = fopen( $filename, "r" );
		fgets( $file ); // We don't need the first line with column names.
		$current = ""; $current_id = false;
		$count = 0;
		while( $link = fgetcsv( $file, 0, "\t" ) ) {
			try {
				if( $link[0] !== $current ) {
					$count++;
					$current = $link[0];
					if ( ( $this->skip !== 0 ) && ( $this->skip > $count ) ) {
						continue;
					}
					if ( ( $this->only !== 0 ) && ( $this->only !== $count ) ) {
						if ($this->only < $count) {
							break;
						}
						continue;
					}
					$this->maybePrint( "New item: $current" );
					$current_id = $this->createItem( $lang, $current );
				}
				if ( $current_id ) {
					$this->addLink( $link[1], $link[2], $current_id );
				}
			} catch( importInterlangException $e ) {
				if( !$this->ignore_errors ) {
					throw $e;
				}
				if( $this->verbose ) {
					echo "Error: " . $e->getMessage() . "\n";
				}
			}
		}

		echo "Done!\n";
	}

	protected function createItem( $lang, $link ) {
		$link = self::niceLink( $link );

		$api_response = $this->callAPI( $this->api . "?action=wbgetitems&format=php&sites=" . urlencode( $lang ) . "wiki" . "&titles=" . urlencode( $link ) );
		if( $this->verbose ) {
			print_r ( $api_response );
		}
		if( isset( $api_response['error'] ) ) {
			if( $api_response['error']['code'] !== 'no-such-item' ) {
				throw new importInterlangException( $api_response['error']['info'] );
			}
		} else {
			if( isset( $api_response['success'] ) && $api_response['success'] ) {
				$found = array_filter( $api_response['items'], function( $item ) { return !isset( $item['missing'] ); } );
				if ( count($found) > 1 ) {
					throw new importInterlangException( "several hits: " . count($found) );
				}
				$item = array_pop( $found );
				if ( isset( $item['id'] ) && $item['id'] !== 0 ) {
					$this->addLink( $lang, $link, $item['id'] );
					$this->maybePrint( "The ID#2 is now: " . $item['id'] );
					return $item['id'];
				}
			} else {
				throw new importInterlangException( "no success" );
			}
		}

		// We only reach this if we have received an error, and the error was no-such-item
		// Note: After the changes we will reach this point if no valid items are found,
		// that is they are "missing"
		$api_response = $this->callAPI( $this->api . "?action=wbsetitem&data=%7B%7D&format=php" );
		if( $this->verbose ) {
			print_r ( $api_response );
		}
		if( isset( $api_response['error'] ) ) {
			throw new importInterlangException( $api_response['error']['info'] );
		}
		if( isset( $api_response['success'] ) && $api_response['success'] ) {
			$this->addLink( $lang, $link, $api_response['item']['id'] );
			$this->maybePrint( "The ID is now: " . $api_response['item']['id'] );
			return $api_response['item']['id'];
		} else {
			throw new importInterlangException( "no success" );
		}
	}

	protected function addLink( $lang, $link, $id ) {
		print( "$lang: $link\n" );
		// If a link is empty (which is a valid MediaWiki interlanguage link), fail silently.
		if( $link === "" ) {
			$this->maybePrint( "Skipping empty link." );
			return;
		}
		if( $id === "" ) {
			$this->maybePrint( "Skipping empty id." );
			return;
		}
		$label = self::niceLink( $link );

		$api_response = $this->callAPI( $this->api . "?action=wbsetsitelink&format=php&id=" . urlencode( $id )	. "&linksite=" . urlencode( $lang ) . "wiki" . "&linktitle=" . urlencode( $link ) );
		if( $this->verbose ) {
				print_r ( $api_response );
		}
		if( isset( $api_response['error'] ) ) {
			throw new importInterlangException( $api_response['error']['info'] );
		}

		$api_response = $this->callAPI( $this->api . "?action=wbsetlabel&format=php&id=" . urlencode( $id ) . "&language=" . urlencode( $lang ) . "&value=" . urlencode( $label ) );
		if( isset( $api_response['error'] ) ) {
			throw new importInterlangException( $api_response['error']['info'] );
		}
	}

	/**
	 * Call the API, return the results.
	 */
	protected function callAPI( $url ) {
		$this->maybePrint( $url );
		$api_response = Http::post( $url );
		$this->maybePrint( "API response is: " . $api_response . "\n" );
		$api_response = unserialize( $api_response );
		$this->maybePrint( $api_response );
		if( empty( $api_response ) ) {
			throw new importInterlangException( "API returned invalid response" );
		}
		return $api_response;
	}

	/**
	 * Make a nicer link (convert _ to	).
	 */
	protected static function niceLink( $link ) {
		return strtr( $link, "_", " " );
	}

	/**
	 * Print a scalar, array or object if --verbose option is set.
	 */
	protected function maybePrint( $a ) {
		if( $this->verbose ) {
			if( is_bool( $a ) ) {
				echo $a? "true\n": "false\n";
			} elseif( is_scalar( $a ) ) {
				echo $a . (substr( $a, -1 ) != "\n"? "\n": "");
			} else {
				print_r( $a );
			}
		}
	}

}

$maintClass = 'importInterlang';
require_once( RUN_MAINTENANCE_IF_MAIN );
