<?php

namespace Wikibase;

use Exception;
use Maintenance;
use User;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for importing properties in Wikidata.
 *
 * For using it with the included en-elements-properties.csv and fill the database with properties
 * of chemical elements, use it thusly:
 *
 * php importInterlang.php --verbose --ignore-errors en en-elements-properties.csv
 *
 * For now, this script is little more than a copy of importInterlang.php. Once we have more
 * interesting properties, this will change.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */
class ImportProperties extends Maintenance {

	/**
	 * @var bool
	 */
	private $verbose = false;

	/**
	 * @var bool
	 */
	private $ignoreErrors = false;

	/**
	 * @var int
	 */
	private $skip = 0;

	/**
	 * @var int
	 */
	private $only = 0;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var EntityStore
	 */
	private $store;

	public function __construct() {
		$this->addDescription( "Import properties in Wikidata." );

		$this->addOption( 'skip', "Skip number of entries in the import file" );
		$this->addOption( 'only', "Only import the specific entry from the import file" );
		$this->addOption( 'verbose', "Print activity" );
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
		$this->ignoreErrors = (bool)$this->getOption( 'ignore-errors' );
		$this->skip = (int)$this->getOption( 'skip' );
		$this->only = (int)$this->getOption( 'only' );
		$languageCode = $this->getArg( 0 );
		$filename = $this->getArg( 1 );

		$file = fopen( $filename, 'r' );

		if ( !$file ) {
			$this->doPrint( "ERROR: failed to open `$filename`" );
			return;
		}

		$current = null;
		$currentProperties = [];
		$count = 0;
		$ok = true;
		while ( true ) {
			$link = fgetcsv( $file, 0, "\t" );
			if ( !$link ) {
				break;
			} elseif ( $link[0] === $current ) {
				continue;
			}

			if ( !empty( $currentProperties ) ) {
				$ok = $this->createProperty( $currentProperties );

				if ( !$ok && !$this->ignoreErrors ) {
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
			$currentProperties = array( $languageCode => $current );
		}

		if ( !$ok && !$this->ignoreErrors ) {
			$this->doPrint( 'Aborted!' );
			return;
		}

		if ( !empty( $currentProperties ) ) {
			$ok = $this->createProperty( $currentProperties );
		}

		if ( $ok ) {
			$this->maybePrint( 'Done.' );
		}
	}

	/**
	 * @param string[] $labels Associative array, mapping language codes to labels.
	 *
	 * @return bool true if the item was created, false otherwise
	 */
	private function createProperty( array $labels ) {
		$property = Property::newFromType( 'wikibase-item' );
		$fingerprint = $property->getFingerprint();

		foreach ( $labels as $languageCode => $label ) {
			$fingerprint->setLabel( $languageCode, $label );
		}

		try {
			$this->store->saveEntity( $property, 'imported', $this->user, EDIT_NEW );
			return true;
		} catch ( Exception $ex ) {
			$this->doPrint( 'ERROR: ' . str_replace( "\n", ' ', $ex->getMessage() ) );
		}

		return false;
	}

	/**
	 * Print a scalar, array or object if --verbose option is set.
	 *
	 * @see doPrint
	 */
	private function maybePrint( $a ) {
		if ( $this->verbose ) {
			$this->doPrint( $a );
		}
	}

	/**
	 * Output a scalar, array or object to the default channel
	 *
	 * @see Maintenance::output
	 */
	private function doPrint( $a ) {
		if ( is_null( $a ) ) {
			$a = 'null';
		} elseif ( is_bool( $a ) ) {
			$a = $a ? 'true' : 'false';
		} elseif ( !is_scalar( $a ) ) {
			$a = print_r( $a, true );
		}

		$this->output( trim( strval( $a ) ) . "\n" );
	}

}

$maintClass = ImportProperties::class;
require_once RUN_MAINTENANCE_IF_MAIN;
