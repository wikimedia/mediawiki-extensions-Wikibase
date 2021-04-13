<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeModification;

use IJobSpecification;
use Job;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Content\ContentHandlerEntityIdLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Base class for repo jobs that dispatch client
 * {@link \Wikibase\Client\ChangeModification\ChangeModificationNotificationJob change modification notification jobs}
 * to all client wikis.
 *
 * @license GPL-2.0-or-later
 */
abstract class DispatchChangeModificationNotificationJob extends Job {

	/** @var int */
	protected $clientRCMaxAge;

	/** @var string[] */
	private $localClientDatabases;

	/** @var EntityIdLookup */
	private $entityIdLookup;

	/** @var LoggerInterface */
	protected $logger;

	/** @var callable */
	private $jobGroupFactory;

	public function __construct( string $jobName, Title $title, array $params ) {
		parent::__construct( $jobName, $title, $params );

		$this->initFromGlobalState( MediaWikiServices::getInstance() );
	}

	protected function initFromGlobalState( MediaWikiServices $mwServices ) {
		$repoSettings = WikibaseRepo::getSettings( $mwServices );
		$this->clientRCMaxAge = $repoSettings->getSetting( 'deleteNotificationClientRCMaxAge' );
		$this->localClientDatabases = $repoSettings->getSetting( 'localClientDatabases' );

		$this->initServices(
			new ContentHandlerEntityIdLookup( WikibaseRepo::getEntityContentFactory( $mwServices ) ),
			WikibaseRepo::getLogger( $mwServices ),
			'JobQueueGroup::singleton'
		);
	}

	/**
	 * @param EntityIdLookup $entityIdLookup
	 * @param LoggerInterface $logger
	 * @param callable $jobGroupFactory
	 */
	public function initServices(
		EntityIdLookup $entityIdLookup,
		LoggerInterface $logger,
		callable $jobGroupFactory
	) {
		$this->entityIdLookup = $entityIdLookup;
		$this->logger = $logger;
		$this->jobGroupFactory = $jobGroupFactory;
	}

	/**
	 * @param RepoRevisionIdentifier[] $revisionIdentifiers
	 * @return string JSON
	 */
	protected function revisionIdentifiersToJson( array $revisionIdentifiers ): string {
		return json_encode(
			array_map(
				function ( RepoRevisionIdentifier $revisionIdentifier ) {
					return $revisionIdentifier->toArray();
				},
				$revisionIdentifiers
			)
		);
	}

	/**
	 * @param IJobSpecification[] $jobSpecifications
	 */
	private function dispatchChangeModificationNotificationJobs( array $jobSpecifications ): void {
		foreach ( $this->localClientDatabases as $clientDatabase ) {
			call_user_func( $this->jobGroupFactory, $clientDatabase )->push( $jobSpecifications );
		}
	}

	/**
	 * @param EntityId $entityId
	 * @return IJobSpecification[]
	 */
	abstract protected function getChangeModificationNotificationJobs( EntityId $entityId ): array;

	/**
	 * @inheritDoc
	 */
	public function run(): bool {
		if ( empty( $this->localClientDatabases ) ) {
			return true;
		}
		$entityId = $this->entityIdLookup->getEntityIdForTitle( $this->getTitle() );

		if ( $entityId === null ) {
			$this->logger->warning( "Job should not be queued for non-entity pages." );
			return true;
		}

		$jobSpecifications = $this->getChangeModificationNotificationJobs( $entityId );

		if ( $jobSpecifications === [] ) {
			return true;
		}

		$this->dispatchChangeModificationNotificationJobs( $jobSpecifications );

		return true;
	}
}
