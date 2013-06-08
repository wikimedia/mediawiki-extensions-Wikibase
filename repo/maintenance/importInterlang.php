<?php

/**
 * Maintenance script for importing interlanguage links in Wikidata.
 *
 * For using it with the included simple-elements.csv and fill the database with chemical elements, use it thusly:
 *
 * php importInterlang.php --verbose --ignore-errors simple simple-elements.csv
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */

use Wikibase\DataModel\SimpleSiteLink;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';
require_once $basePath . '/includes/Exception.php';

class importInterlang extends Maintenance {
	protected $verbose = false;
	protected $ignore_errors = false;
	protected $skip = 0;
	protected $only = 0;

	public function __construct() {
		$this->mDescription = "Import interlanguage links in Wikidata.\n\nThe links may be created by extractInterlang.sql";

		$this->addOption( 'skip', "Skip number of entries in the import file" );
		$this->addOption( 'only', "Only import the specific entry from the import file" );
		$this->addOption( 'verbose', "Print activity " );
		$this->addOption( 'ignore-errors', "Continue after errors" );
		$this->addArg( 'lang', "The source wiki's language code (e.g. `en`)", true );
		$this->addArg( 'filename', "File with interlanguage links", true );

		parent::__construct();
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

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

		fgets( $file ); // We don't need the first line with column names.

		$current = null;
		$current_links = array();
		$count = 0;
		$ok = true;
		while( $link = fgetcsv( $file, 0, "\t" ) ) {
			if( $link[0] !== $current ) {
				if ( !empty( $current_links ) ) {
					$ok = $this->createItem( $current_links );

					if ( !$ok && !$this->ignore_errors ) {
						break;
					}
				}

				$count++;
				if ( ( $this->skip !== 0 ) && ( $this->skip > $count ) ) {
					continue;
				}
				if ( ( $this->only !== 0 ) && ( $this->only !== $count ) ) {
					if ($this->only < $count) {
						break;
					}
					continue;
				}

				$current = $link[0];
				$this->maybePrint( "Processing `$current`" );

				$current_links = array(
					$lang => $current
				);
			}

			$current_links[ $link[1] ] = $link[2];
		}

		if ( !$ok && !$this->ignore_errors ) {
			$this->doPrint( "Aborted!" );
			return;
		}

		if ( !empty( $current_links ) ) {
			$ok = $this->createItem( $current_links );
		}

		if ( $ok ) {
			$this->maybePrint( "Done." );
		}
	}

	/**
	 * @param Array $links An associative array of interlanguage links, mapping site IDs to page titles on that site.
	 *
	 * @return bool true if the item was created, false otherwise
	 */
	protected function createItem( $links ) {
		$item = \Wikibase\Item::newEmpty();

		foreach ( $links as $lang => $title ) {
			$name = strtr( $title, "_", " " );
			$label = preg_replace( '/ *\(.*\)$/u', '', $name );

			$item->setLabel( $lang, $label );
			$item->addSimpleSiteLink( new SimpleSiteLink( $lang . 'wiki',  $name ) );
		}

		$content = \Wikibase\ItemContent::newFromItem( $item );

		try {
			$status = $content->save( "imported", null, EDIT_NEW );

			if ( $status->isOK() ) {
				return true;
			}

			$this->doPrint( "ERROR: " . strtr( $status->getMessage(), "\n", " " ) );
		} catch ( MWException $ex ) {
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

$maintClass = 'importInterlang';
require_once( RUN_MAINTENANCE_IF_MAIN );
