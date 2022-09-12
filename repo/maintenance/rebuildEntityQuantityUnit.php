<?php

namespace Wikibase\Repo\Maintenance;

use ExtensionRegistry;
use Maintenance;
use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterItemLookup;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterPropertyLookup;
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
 * This script attemtps to rebuild the Quantity unit URI:s in statements from one hostname to another.
 *
 * You can run it using the --all parameter, this will loop through each property / item.
 * Without this parameter the script uses a rather slow SQL query to figure out which entities might need updating.
 *
 * Example:
 * WBS_DOMAIN=derphub.wbaas.localhost php extensions/Wikibase/repo/maintenance/wbstack/rebuildEntityQuantityUnit.php --from-host=wbaas.localhost --to-host=wbaas-2.localhost
 */
class RebuildEntityQuantityUnit extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuilds entity quantity unit uris' );

		$this->addOption(
			'all',
			"Loop through every item / property",
			false,
			true
		);

		$this->addOption(
			'from-id',
			"First row (page id) to start updating from",
			false,
			true
		);

		$this->addOption(
			'from-host',
			"Hostname to change from (Default: 'wiki.opencura.com')",
			true,
			true
		);

		$this->addOption(
			'to-host',
			"Hostname to change to (Default: 'wikibase.cloud')",
			true,
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
	}

	public function execute() {

		$rebuilder = new EntityQuantityUnitRebuilder(
			$this->newEntityIdPager(),
			$this->getReporter(),
			$this->getErrorReporter(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb(),
			new LegacyAdapterPropertyLookup(
				WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY )
			),
			new LegacyAdapterItemLookup(
				WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY )
			),
			(int)$this->getOption( 'batch-size', 250 ),
			(int)$this->getOption( 'sleep', 10 ),
			(string)$this->getOption( 'from-host', 'wiki.opencura.com' ),
			(string)$this->getOption( 'to-host', 'wikibase.cloud' ),
			$this->getOption( 'all', false )
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

		$fromId = $this->getOption( 'from-id' );

		if ( $fromId !== null ) {
			if( $this->getOption( 'all') !== false ) {
				$this->fatalError( "--from-id only works with --all=true option" );
			}

			$pager->setPosition( (int)$fromId - 1 );
		}

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
