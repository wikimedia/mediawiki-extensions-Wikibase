<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\ChangeModification;

use BatchRowIterator;
use Job;
use JobSpecification;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Title;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\ILBFactory;

/**
 * Job for fetching and dispatching RepoRevisionIdentifiers marked for deletion for client databases
 *
 * @license GPL-2.0-or-later
 */
class DispatchChangeDeletionNotificationJob extends Job {

	/** @var int */
	private $batchSize;

	/** @var int */
	private $archivedRevisionCount;

	/** @var int */
	private $pageId;

	/** @var ILBFactory */
	private $loadBalancerFactory;

	/** @var int */
	private $clientRCMaxAge;

	/** @var EntityContentFactory */
	private $entityContentFactory;

	/** @var string[] */
	private $localClientDatabases;

	/** @var LoggerInterface */
	private $logger;

	/** @var callable */
	private $jobGroupFactory;

	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'DispatchChangeDeletionNotification', $title, $params );

		Assert::parameter( isset( $params['pageId'] ), '$params', '$params[\'pageId\'] not set.' );
		Assert::parameter( isset( $params['archivedRevisionCount'] ), '$params', '$params[\'archivedRevisionCount\'] not set.' );
		Assert::parameterType( 'integer', $params['pageId'], '$params[\'pageId\']' );
		Assert::parameterType( 'integer', $params['archivedRevisionCount'], '$params[\'archivedRevisionCount\']' );

		$this->pageId = $params['pageId'];
		$this->archivedRevisionCount = $params['archivedRevisionCount'];

		$this->initRepoJobServicesFromGlobalState();
	}

	private function initRepoJobServicesFromGlobalState() {
		$mwServices = MediaWikiServices::getInstance();
		$repo = WikibaseRepo::getDefaultInstance();

		$this->batchSize = $mwServices->getMainConfig()->get( 'UpdateRowsPerQuery' );
		$this->clientRCMaxAge = $repo->getSettings()->getSetting( 'deleteNotificationClientRCMaxAge' );
		$this->localClientDatabases = $repo->getSettings()->getSetting( 'localClientDatabases' );

		$this->initServices(
			$mwServices->getDBLoadBalancerFactory(),
			$repo->getEntityContentFactory(),
			$repo->getLogger(),
			'JobQueueGroup::singleton'
		);
	}

	/**
	 * @param ILBFactory $loadBalancerFactory
	 * @param EntityContentFactory $entityContentFactory
	 * @param LoggerInterface $logger
	 * @param callable $jobGroupFactory
	 */
	public function initServices(
		ILBFactory $loadBalancerFactory,
		EntityContentFactory $entityContentFactory,
		LoggerInterface $logger,
		callable $jobGroupFactory
	) {
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->entityContentFactory = $entityContentFactory;
		$this->logger = $logger;
		$this->jobGroupFactory = $jobGroupFactory;
	}

	/**
	 * @param RepoRevisionIdentifier[] $revisionIdentifiers
	 * @return string JSON
	 */
	private function revisionIdentifiersToJson( array $revisionIdentifiers ): string {
		return json_encode(
			array_map(
				function ( RepoRevisionIdentifier $revisionIdentifier ) {
					return $revisionIdentifier->toArray();
				},
				$revisionIdentifiers
			)
		);
	}

	private function dispatchClientDeletionJob( array $repoRevisionIdentifiers ) {
		$jobSpecification = new JobSpecification(
			'ChangeDeletionNotification',
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( $repoRevisionIdentifiers ),
			]
		);

		foreach ( $this->localClientDatabases as $clientDatabase ) {
			call_user_func( $this->jobGroupFactory, $clientDatabase )->push( $jobSpecification );
		}
	}

	private function getArchiveRows( int &$processed, int &$staleRecords, string $entityIdSerialization ) {
		$dbr = $this->loadBalancerFactory
			->getMainLB()
			->getConnectionRef( DB_REPLICA );

		$iterator = new BatchRowIterator(
			$dbr,
			'archive',
			[ 'ar_id' ],
			$this->batchSize
		);

		$thresholdTime = time() - $this->clientRCMaxAge;
		$staleRecords = (int)$dbr->selectField(
			'archive', 'COUNT(*)',
			[
				'ar_namespace' => $this->getTitle()->getNamespace(),
				'ar_title' => $this->getTitle()->getDBkey(),
				'ar_page_id' => $this->pageId,
				'ar_timestamp < ' . $dbr->addQuotes( $dbr->timestamp( $thresholdTime ) ),
			], __METHOD__
		);

		if ( $staleRecords === $this->archivedRevisionCount ) {
			return [];
		}

		$iterator->setFetchColumns( [ 'ar_rev_id', 'ar_timestamp' ] );
		$iterator->addConditions( [
			'ar_page_id' => $this->pageId,
			'ar_title' => $this->title->getDBkey(),
			'ar_namespace' => $this->title->getNamespace(),
			'ar_timestamp >= ' . $dbr->addQuotes( $dbr->timestamp( $thresholdTime ) ),
		] );

		$identifiers = [];
		foreach ( $iterator as $batch ) {
			foreach ( $batch as $row ) {
				$identifiers[] = new RepoRevisionIdentifier(
					$entityIdSerialization,
					$row->ar_timestamp,
					intval( $row->ar_rev_id )
				);
				$processed++;
			}
		}
		return $identifiers;
	}

	/**
	 * @inheritDoc
	 */
	public function run() {
		if ( $this->localClientDatabases === [] ) {
			return true;
		}
		$entityId = $this->entityContentFactory->getEntityIdForTitle( $this->getTitle() );
		if ( $entityId === null ) {
			$this->logger->warning( "Job should not be queued for non-entity pages." );
			return false;
		}

		$processed = 0;
		$staleRecords = 0;
		$repoRevisionIdentifiers = $this->getArchiveRows( $processed, $staleRecords, $entityId->getSerialization() );

		if ( $staleRecords === $this->archivedRevisionCount ) {
			$this->logger->info( "All archive records are too old. Aborting." );
			return true;
		}

		if ( $processed !== $this->archivedRevisionCount - $staleRecords ) {
			$this->logger->warning( __METHOD__ . ': processed {processed} rows but archived {archived} revisions for {entityId}', [
				'processed' => $processed,
				'archived' => $this->archivedRevisionCount - $staleRecords,
				'entityId' => $entityId->getSerialization()
			] );
		}

		$this->dispatchClientDeletionJob( $repoRevisionIdentifiers );

		return true;
	}
}
