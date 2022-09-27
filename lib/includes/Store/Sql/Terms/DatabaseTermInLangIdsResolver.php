<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\Sql\Terms\Util\StatsdMonitoring;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Term in lang ID resolver using the normalized database schema.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseTermInLangIdsResolver implements TermInLangIdsResolver {

	use StatsdMonitoring;

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/** @var TypeIdsLookup */
	private $typeIdsLookup;

	/** @var RepoDomainDb */
	private $db;

	/** @var LoggerInterface */
	private $logger;

	/** @var string[] stash of data returned from the {@link TypeIdsResolver} */
	private $typeNames = [];

	public function __construct(
		TypeIdsResolver $typeIdsResolver,
		TypeIdsLookup $typeIdsLookup,
		RepoDomainDb $db,
		?LoggerInterface $logger = null
	) {
		$this->typeIdsResolver = $typeIdsResolver;
		$this->typeIdsLookup = $typeIdsLookup;
		$this->db = $db;
		$this->logger = $logger ?: new NullLogger();
	}

	public function resolveTermInLangIds(
		array $termInLangIds,
		array $types = null,
		array $languages = null
	): array {
		return $this->resolveGroupedTermInLangIds( [ '' => $termInLangIds ], $types, $languages )[''];
	}

	public function resolveGroupedTermInLangIds(
		array $groupedTermInLangIds,
		array $types = null,
		array $languages = null
	): array {
		$groupedTerms = [];

		$groupNamesByTermInLangIds = [];
		foreach ( $groupedTermInLangIds as $groupName => $termInLangIds ) {
			$groupedTerms[$groupName] = [];
			foreach ( $termInLangIds as $termInLangId ) {
				$groupNamesByTermInLangIds[$termInLangId][] = $groupName;
			}
		}
		$allTermInLangIds = array_keys( $groupNamesByTermInLangIds );

		if ( $allTermInLangIds === [] || $types === [] || $languages === [] ) {
			return $groupedTerms;
		}

		$this->logger->debug(
			'{method}: getting {termCount} rows from replica',
			[
				'method' => __METHOD__,
				'termCount' => count( $allTermInLangIds ),
			]
		);

		$result = $this->selectTermsViaJoin(
			[], [], [ 'wbtl_id' => $allTermInLangIds ], $types, $languages );
		$this->preloadTypes( $result );

		foreach ( $result as $row ) {
			foreach ( $groupNamesByTermInLangIds[$row->wbtl_id] as $groupName ) {
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
	 * @param string $joinColumn Column name in $joinTable that stores term ids to join on
	 * @param string $groupColumn Resolved terms will be grouped by values
	 *	of this column in $joinTable.
	 * @param array $conditions
	 * @param array|null $types
	 * @param array|null $languages
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
		array $conditions,
		array $types = null,
		array $languages = null
	): array {
		$joinConditions = [ $joinTable => [ 'JOIN', $this->getDbr()->addIdentifierQuotes( $joinColumn ) . ' = wbtl_id' ] ];
		$records = $this->selectTermsViaJoin(
			[ $joinTable ],
			[ $groupColumn ],
			$conditions,
			$types,
			$languages,
			$joinConditions
		);

		$this->preloadTypes( $records );

		$termsByKeyColumn = [];
		foreach ( $records as $record ) {
			if ( !isset( $termsByKeyColumn[$record->$groupColumn] ) ) {
				$termsByKeyColumn[$record->$groupColumn] = [];
			}
			$this->addResultTerms( $termsByKeyColumn[$record->$groupColumn], $record );
		}

		return $termsByKeyColumn;
	}

	private function selectTermsViaJoin(
		array $joinTables,
		array $columns,
		array $conditions,
		array $types = null,
		array $languages = null,
		array $joinConditions = []
	): IResultWrapper {
		$this->incrementForQuery( 'DatabaseTermIdsResolver_selectTermsViaJoin' );
		if ( $types !== null ) {
			$conditions['wbtl_type_id'] = $this->lookupTypeIds( $types );
		}
		if ( $languages !== null ) {
			$conditions['wbxl_language'] = $languages;
		}

		return $this->getDbr()->select(
			array_merge( [ 'wbt_term_in_lang', 'wbt_text_in_lang', 'wbt_text' ], $joinTables ),
			array_merge( [ 'wbtl_id', 'wbtl_type_id', 'wbxl_language', 'wbx_text' ], $columns ),
			$conditions,
			__METHOD__,
			[],
			array_merge( [
				'wbt_text_in_lang' => [ 'JOIN', 'wbtl_text_in_lang_id=wbxl_id' ],
				'wbt_text' => [ 'JOIN', 'wbxl_text_id=wbx_id' ],
			], $joinConditions )
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
		$type = $this->lookupTypeName( $row->wbtl_type_id );
		$lang = $row->wbxl_language;
		$text = $row->wbx_text;
		$terms[$type][$lang][] = $text;
	}

	private function lookupTypeName( $typeId ) {
		$typeName = $this->typeNames[$typeId] ?? null;
		if ( $typeName === null ) {
			throw new InvalidArgumentException(
				'Type ID ' . $typeId . ' was requested but not preloaded!' );
		}
		return $typeName;
	}

	private function lookupTypeIds( array $typeNames ) {
		return $this->typeIdsLookup->lookupTypeIds( $typeNames );
	}

	private function getDbr() {
		return $this->db->connections()->getReadConnection();
	}

}
