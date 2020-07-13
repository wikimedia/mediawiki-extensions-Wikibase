<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use IJobSpecification;
use JobQueueGroup;
use JobSpecification;
use MediaWiki\Hook\ArticleRevisionVisibilitySetHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use Title;
use TitleFactory;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * Hook handler that propagates changes to the visibility of an article's revisions
 * to clients.
 *
 * This schedules "ChangeVisibilityNotification" jobs on all client wikis (all as
 * some wikis might no longer be subscribed) which will handle this on the clients.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ArticleRevisionVisibilitySetHookHandler implements ArticleRevisionVisibilitySetHook {

	/**
	 * @var RevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var string[]
	 */
	private $localClientDatabases;

	/**
	 * @var bool
	 */
	private $propagateChangeVisibility;

	/**
	 * @var int
	 */
	private $clientRCMaxAge;

	/**
	 * @var int
	 */
	private $jobBatchSize;

	/**
	 * @var callable
	 */
	private $jobQueueGroupFactory;

	public function __construct(
		RevisionLookup $revisionLookup,
		EntityContentFactory $entityContentFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		TitleFactory $titleFactory,
		array $localClientDatabases,
		callable $jobQueueGroupFactory,
		bool $propagateChangeVisibility,
		int $clientRCMaxAge,
		int $jobBatchSize
	) {
		$this->revisionLookup = $revisionLookup;
		$this->entityContentFactory = $entityContentFactory;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->titleFactory = $titleFactory;
		$this->localClientDatabases = $localClientDatabases;
		$this->jobQueueGroupFactory = $jobQueueGroupFactory;
		$this->propagateChangeVisibility = $propagateChangeVisibility;
		$this->clientRCMaxAge = $clientRCMaxAge;
		$this->jobBatchSize = $jobBatchSize;
	}

	public static function newFromGlobalState() {
		$wbRepo = WikibaseRepo::getDefaultInstance();

		return new self(
			MediaWikiServices::getInstance()->getRevisionLookup(),
			$wbRepo->getEntityContentFactory(),
			$wbRepo->getEntityNamespaceLookup(),
			new TitleFactory(),
			$wbRepo->getSettings()->getSetting( 'localClientDatabases' ),
			'JobQueueGroup::singleton',
			$wbRepo->getSettings()->getSetting( 'propagateChangeVisibility' ),
			$wbRepo->getSettings()->getSetting( 'changeVisibilityNotificationClientRCMaxAge' ),
			$wbRepo->getSettings()->getSetting( 'changeVisibilityNotificationJobBatchSize' )
		);
	}

	/**
	 * @param Title $title
	 * @param int[] $ids
	 * @param int[][] $visibilityChangeMap
	 */
	public function onArticleRevisionVisibilitySet( $title, $ids, $visibilityChangeMap ): void {
		if ( !$this->propagateChangeVisibility ) {
			return;
		}
		// Check if $title is in a wikibase namespace
		if ( !$this->entityNamespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
			return;
		}

		/** @var RevisionRecord[][] $revisionsByNewBits */
		$revisionsByNewBits = [];
		foreach ( $this->getEligibleRevisionsById( $ids ) as $id => $revision ) {
			$revisionsByNewBits[$visibilityChangeMap[$id]['newBits']][] = $revision;
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

		if ( !$jobSpecifications ) {
			return;
		}
		foreach ( $this->localClientDatabases as $clientDatabase ) {
			$this->newJobQueueGroup( $clientDatabase )->push( $jobSpecifications );
		}
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
			$revision->getId(),
			$revision->getParentId()
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
	 * @param string $wikiId
	 *
	 * @return JobQueueGroup
	 */
	private function newJobQueueGroup( string $wikiId ): JobQueueGroup {
		return call_user_func( $this->jobQueueGroupFactory, $wikiId );
	}

	/**
	 * JSON encode the given RepoRevisionIdentifiers
	 *
	 * @param RepoRevisionIdentifier[] $revisionIdentifiers
	 *
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
