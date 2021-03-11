<?php

namespace Wikibase\Repo\Maintenance;

use Maintenance;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\Sql\Terms\DatabaseInnerTermStoreCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseUsageCheckingTermStoreCleaner;
use Wikibase\Lib\Store\Sql\Terms\NormalizedTermStorageMapping;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\ILoadBalancer;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 */
class RemoveDeletedItemsFromTermStore extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Remove deleted items from the term store." );

		// ex. --itemIds '123,234,456,567' or --itemIds 'Q123,Q234,Q456,Q567'
		$this->addOption( 'itemIds', 'Item IDs', true, true );
		$this->setBatchSize( 100 );
	}

	public function execute() {
		$batchSize = $this->getBatchSize();
		/** @var ItemId[] $itemIds */
		$itemIds = array_map(
			function ( $id ) {
				if ( is_numeric( $id ) ) {
					$id = "Q$id";
				}
				return new ItemId( $id );
			},
			explode( ',', $this->getOption( 'itemIds' ) )
		);

		$services = MediaWikiServices::getInstance();
		$lbFactory = $services->getDBLoadBalancerFactory();
		$loadBalancer = $lbFactory->getMainLB();
		$dbr = $loadBalancer->getConnection( ILoadBalancer::DB_REPLICA );
		$dbw = $loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		$entityExistenceChecker = WikibaseRepo::getEntityExistenceChecker( $services );
		$logger = WikibaseRepo::getLogger( $services );
		$innerTermStoreCleaner = new DatabaseInnerTermStoreCleaner( $logger );
		$cleaner = new DatabaseUsageCheckingTermStoreCleaner( $loadBalancer, $innerTermStoreCleaner );
		$mapping = NormalizedTermStorageMapping::factory( Item::ENTITY_TYPE );

		// delete wbt_item_terms rows, collecting term_in_lang IDs
		$termInLangIds = [];
		foreach ( array_chunk( $itemIds, $batchSize ) as $itemIdsBatch ) {
			$exist = $entityExistenceChecker->existsBatch( $itemIdsBatch );
			$deletedNumericItemIds = [];
			foreach ( $itemIdsBatch as $itemId ) {
				if ( !$exist[$itemId->getSerialization()] ) {
					$deletedNumericItemIds[] = $itemId->getNumericId();
				}
			}
			if ( $deletedNumericItemIds === [] ) {
				continue;
			}

			while ( true ) {
				Assert::invariant( count( $deletedNumericItemIds ) <= $batchSize,
					'not selecting rows of more than $batchSize items at once' );
				$res = $dbr->select(
					$mapping->getTableName(),
					[
						'id' => $mapping->getRowIdColumn(),
						'term_in_lang_id' => $mapping->getTermInLangIdColumn(),
					],
					[ $mapping->getEntityIdColumn() => $deletedNumericItemIds ],
					__METHOD__,
					[ 'LIMIT' => $batchSize ]
				);

				$rowIds = [];
				foreach ( $res as $row ) {
					$rowIds[] = $row->id;
					$termInLangIds[$row->term_in_lang_id] = null;
				}

				if ( $rowIds !== [] ) {
					Assert::invariant( count( $rowIds ) <= $batchSize,
						'not deleting more than $batchSize rows at once' );
					$dbw->delete(
						$mapping->getTableName(),
						[ $mapping->getRowIdColumn() => $rowIds ],
						__METHOD__
					);
					$lbFactory->waitForReplication();
				} else {
					break;
				}
			}
		}

		// clean those term_in_lang IDs ($cleaner checks that they’re unused)
		foreach ( array_chunk( array_keys( $termInLangIds ), $batchSize ) as $termInLangIdsChunk ) {
			$cleaner->cleanTermInLangIds( $termInLangIdsChunk );
			$lbFactory->waitForReplication();
		}

		$this->output( "Successfully removed items in term store\n" );
	}

}

$maintClass = RemoveDeletedItemsFromTermStore::class;
require_once RUN_MAINTENANCE_IF_MAIN;
