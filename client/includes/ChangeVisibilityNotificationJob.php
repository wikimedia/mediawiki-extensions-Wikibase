<?php

declare( strict_types = 1 );

namespace Wikibase\Client;

use Job;
use MediaWiki\MediaWikiServices;
use Title;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Changes\RepoRevisionIdentifierFactory;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Job for notifying a client wiki of a batch of revision visibility changes on the repository.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ChangeVisibilityNotificationJob extends Job {

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var RepoRevisionIdentifier[]
	 */
	private $revisionIdentifiers;

	/**
	 * Constructs a ChangeVisibilityNotificationJob for the repo revisions given.
	 *
	 * @param ILoadBalancer $loadBalancer
	 * @param array $params Contains the name of the repo, revisionIdentifiersJson to redact
	 *   and the visibilityBitFlag to set.
	 */
	public function __construct( ILoadBalancer $loadBalancer, array $params = [] ) {
		parent::__construct( 'ChangeVisibilityNotification', $params );

		Assert::parameterType( 'array', $params, '$params' );
		Assert::parameter(
			is_string( $params['revisionIdentifiersJson'] ) && $params['revisionIdentifiersJson'] !== '',
			'$params',
			'$params[\'revisionIdentifiersJson\'] must be a non-empty string.'
		);

		$this->revisionIdentifiers = $this->unpackRevisionIdentifiers( $params['revisionIdentifiersJson'] );

		Assert::parameter(
			isset( $params['visibilityBitFlag'] ),
			'$params',
			'$params[\'visibilityBitFlag\'] not set.'
		);

		$this->loadBalancer = $loadBalancer;
	}

	public static function newFromGlobalState( Title $unused, array $params ) {
		return new self(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			$params
		);
	}

	/**
	 * @param string $revisionIdentifiersJson
	 *
	 * @return RepoRevisionIdentifier[]
	 */
	private function unpackRevisionIdentifiers( string $revisionIdentifiersJson ): array {
		$repoRevisionFactory = new RepoRevisionIdentifierFactory();
		$revisionIdentifiersArray = json_decode( $revisionIdentifiersJson, true );
		$revisionIdentifiers = [];
		foreach ( $revisionIdentifiersArray as $revisionIdentifierArray ) {
			$revisionIdentifiers[] = $repoRevisionFactory->newFromArray( $revisionIdentifierArray );
		}

		return $revisionIdentifiers;
	}

	/**
	 * @return bool success
	 */
	public function run() {
		$visibilityBitFlag = $this->params['visibilityBitFlag'];
		$toRedact = $this->getRecentChangesToRedact( $visibilityBitFlag );
		if ( $toRedact === [] ) {
			return true;
		}

		$this->redactRecentChanges( $toRedact, $visibilityBitFlag );
		return true;
	}

	/**
	 * @param int $visibilityBitFlag
	 * @return int[] rc_ids to redact
	 */
	private function getRecentChangesToRedact( int $visibilityBitFlag ): array {
		$revisionIdentifiersByEntityId = $this->getGroupedRepoRevisionIdentifiers();

		$toRedact = [];
		foreach ( $revisionIdentifiersByEntityId as $entityIdSerialization => $revisionIdentifiers ) {
			$candidateResults = $this->selectCandidateResults(
				$entityIdSerialization,
				$revisionIdentifiers,
				$visibilityBitFlag
			);

			$toRedact = array_merge(
				$toRedact,
				$this->filterCandidateResults( $candidateResults, $revisionIdentifiers )
			);
		}

		return $toRedact;
	}

	/**
	 * @param int[] $rcIds
	 * @param int $visibilityBitFlag Target rc_deleted bitflag (ignore entries that already have it)
	 */
	private function redactRecentChanges( array $rcIds, int $visibilityBitFlag ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		foreach ( array_chunk( $rcIds, 100 ) as $rcIdBatch ) {
			$dbw->update(
				'recentchanges',
				[ 'rc_deleted' => $visibilityBitFlag ],
				[ 'rc_id' => $rcIdBatch ],
				__METHOD__
			);
		}
	}

	/**
	 * @param string $entityIdSerialization
	 * @param RepoRevisionIdentifier[] $revisionIdentifiers
	 * @param int $visibilityBitFlag Target rc_deleted bitflag (ignore rows that already have it)
	 *
	 * @return IResultWrapper
	 */
	private function selectCandidateResults(
		string $entityIdSerialization,
		array $revisionIdentifiers,
		int $visibilityBitFlag
	): IResultWrapper {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$rcParamPattern = $dbr->buildLike(
			$dbr->anyString(),
			'"', $entityIdSerialization, '"',
			$dbr->anyString()
		);

		$timestamps = [];
		foreach ( $revisionIdentifiers as $revisionIdentifier ) {
			$timestamps[] = $revisionIdentifier->getRevisionTimestamp();
		}

		// For extra clarity: $visibilityBitFlag is int.
		Assert::parameterType( 'integer', $visibilityBitFlag, '$visibilityBitFlag' );
		return $dbr->select(
			'recentchanges',
			[ 'rc_id', 'rc_params' ],
			[
				'rc_timestamp' => $timestamps,
				"rc_deleted != $visibilityBitFlag",
				"rc_params $rcParamPattern",
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE
			],
			__METHOD__
		);
	}

	/**
	 * @param IResultWrapper $candidateResults
	 * @param RepoRevisionIdentifier[] $revisionIdentifiers
	 *
	 * @return int[]
	 */
	private function filterCandidateResults( IResultWrapper $candidateResults, array $revisionIdentifiers ): array {
		if ( $candidateResults->numRows() === 0 ) {
			return [];
		}

		$needleMap = [];
		foreach ( $revisionIdentifiers as $revisionIdentifier ) {
			$needleMap[$revisionIdentifier->getRevisionId() . '#' . $revisionIdentifier->getRevisionParentId()] = true;
		}

		$results = [];
		foreach ( $candidateResults as $rc ) {
			$metadata = $this->readRecentChangeParams( $rc->rc_params );

			$parent_id = $metadata[ 'parent_id' ];
			$rev_id = $metadata[ 'rev_id' ];

			if ( isset( $needleMap["$rev_id#$parent_id"] ) ) {
				$results[] = (int)$rc->rc_id;
			}
		}

		return $results;
	}

	/**
	 * @return RepoRevisionIdentifier[][]
	 */
	private function getGroupedRepoRevisionIdentifiers(): array {
		$revisionIdentifiersByEntityId = [];
		foreach ( $this->revisionIdentifiers as $revisionIdentifier ) {
			$entityIdSerialization = $revisionIdentifier->getEntityIdSerialization();
			$revisionIdentifiersByEntityId[$entityIdSerialization][] = $revisionIdentifier;
		}

		return $revisionIdentifiersByEntityId;
	}

	/**
	 * Extracts the metadata array from the value of an rc_params field.
	 *
	 * @param array|string $rc_params
	 *
	 * @return array
	 */
	private function readRecentChangeParams( $rc_params ): array {
		if ( is_string( $rc_params ) ) {
			$rc_params = unserialize( $rc_params );
		}

		if ( is_array( $rc_params ) && array_key_exists( 'wikibase-repo-change', $rc_params ) ) {
			$metadata = $rc_params['wikibase-repo-change'];
		} else {
			$metadata = [];
		}

		$metadata = array_merge( [ 'parent_id' => 0, 'rev_id' => 0 ], $metadata );
		return $metadata;
	}

}
