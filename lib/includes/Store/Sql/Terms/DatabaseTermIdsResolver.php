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
		$replicaResult = $this->selectTermsById( $allTermIds );
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

	/**
	 * Resolves terms by joining internal term ids table against another external table
	 * to allow maximum optimization to the user over how many queries would be performed.
	 *
	 *
	 * @param string $joinTable
	 * @param string $joinColumnName Column name in $joinTable that stores term ids to join on
	 * @param string $groupColumn Resolved terms will be grouped by values
	 *	of this column in $joinTable.
	 * @param array $conditions
	 *
	 * @return array[] keys of returned array are the distinct values of $groupColumn, and values
	 *	will be term arrays containing terms per type per language.
	 *  Example, given group column contains 1, 2 and 3 as distinct values:
	 * 	[
	 *		1 => [
	 *			'label' => [ 'en' => [ 'some label' ], ... ],
	 *			'alias' => [ 'en' => [ 'alias', 'another alias', ... ], ... ],
	 *			...
	 *  	],
	 *		2 => [
	 *			'label' => [ 'en' => [ 'another label' ], ... ],
	 *			...
	 *  	],
	 *		3 => [
	 *			'description' => [ 'en' => [ 'just a description' ], ... ],
	 *			...
	 *  	]
	 *  ]
	 */
	public function resolveTermsViaJoin(
		$joinTable,
		$joinColumn,
		$groupColumn,
		array $conditions
	): array {
		$conditions[] = $this->getDbr()->addIdentifierQuotes( $joinColumn ) . ' = wbtl_id';

		$records = $this->selectTermsViaJoin(
			[ $joinTable ],
			[ $groupColumn ],
			$conditions
		);

		$this->preloadTypes( $records );

		$termsByKeyColumn = [];
		foreach ( $records as $record ) {
			if ( !isset ( $termsByKeyColumn[$record->$groupColumn] ) ) {
				$termsByKeyColumn[$record->$groupColumn] = [];
			}
			$this->addResultTerms( $termsByKeyColumn[$record->$groupColumn], $record );
		}

		return $termsByKeyColumn;
	}

	private function selectTermsById( array $termIds ): IResultWrapper {
		return $this->selectTermsViaJoin( [], [], [ 'wbtl_id' => $termIds ] );
	}

	private function selectTermsViaJoin(
		array $joinTables,
		array $columns,
		array $conditions
	): IResultWrapper {
		return $this->getDbr()->select(
			array_merge( [ 'wbt_term_in_lang', 'wbt_text_in_lang', 'wbt_text' ], $joinTables ),
			array_merge( [ 'wbtl_id', 'wbtl_type_id', 'wbxl_language', 'wbx_text' ], $columns ),
			array_merge( [
				'wbtl_text_in_lang_id=wbxl_id',
				'wbxl_text_id=wbx_id',
			], $conditions ),
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
