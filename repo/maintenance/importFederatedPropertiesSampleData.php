<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Maintenance;

use Maintenance;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../mediawiki';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Import data from a data file to a Federated Properties Wikibase instance
 * For an example file format to import, see repo/tests/phpunit/data/maintenance/federatedPropertiesTestDataFile.tsv
 *
 * @license GPL-2.0-or-later
 */
class ImportFederatedPropertiesSampleData extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Create new Items for federated properties test system." );
		$this->addOption( 'dataFile', 'Data source for the new Items to create', true, true );
		$this->addOption(
			'delimiter',
			'Character separating data across columns in the data file. default is tab.',
			false,
			true
		);
	}

	public function execute() {
		$dataFile = $this->getOption( 'dataFile' );
		$lineDelimiter = $this->getOption( 'delimiter', "\t" );

		$entityStore = WikibaseRepo::getEntityStore();
		$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );

		foreach ( $this->getDataToImport( $dataFile ) as $dataline ) {
			if ( $dataline !== '' ) {
				$dataArray = explode( $lineDelimiter, $dataline );
				$this->storeNewItemWithTermData( $dataArray, $entityStore, $user );
			}
		}
		$this->output( 'Created new Items from data file:' . $dataFile . "\n" );
	}

	public function storeNewItemWithTermData( array $data, EntityStore $entityStore, User $user ) {
		$item = new Item();
		$item->setLabel( 'en', $data[0] );
		$item->setDescription( 'en', $data[1] );

		$entityStore->saveEntity( $item, 'Create new Item', $user, EDIT_NEW );

		return $item;
	}

	public function getDataToImport( $dataFileLocation ) {
		$data = file_get_contents( $dataFileLocation );
		if ( $data === '' ) {
			$this->fatalError( 'Error: ' . $dataFileLocation . ' is an empty file' . "\n" );
		}

		return explode( "\n", $data );
	}
}

$maintClass = ImportFederatedPropertiesSampleData::class;
require_once RUN_MAINTENANCE_IF_MAIN;
