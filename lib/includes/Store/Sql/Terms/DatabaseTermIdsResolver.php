<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Term ID resolver using the normalized database schema.
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermIdsResolver implements TermIdsResolver {

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/** @var ILoadBalancer */
	private $lb;

	/** @var LoggerInterface */
	private $logger;

	/** @var IDatabase */
	private $dbr = null;

	/** @var string[] stash of data returned from the {@link TypeIdsResolver} */
	private $typeNames = [];

	/**
	 * @param TypeIdsResolver $typeIdsResolver
	 * @param ILoadBalancer $lb
	 */
	public function __construct(
		TypeIdsResolver $typeIdsResolver,
		ILoadBalancer $lb,
		LoggerInterface $logger = null
	) {
		$this->typeIdsResolver = $typeIdsResolver;
		$this->lb = $lb;
		$this->logger = $logger ?: new NullLogger();
	}

	public function resolveTermIds( array $termIds ): array {
		return $this->resolveGroupedTermIds( [ '' => $termIds ] )[''];
	}

	/*
	 * Term data is first read from the replica; if that returns less rows than we asked for, then
	 * there are some new rows in the master that were not yet replicated, and we fall back to the
	 * master if allowed. As the internal relations of the term store never change (for example, a
	 * term_in_lang row will never suddenly point to a different text_in_lang), a master fallback
	 * should never be necessary in any other case. However, callers need to consider where they
	 * got the list of term IDs they pass into this method from: if it’s from a replica, they may
	 * still see outdated data overall.
	 */
	public function resolveGroupedTermIds( array $groupedTermIds ): array {
		$groupedTerms = [];

		$groupNamesByTermIds = [];
		foreach ( $groupedTermIds as $groupName => $termIds ) {
			$groupedTerms[$groupName] = [];
			foreach ( $termIds as $termId ) {
				$groupNamesByTermIds[$termId][] = $groupName;
			}
		}
		$allTermIds = array_keys( $groupNamesByTermIds );

		if ( $allTermIds === [] ) {
			return $groupedTerms;
		}

		$this->logger->debug(
			'{method}: getting {termCount} rows from replica',
			[
				'method' => __METHOD__,
				'termCount' => count( $allTermIds ),
			]
		);
		$replicaResult = $this->selectTerms( $this->getDbr(), $allTermIds );
		$this->preloadTypes( $replicaResult );
		$replicaTermIds = [];

		foreach ( $replicaResult as $row ) {
			$replicaTermIds[] = $row->wbtl_id;
			foreach ( $groupNamesByTermIds[$row->wbtl_id] as $groupName ) {
				$this->addResultTerms( $groupedTerms[$groupName], $row );
			}
		}

		return $groupedTerms;
	}

	private function selectTerms( IDatabase $db, array $termIds ): IResultWrapper {
		return $db->select(
			[ 'wbt_term_in_lang', 'wbt_text_in_lang', 'wbt_text' ],
			[ 'wbtl_id', 'wbtl_type_id', 'wbxl_language', 'wbx_text' ],
			[
				'wbtl_id' => $termIds,
				// join conditions
				'wbtl_text_in_lang_id=wbxl_id',
				'wbxl_text_id=wbx_id',
			],
			__METHOD__
		);
	}

	private function preloadTypes( IResultWrapper $result ) {
		$typeIds = [];
		foreach ( $result as $row ) {
			$typeId = $row->wbtl_type_id;
			if ( !array_key_exists( $typeId, $this->typeNames ) ) {
				$typeIds[$typeId] = true;
			}
		}
		$this->typeNames += $this->typeIdsResolver->resolveTypeIds( array_keys( $typeIds ) );
	}

	private function addResultTerms( array &$terms, stdClass $row ) {
		$type = $this->lookupType( $row->wbtl_type_id );
		$lang = $row->wbxl_language;
		$text = $row->wbx_text;
		$terms[$type][$lang][] = $text;
	}

	private function lookupType( $typeId ) {
		$typeName = $this->typeNames[$typeId] ?? null;
		if ( $typeName === null ) {
			throw new InvalidArgumentException(
				'Type ID ' . $typeId . ' was requested but not preloaded!' );
		}
		return $typeName;
	}

	private function getDbr() {
		if ( $this->dbr === null ) {
			$this->dbr = $this->lb->getConnection( ILoadBalancer::DB_REPLICA );
		}

		return $this->dbr;
	}

}