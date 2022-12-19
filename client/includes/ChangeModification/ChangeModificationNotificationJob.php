<?php

declare( strict_types = 1 );

namespace Wikibase\Client\ChangeModification;

use Job;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Changes\RepoRevisionIdentifierFactory;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Base class for Jobs handling modifications to a set of client changes (identified
 * by RepoRevisionIdentifiers).
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
abstract class ChangeModificationNotificationJob extends Job {

	/**
	 * @var ClientDomainDb
	 */
	protected $clientDb;

	/**
	 * @var RepoRevisionIdentifier[]
	 */
	private $revisionIdentifiers;

	/**
	 * @param string $jobName Name of this job.
	 * @param ClientDomainDb $clientDb
	 * @param array $params Contains the revisionIdentifiersJson to act upon.
	 */
	public function __construct( string $jobName, ClientDomainDb $clientDb, array $params = [] ) {
		parent::__construct( $jobName, $params );

		Assert::parameterType( 'array', $params, '$params' );
		Assert::parameter(
			is_string( $params['revisionIdentifiersJson'] ) && $params['revisionIdentifiersJson'] !== '',
			'$params',
			'$params[\'revisionIdentifiersJson\'] must be a non-empty string.'
		);

		$this->revisionIdentifiers = $this->unpackRevisionIdentifiers( $params['revisionIdentifiersJson'] );

		$this->clientDb = $clientDb;
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
		$relevantChanges = $this->getRelevantRecentChanges();
		if ( $relevantChanges === [] ) {
			return true;
		}

		$this->modifyChanges( $relevantChanges );

		return true;
	}

	/**
	 * @param int[] $relevantChanges Ids of changes relevant for this job.
	 * @return void
	 */
	abstract protected function modifyChanges( array $relevantChanges ): void;

	/**
	 * @return int[] Relevant rc_ids
	 */
	protected function getRelevantRecentChanges(): array {
		$revisionIdentifiersByEntityId = $this->getGroupedRepoRevisionIdentifiers();

		$toRedact = [];
		foreach ( $revisionIdentifiersByEntityId as $entityIdSerialization => $revisionIdentifiers ) {
			$candidateResults = $this->selectCandidateResults(
				$entityIdSerialization,
				$revisionIdentifiers
			);

			$toRedact = array_merge(
				$toRedact,
				$this->filterCandidateResults( $candidateResults, $revisionIdentifiers )
			);
		}

		return $toRedact;
	}

	/**
	 * @param string $entityIdSerialization
	 * @param RepoRevisionIdentifier[] $revisionIdentifiers
	 *
	 * @return IResultWrapper
	 */
	private function selectCandidateResults(
		string $entityIdSerialization,
		array $revisionIdentifiers
	): IResultWrapper {
		$dbr = $this->clientDb->connections()->getReadConnection();
		$rcParamPattern = $dbr->buildLike(
			$dbr->anyString(),
			'"', $entityIdSerialization, '"',
			$dbr->anyString()
		);

		$timestamps = [];
		foreach ( $revisionIdentifiers as $revisionIdentifier ) {
			$timestamps[] = $dbr->timestamp( $revisionIdentifier->getRevisionTimestamp() );
		}

		return $dbr->newSelectQueryBuilder()
			->select( [ 'rc_id', 'rc_params' ] )
			->from( 'recentchanges' )
			->where( [
				'rc_timestamp' => $timestamps,
				"rc_params $rcParamPattern",
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			] )
			->caller( __METHOD__ )
			->fetchResultSet();
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
		$ids = [];
		foreach ( $revisionIdentifiers as $revisionIdentifier ) {
			$ids[$revisionIdentifier->getRevisionId()] = true;
		}

		$results = [];
		foreach ( $candidateResults as $rc ) {
			$metadata = $this->readRecentChangeParams( $rc->rc_params );

			$rev_id = $metadata[ 'rev_id' ];

			if ( isset( $ids[$rev_id] ) ) {
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

		$metadata = array_merge( [ 'rev_id' => 0 ], $metadata );
		return $metadata;
	}

}
