<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Maintenance;

use Maintenance;
use MediaWiki\MediaWikiServices;
use Onoi\MessageReporter\ObservableMessageReporter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\IO\EntityIdReader;
use Wikibase\Repo\IO\LineReader;
use Wikibase\Repo\Store\Sql\ItemsPerSiteBuilder;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the wb_items_per_site table.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class RebuildItemsPerSite extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			'Rebuild the wb_items_per_site table for all existing items. ' .
			'This doesn\'t prune rows belonging to deleted Items, run pruneItemsPerSite.php first for that.'
		);

		$this->addOption( 'batch-size', "Number of rows to update per batch (100 by default)", false, true );
		$this->addOption(
			'first-page-id',
			'First page id to process, use 1 to start with the first page. ' .
			'Use --last-page-id + 1 to continue a previous run. Not compatible with --file.',
			false,
			true
		);
		$this->addOption(
			'last-page-id',
			'Page id of the last page to process. Not compatible with --file.',
			false,
			true
		);

		$this->addOption(
			'file',
			'File path for loading a list of item numeric ids, one numeric id per line. ',
			false,
			true
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}
		if ( !in_array( Item::ENTITY_TYPE, WikibaseRepo::getLocalEntitySource()->getEntityTypes() ) ) {
			$this->fatalError(
				"You can't run this maintenance script on foreign items!",
				1
			);
		}

		$batchSize = (int)$this->getOption( 'batch-size', 100 );

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			[ $this, 'report' ]
		);

		$mwServices = MediaWikiServices::getInstance();
		$siteLinkTable = new SiteLinkTable(
			'wb_items_per_site',
			false,
			WikibaseRepo::getRepoDomainDbFactory( $mwServices )->newRepoDb()
		);
		$store = WikibaseRepo::getStore( $mwServices );
		// Use an uncached EntityLookup here to avoid memory leaks
		$entityLookup = $store->getEntityLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY );
		$domainDB = WikibaseRepo::getRepoDomainDbFactory( $mwServices )->newRepoDb();

		$builder = new ItemsPerSiteBuilder(
			$siteLinkTable,
			$entityLookup,
			$store->getEntityPrefetcher(),
			$domainDB
		);

		$builder->setReporter( $reporter );
		$builder->setBatchSize( $batchSize );

		$file = $this->getOption( 'file' );
		if ( $file !== null ) {
			$stream = new EntityIdReader(
				new LineReader( fopen( $file, 'r' ) ),
				new ItemIdParser()
			);
			$stream->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );
		} else {
			$stream = new SqlEntityIdPager(
				WikibaseRepo::getEntityNamespaceLookup( $mwServices ),
				WikibaseRepo::getEntityIdLookup( $mwServices ),
				$domainDB,
				[ 'item' ]
			);

			$firstPageId = $this->getOption( 'first-page-id' );
			if ( $firstPageId ) {
				$stream->setPosition( intval( $firstPageId ) - 1 );
			}
			$lastPageId = $this->getOption( 'last-page-id' );
			if ( $lastPageId ) {
				$stream->setCutoffPosition( intval( $lastPageId ) );
			}
		}

		// Now <s>kill</s> fix the table
		$builder->rebuild( $stream );
	}

	/**
	 * Outputs a message vis the output() method.
	 */
	public function report( string $msg ): void {
		$this->output( "$msg\n" );
	}

}

$maintClass = RebuildItemsPerSite::class;
require_once RUN_MAINTENANCE_IF_MAIN;
