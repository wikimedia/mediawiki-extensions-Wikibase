<?php

namespace Wikibase\Repo\Maintenance;

use Maintenance;
use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

require_once __DIR__ . '/EntityQuantityUnitRebuilder.php';

/**
 * @license GPL-2.0-or-later
 *
 * This script attempts to rebuild the Quantity unit URI:s in statements from one value to another.
 * https://phabricator.wikimedia.org/T312256
 *
 * Example:
 * php extensions/Wikibase/repo/maintenance/rebuildEntityQuantityUnit.php --from-value=example.localhost --to-value=example.com
 */
class RebuildEntityQuantityUnit extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuilds entity quantity unit values' );

		$this->addOption(
			'from-value',
			"Value to change from",
			true,
			true
		);

		$this->addOption(
			'to-value',
			"Value to change to",
			true,
			true
		);

		$this->addOption(
			'batch-size',
			"Number of entities to update per batch (Default: 250)",
			false,
			true
		);

		$this->addOption(
			'sleep',
			"Sleep time (in seconds) between every batch (Default: 10)",
			false,
			true
		);
	}

	public function execute() {
		$rebuilder = new EntityQuantityUnitRebuilder(
			$this->newEntityIdPager(),
			$this->getReporter(),
			$this->getErrorReporter(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb(),
			WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY ),
			(int)$this->getOption( 'batch-size', 250 ),
			(int)$this->getOption( 'sleep', 10 ),
			(string)$this->getOption( 'from-value' ),
			(string)$this->getOption( 'to-value' )
		);

		$rebuilder->rebuild();

		$this->output( "Done.\n" );
	}

	private function newEntityIdPager(): SqlEntityIdPager {
		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			WikibaseRepo::getEntityNamespaceLookup(),
			WikibaseRepo::getEntityIdLookup(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
		);

		$pager = $sqlEntityIdPagerFactory->newSqlEntityIdPager( [ 'property', 'item' ] );

		return $pager;
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

$maintClass = RebuildEntityQuantityUnit::class;
require_once RUN_MAINTENANCE_IF_MAIN;
