<?php

namespace Wikibase\Repo\Maintenance;

use Maintenance;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\Lib\Store\Sql\Terms\DatabaseInnerTermStoreCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseUsageCheckingTermStoreCleaner;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 */
class RemoveDeletedItemsFromTermStore extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Remove deleted items from the term store." );

		// ex. --termInLangIds '123,234,456,567'
		$this->addOption( 'termInLangIds', 'Term in language ids (wbtl_id)', true, true );
		$this->setBatchSize( 100 );
	}

	public function execute() {
		$batchSize = $this->getBatchSize();
		$termInLangIds = $this->getOption( 'termInLangIds' );
		$termInLangIds = explode( ',', $termInLangIds );
		$loadBalancerFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$loadBalancer = $loadBalancerFactory->getMainLB();
		$logger = LoggerFactory::getInstance( 'Wikibase' );
		$innerTermStoreCleaner = new DatabaseInnerTermStoreCleaner( $logger );
		$cleaner = new DatabaseUsageCheckingTermStoreCleaner( $loadBalancer, $innerTermStoreCleaner );
		$termInLangIdsInChunks = array_chunk( $termInLangIds, $batchSize );

		foreach ( $termInLangIdsInChunks as $termInLangIdsInChunk ) {
			$cleaner->cleanTermInLangIds( $termInLangIdsInChunk );
			$loadBalancerFactory->waitForReplication();
		}

		$this->output( "Successfully removed items in term store" . "\n" );
	}

}

$maintClass = RemoveDeletedItemsFromTermStore::class;
require_once RUN_MAINTENANCE_IF_MAIN;
