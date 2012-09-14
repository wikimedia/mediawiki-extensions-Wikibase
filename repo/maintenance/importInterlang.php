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
	protected $skip = 0;
	protected $only = 0;

	public function __construct() {
		$this->mDescription = "Import interlanguage links in Wikidata.\n\nThe links may be created by extractInterlang.sql";

		$this->addOption( 'skip', "Skip number of entries in the import file" );
		$this->addOption( 'only', "Only import the specific entry from the import file" );
		$this->addOption( 'verbose', "Print API requests and responses" );
		$this->addOption( 'ignore-errors', "Ignore API errors" );
		$this->addArg( 'lang', "The source wiki's language code", true );
		$this->addArg( 'filename', "File with interlanguage links", true );

		parent::__construct();
	}

	public function execute() {
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
		while( $link = fgetcsv( $file, 0, "\t" ) ) {
			try {
				if( $link[0] !== $current ) {
					if ( !empty( $current_links ) ) {
						$this->createItem( $current_links );
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
			} catch( importInterlangException $e ) {
				if( !$this->ignore_errors ) {
					throw $e;
				}
				if( $this->verbose ) {
					echo "Error: " . $e->getMessage() . "\n";
				}
			}
		}

		if ( !empty( $current_links ) ) {
			$this->createItem( $current_links );
		}

		echo "Done!\n";
	}

	protected function createItem( $links ) {
		$item = \Wikibase\ItemObject::newEmpty();

		foreach ( $links as $lang => $title ) {
			$name = strtr( $title, "_", " " );
			$label = preg_replace( '/ *\(.*\)$/u', '', $name );

			$item->setLabel( $lang, $label );

			$siteLink = \Wikibase\SiteLink::newFromText( $lang . 'wiki',  $name );

			if ( $siteLink ) {
				$item->addSiteLink( $siteLink );
			}
		}

		$content = \Wikibase\ItemContent::newFromItem( $item );

		try {
			$status = $content->save( "imported", null, EDIT_NEW );

			if ( !$status->isOK() ) {
				$this->doPrint( "ERROR: " . strtr( $status->getMessage(), "\n", " " ) );
			}
		} catch ( MWException $ex ) {
			$this->doPrint( "ERROR: " . strtr( $ex->getMessage(), "\n", " " ) );
		}
	}

	/**
	 * Print a scalar, array or object if --verbose option is set.
	 */
	protected function maybePrint( $a ) {
		if( $this->verbose ) {
			$this->doPrint( $a );
		}
	}

	/**
	 * Print a scalar, array or object
	 */
	protected function doPrint( $a ) {
		if( is_bool( $a ) ) {
			echo $a? "true\n": "false\n";
		} elseif( is_scalar( $a ) ) {
			echo trim( strval( $a ) ) . "\n";
		} else {
			print_r( $a );
		}
	}

}

$maintClass = 'importInterlang';
require_once( RUN_MAINTENANCE_IF_MAIN );
