<?php

namespace Wikibase\Repo\Maintenance;

use ExtensionRegistry;
use Maintenance;
use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterItemLookup;
use Wikibase\Repo\RangeTraversable;
use Wikibase\Repo\Store\ItemTermsRebuilder;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 */
class RebuildItemTerms extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuilds item terms from primary persistence' );

		$this->addOption(
			'from-id',
			"Lowest (numeric) item id that should be updated (Default: 1)",
			false,
			true
		);

		$this->addOption(
			'to-id',
			"Highest (numeric) item id that should be updated (Default: no limit, manual script termination required)",
			false,
			true
		);

		$this->addOption(
			'batch-size',
			"Number of rows to update per batch (Default: 250)",
			false,
			true
		);

		$this->addOption(
			'sleep',
			"Sleep time (in seconds) between every batch (Default: 10)",
			false,
			true
		);
		$this->addOption(
			'file',
			'File path for loading a list of item numeric ids, one numeric id per line. ' .
				'Works if from-id and to-id are not set',
			false,
			true
		);
	}

	public function execute() {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseRepository' ) ) {
			$this->fatalError(
				"You need to have Wikibase enabled in order to use this "
				. "maintenance script!\n\n",
				1
			);
		}
		if ( !in_array( Item::ENTITY_TYPE, WikibaseRepo::getLocalEntitySource()->getEntityTypes() ) ) {
			$this->fatalError(
				"You can't run this maintenance script on foreign items!",
				1
			);
		}

		if (
			$this->getOption( 'from-id' ) === null &&
			$this->getOption( 'to-id' ) === null &&
			$this->getOption( 'file' ) !== null
		) {
			$iterator = $this->newItemIdIteratorFromFile( $this->getOption( 'file' ) );
		} else {
			$iterator = $this->newItemIdIterator();
		}

		$rebuilder = new ItemTermsRebuilder(
			WikibaseRepo::getTermStoreWriterFactory()->newItemTermStoreWriter(),
			$iterator,
			$this->getReporter(),
			$this->getErrorReporter(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb(),
			new LegacyAdapterItemLookup(
				WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY )
			),
			(int)$this->getOption( 'batch-size', 250 ),
			(int)$this->getOption( 'sleep', 10 )
		);

		$rebuilder->rebuild();

		$this->output( "Done.\n" );
	}

	private function newItemIdIteratorFromFile( $file ): \Iterator {
		$itemIds = file_get_contents( $file );
		$itemIds = explode( "\n", $itemIds );

		foreach ( $itemIds as $itemId ) {
			// Ignore empty lines
			if ( !$itemId ) {
				continue;
			}
			yield ItemId::newFromNumber( (int)$itemId );
		}
	}

	private function newItemIdIterator(): \Iterator {
		$idRange = new RangeTraversable(
			(int)$this->getOption( 'from-id', 1 ),
			$this->getToIdOrHighestId()
		);

		foreach ( $idRange as $integer ) {
			yield ItemId::newFromNumber( $integer );
		}
	}

	private function getToIdOrHighestId(): int {
		if ( $this->hasOption( 'to-id' ) ) {
			return (int)$this->getOption( 'to-id' );
		}

		$highestId = WikibaseRepo::getRepoDomainDbFactory()
			->newRepoDb()
			->connections()
			->getReadConnection()
			->newSelectQueryBuilder()
			->select( 'id_value' )
			->from( 'wb_id_counters' )
			->where( [ 'id_type' => 'wikibase-item' ] )
			->caller( __METHOD__ )->fetchRow();
		return (int)$highestId->id_value;
	}

	private function getReporter(): MessageReporter {
		return new CallbackMessageReporter(
			function ( $message ) {
				$this->output( "$message\n" );
			}
		);
	}

	private function getErrorReporter(): MessageReporter {
		return new CallbackMessageReporter(
			function ( $message ) {
				$this->error( "[ERROR] $message" );
			}
		);
	}

}

$maintClass = RebuildItemTerms::class;
require_once RUN_MAINTENANCE_IF_MAIN;
