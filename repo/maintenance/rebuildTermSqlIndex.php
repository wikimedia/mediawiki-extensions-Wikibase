<?php

namespace Wikibase;

use Maintenance;
use MediaWiki\MediaWikiServices;
use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Sql\TermSqlIndexBuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RebuildTermSqlIndex extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuild the index in the wb terms table ' .
			'(among other things populating term_full_entity_id).' );

		$this->addOption(
			'batch-size', "Number of rows to update per batch (Default: 1000)",
			false,
			true
		);
		$this->addOption(
			'entity-type', "Only rebuild terms for specified entity type (e.g. 'item', 'property')",
			false,
			true
		);
		$this->addOption(
			'deduplicate-terms', 'Remove duplicate entries in the index (might slow the run down).'
				. 'Redundant when rebuild-all-terms option is specified.'
		);
		$this->addOption(
			'rebuild-all-terms', 'Rebuilds all terms of the entity (requires loading data of each processed entity)'
		);
		$this->addOption( 'from-id', "First row (page id) to start updating from", false, true );
		$this->addOption( 'sleep', "Sleep time (in seconds) between every batch", false, true );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->error( "You need to have Wikibase enabled in order to use this "
				. "maintenance script!\n\n", 1 );
		}

		$builder = $this->getTermIndexBuilder();
		$builder->rebuild();

		$this->output( "Done.\n" );
	}

	private function getTermIndexBuilder() {
		$batchSize = (int)$this->getOption( 'batch-size', 1000 );
		$fromId = $this->getOption( 'from-id', null );
		$deduplicateTerms = $this->getOption( 'deduplicate-terms', false );
		$rebuildAllEntityTerms = $this->getOption( 'rebuild-all-terms', false );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$idParser = $wikibaseRepo->getEntityIdParser();
		$entityIdComposer = $wikibaseRepo->getEntityIdComposer();
		$repoSettings = $wikibaseRepo->getSettings();

		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getEntityIdLookup()
		);

		$termIndex = $this->getTermSqlIndex( $entityIdComposer, $idParser, $repoSettings );

		$builder = new TermSqlIndexBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$termIndex,
			$sqlEntityIdPagerFactory,
			$wikibaseRepo->getEntityRevisionLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY ),
			$this->getEntityTypes(),
			$this->getOption( 'sleep', 10 )
		);
		$builder->setProgressReporter( $this->getReporter() );
		$builder->setErrorReporter( $this->getErrorReporter() );
		$builder->setBatchSize( $batchSize );

		if ( $fromId !== null ) {
			$builder->setFromId( (int)$fromId );
		}
		$builder->setRemoveDuplicateTerms( $deduplicateTerms );
		$builder->setRebuildAllEntityTerms( $rebuildAllEntityTerms );

		return $builder;
	}

	/**
	 * @return string[]
	 */
	private function getEntityTypes() {
		$entityType = $this->getOption( 'entity-type', null );
		$localEntityTypes = WikibaseRepo::getDefaultInstance()->getLocalEntityTypes();

		$entityTypes = $localEntityTypes;
		if ( $entityType !== null ) {
			if ( !in_array( $entityType, $localEntityTypes ) ) {
				$this->error( "Unknown entity type: \"$entityType\"\n", 1 );
			}
			$entityTypes = [ $entityType ];
		}

		return $entityTypes;
	}

	/**
	 * @param EntityIdComposer $entityIdComposer
	 * @param EntityIdParser $entityIdParser
	 * @param SettingsArray $settings
	 *
	 * @return TermSqlIndex
	 */
	private function getTermSqlIndex(
		EntityIdComposer $entityIdComposer,
		EntityIdParser $entityIdParser,
		SettingsArray $settings
	) {
		$dataAccessSettings = new DataAccessSettings(
			$settings->getSetting( 'maxSerializedEntitySize' ),
			$settings->getSetting( 'useTermsTableSearchFields' ),
			$settings->getSetting( 'forceWriteTermsTableSearchFields' ),
			DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION,
			$settings->getSetting( 'tmpPropertyTermsMigrationStage' ) >= MIGRATION_WRITE_NEW
		);

		$termSqlIndex = new TermSqlIndex(
			new StringNormalizer(),
			$entityIdComposer,
			$entityIdParser,
			new UnusableEntitySource(),
			$dataAccessSettings,
			false,
			''
		);
		$termSqlIndex->setUseSearchFields( $settings->getSetting( 'useTermsTableSearchFields' ) );
		$termSqlIndex->setForceWriteSearchFields( $settings->getSetting( 'forceWriteTermsTableSearchFields' ) );
		return $termSqlIndex;
	}

	private function getReporter(): MessageReporter {
		return new CallbackMessageReporter(
			function( $message ) {
				$this->output( "$message\n" );
			}
		);
	}

	private function getErrorReporter(): MessageReporter {
		return new CallbackMessageReporter(
			function( $message ) {
				$this->error( "[ERROR] $message" );
			}
		);
	}

}

$maintClass = RebuildTermSqlIndex::class;
require_once RUN_MAINTENANCE_IF_MAIN;
