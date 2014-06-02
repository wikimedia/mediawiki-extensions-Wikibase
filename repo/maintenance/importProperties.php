<?php

use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Store\EntityStore;

/**
 * Maintenance script for importing properties in Wikidata.
 *
 * For using it with the included en-elements-properties.csv and fill the database with properties of chemical elements, use it thusly:
 *
 * php importInterlang.php --verbose --ignore-errors en en-elements-properties.csv
 *
 * For now, this script is little more than a copy of importInterlang.php. Once we have more interesting properties, this will change.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class importProperties extends Maintenance {
	protected $verbose = false;
	protected $ignore_errors = false;
	protected $skip = 0;
	protected $only = 0;

	/**
	 * @var User
	 */
	protected $user = null;

	/**
	 * @var EntityStore
	 */
	protected $store = null;

	public function __construct() {
		$this->mDescription = "Import properties in Wikidata.";

		$this->addOption( 'skip', "Skip number of entries in the import file" );
		$this->addOption( 'only', "Only import the specific entry from the import file" );
		$this->addOption( 'verbose', "Print activity " );
		$this->addOption( 'ignore-errors', "Continue after errors" );
		$this->addArg( 'lang', "The source wiki's language code (e.g. `en`)", true );
		$this->addArg( 'filename', "File with interlanguage links", true );

		parent::__construct();
	}

	public function execute() {
		global $wgUser;

		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$this->user = $wgUser;
		$this->store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$this->verbose = (bool)$this->getOption( 'verbose' );
		$this->ignore_errors = (bool)$this->getOption( 'ignore-errors' );
		$this->skip = (int)$this->getOption( 'skip' );
		$this->only = (int)$this->getOption( 'only' );
		$lang = $this->getArg( 0 );
		$filename = $this->getArg( 1 );

		$file = fopen( $filename, "r" );

		if ( !$file ) {
			$this->doPrint( "ERROR: failed to open `$filename`" );
			return;
		}

		$current = null;
		$current_properties = array();
		$count = 0;
		$ok = true;
		while( $link = fgetcsv( $file, 0, "\t" ) ) {
			if( $link[0] !== $current ) {
				if ( !empty( $current_properties ) ) {
					$ok = $this->createProperty( $current_properties );

					if ( !$ok && !$this->ignore_errors ) {
						break;
					}
				}

				$count++;
				if ( ( $this->skip !== 0 ) && ( $this->skip > $count ) ) {
					continue;
				}
				if ( ( $this->only !== 0 ) && ( $this->only !== $count ) ) {
					if ( $this->only < $count ) {
						break;
					}
					continue;
				}

				$current = $link[0];
				$this->maybePrint( "Processing `$current`" );
				$current_properties = array(
					$lang => $current
				);
			}
		}

		if ( !$ok && !$this->ignore_errors ) {
			$this->doPrint( "Aborted!" );
			return;
		}

		if ( !( $current_properties  === array() ) ) {
			$ok = $this->createProperty( $current_properties );
		}

		if ( $ok ) {
			$this->maybePrint( "Done." );
		}
	}

	/**
	 * @param Array $data An associative array of interlanguage links, mapping site IDs to page titles on that site.
	 *
	 * @return bool true if the item was created, false otherwise
	 */
	protected function createProperty( $data ) {
		$property = Property::newFromType( 'wikibase-item' );

		foreach ( $data as $lang => $title ) {
			$property->setLabel( $lang, $title );
		}

		try {
			$this->store->saveEntity( $property, 'imported', $this->user, EDIT_NEW );

			return true;
		} catch ( Exception $ex ) {
			$this->doPrint( "ERROR: " . strtr( $ex->getMessage(), "\n", " " ) );
		}

		return false;
	}

	/**
	 * Print a scalar, array or object if --verbose option is set.
	 *
	 * @see importInterlang::doPrint()
	 * @see Maintenance::output()
	 */
	protected function maybePrint( $a ) {
		if( $this->verbose ) {
			$this->doPrint( $a );
		}
	}

	/**
	 * Output a scalar, array or object to the default channel
	 *
	 * @see Maintenance::output()
	 */
	protected function doPrint( $a ) {
		if( is_null( $a ) ) {
			$a = 'null';
		} elseif( is_bool( $a ) ) {
			$a = ( $a? "true\n": "false\n" );
		} elseif( !is_scalar( $a ) ) {
			$a = print_r( $a, true );
		}

		$this->output( trim( strval( $a ) ) . "\n" );
	}

}

$maintClass = 'importProperties';
require_once( RUN_MAINTENANCE_IF_MAIN );
