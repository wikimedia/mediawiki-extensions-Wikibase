<?php

namespace Wikibase\Repo\ChangeModification;

use IJobSpecification;
use JobSpecification;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use Title;
use TitleFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * Job that propagates changes to the visibility of an article's revisions to clients.
 *
 * This job is scheduled by ArticleRevisionVisibilitySetHookHandler and schedules
 * {@link \Wikibase\Client\ChangeModification\ChangeVisibilityNotificationJob ChangeVisibilityNotification}
 * jobs on all client wikis (all as some wikis might no longer be subscribed)
 * which will handle this on the clients.
 *
 * @license GPL-2.0-or-later
 */
class DispatchChangeVisibilityNotificationJob extends DispatchChangeModificationNotificationJob {

	/** @var int[] */
	private $revisionIds;

	/** @var int[][] */
	private $visibilityChangeMap;

	/** @var int */
	private $jobBatchSize;

	/** @var RevisionLookup */
	private $revisionLookup;

	/** @var TitleFactory */
	private $titleFactory;

	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'DispatchChangeVisibilityNotification', $title, $params );

		Assert::parameter( isset( $params['revisionIds'] ), '$params', '$params[\'revisionIds\'] not set.' );
		Assert::parameter( isset( $params['visibilityChangeMap'] ), '$params', '$params[\'revisionIds\'] not set.' );
		Assert::parameterElementType( 'integer', $params['revisionIds'], '$params[\'revisionIds\']' );
		Assert::parameterElementType( 'array', $params['visibilityChangeMap'], '$params[\'visibilityChangeMap\']' );

		$this->revisionIds = $params['revisionIds'];
		$this->visibilityChangeMap = $params['visibilityChangeMap'];
	}

	protected function initFromGlobalState( MediaWikiServices $mwServices, WikibaseRepo $repo ) {
		parent::initFromGlobalState( $mwServices, $repo );

		$repoSettings = WikibaseRepo::getSettings( $mwServices );
		$this->jobBatchSize = $repoSettings->getSetting( 'changeVisibilityNotificationJobBatchSize' );
		$this->revisionLookup = $mwServices->getRevisionLookup();
		$this->titleFactory = $mwServices->getTitleFactory();
	}

	protected function getChangeModificationNotificationJobs( EntityId $entityId ): array {
		/** @var RevisionRecord[][] $revisionsByNewBits */
		$revisionsByNewBits = [];
		foreach ( $this->getEligibleRevisionsById( $this->revisionIds ) as $id => $revision ) {
			$revisionsByNewBits[$this->visibilityChangeMap[$id]['newBits']][] = $revision;
		}

		$jobSpecifications = [];
		foreach ( $revisionsByNewBits as $newBits => $revisions ) {
			$revisionIdentifiers = $this->getRepoRevisionIdentifiers( $revisions );

			foreach ( array_chunk( $revisionIdentifiers, $this->jobBatchSize ) as $revisionIdentifierChunk ) {
				$jobSpecifications[] = $this->createJobSpecification(
					$this->revisionIdentifiersToJson( $revisionIdentifierChunk ),
					$newBits
				);
			}
		}

		return $jobSpecifications;
	}

	/**
	 * Gets all revisions with the given ids and returns those that are relevant
	 * (=> no older than self::clientRCMaxAge).
	 *
	 * @param int[] $ids
	 *
	 * @return RevisionRecord[] Indexed by revision id
	 */
	private function getEligibleRevisionsById( array $ids ): array {
		$revisions = [];

		foreach ( $ids as $id ) {
			$revision = $this->revisionLookup->getRevisionById( $id );
			if ( !$revision ) {
				continue;
			}

			$timeDiff = time() - $this->getRevisionAge( $revision );
			if ( $timeDiff > $this->clientRCMaxAge ) {
				continue;
			}

			$revisions[$id] = $revision;
		}

		return $revisions;
	}

	/**
	 * @param RevisionRecord[] $revisions
	 *
	 * @return RepoRevisionIdentifier[]
	 */
	private function getRepoRevisionIdentifiers( array $revisions ): array {
		$revisionIdentifiers = [];

		foreach ( $revisions as $revision ) {
			$repoRevisionIdentifier = $this->newRepoRevisionIdentifier( $revision );
			if ( $repoRevisionIdentifier ) {
				$revisionIdentifiers[] = $repoRevisionIdentifier;
			}
		}

		return $revisionIdentifiers;
	}

	/**
	 * @param RevisionRecord $revision
	 *
	 * @return RepoRevisionIdentifier|null
	 */
	private function newRepoRevisionIdentifier( RevisionRecord $revision ): ?RepoRevisionIdentifier {
		$title = $this->titleFactory->newFromID( $revision->getPageId() );
		if ( !$title ) {
			return null;
		}
		$entityId = $this->entityContentFactory->getEntityIdForTitle( $title );
		if ( !$entityId ) {
			return null;
		}
		return new RepoRevisionIdentifier(
			$entityId->getSerialization(),
			$revision->getTimestamp(),
			$revision->getId()
		);
	}

	/**
	 * @param RevisionRecord $revision
	 *
	 * @return int Age of the revision in seconds
	 */
	private function getRevisionAge( RevisionRecord $revision ): int {
		return intval(
			( new ConvertibleTimestamp( $revision->getTimestamp() ) )->getTimestamp( TS_UNIX )
		);
	}

	/**
	 * Returns a new job for updating a client.
	 *
	 * @param string $revisionIdentifiersJson
	 * @param int $visibilityBitFlag
	 *
	 * @return IJobSpecification
	 */
	private function createJobSpecification( string $revisionIdentifiersJson, int $visibilityBitFlag ): IJobSpecification {
		return new JobSpecification(
			'ChangeVisibilityNotification',
			[
				'revisionIdentifiersJson' => $revisionIdentifiersJson,
				'visibilityBitFlag' => $visibilityBitFlag,
			]
		);
	}
}
