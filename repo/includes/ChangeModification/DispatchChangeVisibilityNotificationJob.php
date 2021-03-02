<?php

namespace Wikibase\Repo\ChangeModification;

use IJobSpecification;
use JobSpecification;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use Title;
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

	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'DispatchChangeVisibilityNotification', $title, $params );

		Assert::parameter( isset( $params['revisionIds'] ), '$params', '$params[\'revisionIds\'] not set.' );
		Assert::parameter( isset( $params['visibilityChangeMap'] ), '$params', '$params[\'revisionIds\'] not set.' );
		Assert::parameterElementType( 'integer', $params['revisionIds'], '$params[\'revisionIds\']' );
		Assert::parameterElementType( 'array', $params['visibilityChangeMap'], '$params[\'visibilityChangeMap\']' );

		$this->revisionIds = $params['revisionIds'];
		$this->visibilityChangeMap = $params['visibilityChangeMap'];
	}

	protected function initFromGlobalState( MediaWikiServices $mwServices ) {
		parent::initFromGlobalState( $mwServices );

		$repoSettings = WikibaseRepo::getSettings( $mwServices );
		$this->jobBatchSize = $repoSettings->getSetting( 'changeVisibilityNotificationJobBatchSize' );
		$this->revisionLookup = $mwServices->getRevisionLookup();
	}

	protected function getChangeModificationNotificationJobs( EntityId $entityId ): array {
		/** @var RepoRevisionIdentifier[][] $revisionsByNewBits */
		$revisionsByNewBits = [];
		foreach ( $this->getEligibleRevisionIdentifiersById( $entityId, $this->revisionIds ) as $id => $revision ) {
			$revisionsByNewBits[$this->visibilityChangeMap[$id]['newBits']][] = $revision;
		}

		$jobSpecifications = [];
		foreach ( $revisionsByNewBits as $newBits => $revisionIdentifiers ) {
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
	 * Gets the timestamps of all revisions with the given IDs
	 * and returns repo revision identifiers for those that are relevant
	 * (=> no older than self::clientRCMaxAge).
	 *
	 * @param EntityId $entityId
	 * @param int[] $ids
	 *
	 * @return RepoRevisionIdentifier[] Indexed by revision id
	 */
	private function getEligibleRevisionIdentifiersById( EntityId $entityId, array $ids ): array {
		$revisions = [];

		foreach ( $ids as $id ) {
			$timestamp = $this->revisionLookup->getTimestampFromId( $id );
			if ( !$timestamp ) {
				continue;
			}

			$timeDiff = time() - $this->getRevisionAge( $timestamp );
			if ( $timeDiff > $this->clientRCMaxAge ) {
				continue;
			}

			$revisions[$id] = new RepoRevisionIdentifier(
				$entityId->getSerialization(),
				$timestamp,
				$id
			);
		}

		return $revisions;
	}

	/**
	 * @param string $timestamp
	 *
	 * @return int Age of the revision in seconds
	 */
	private function getRevisionAge( string $timestamp ): int {
		return intval(
			( new ConvertibleTimestamp( $timestamp ) )->getTimestamp( TS_UNIX )
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
