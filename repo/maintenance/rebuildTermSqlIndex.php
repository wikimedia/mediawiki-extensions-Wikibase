<?php

namespace Wikibase;

use LoggedUpdateMaintenance;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Sql\TermSqlIndexBuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RebuildTermSqlIndex extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the term_full_entity_id column in the wb terms table.' );

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
		$this->addOption( 'from-id', "First row (page id) to start updating from", false, true );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 * @todo Should be a separate script, though also something that can run as a DB update
	 *
	 * @return bool
	 */
	public function doDBUpdates() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->error( "You need to have Wikibase enabled in order to use this "
				. "maintenance script!\n\n", 1 );
		}

		$builder = $this->getTermIndexBuilder();
		$builder->rebuild();

		$this->output( "Done.\n" );

		return true;
	}

	private function getTermIndexBuilder() {
		$batchSize = (int)$this->getOption( 'batch-size', 1000 );
		$fromId = (int)$this->getOption( 'from-id', null );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$idParser = $wikibaseRepo->getEntityIdParser();
		$entityIdComposer = $wikibaseRepo->getEntityIdComposer();

		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$idParser
		);

		$logger = LoggerFactory::getInstance();

		$builder = new TermSqlIndexBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->getTermSqlIndex( $entityIdComposer, $idParser ),
			$sqlEntityIdPagerFactory,
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$this->getEntityTypes(),
			$this->getReporter(),
			$this->getErrorReporter(),
			$batchSize,
			$fromId
		);

		return $builder;
	}

	/**
	 * @return string[]
	 */
	private function getEntityTypes() {
		$entityType = $this->getOption( 'entity-type', null );
		$localEntityTypes = WikibaseRepo::getDefaultInstance()->getLocalEntityTypes();

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
	 * @return TermSqlIndex
	 */
	private function getTermSqlIndex(
		EntityIdComposer $entityIdComposer,
		EntityIdParser $entityIdParser
	) {
		$termSqlIndex = new TermSqlIndex(
			new StringNormalizer(),
			$entityIdComposer,
			$entityIdParser,
			false,
			'',
			true
		);

		$termSqlIndex->setReadFullEntityIdColumn( false );

		return $termSqlIndex;
	}

	/**
	 * @return ObservableMessageReporter
	 */
	private function getReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		return $reporter;
	}

	private function getErrorReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $message ) {
			$this->error( "[ERROR] $message" );
		} );

		return $reporter;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\RebuildTermSqlIndex';
	}

	/**
	 * Outputs a message via the output() method.
	 *
	 * @param string $msg
	 */
	public function report( $msg ) {
		$this->output( "$msg\n" );
	}

}

$maintClass = RebuildTermSqlIndex::class;
require_once RUN_MAINTENANCE_IF_MAIN;
