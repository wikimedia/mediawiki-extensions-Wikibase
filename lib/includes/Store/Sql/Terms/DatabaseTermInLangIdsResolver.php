<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\Store\Sql\Terms\Util\StatsMonitoring;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Term in lang ID resolver using the normalized database schema.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseTermInLangIdsResolver implements TermInLangIdsResolver {

	use StatsMonitoring;

	/** @var TermsDomainDb */
	private $termsDb;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		TermsDomainDb $termsDb,
		?LoggerInterface $logger = null
	) {
		$this->termsDb = $termsDb;
		$this->logger = $logger ?: new NullLogger();
	}

	public function resolveTermInLangIds(
		array $termInLangIds,
		?array $types = null,
		?array $languages = null
	): array {
		return $this->resolveGroupedTermInLangIds( [ '' => $termInLangIds ], $types, $languages )[''];
	}

	public function resolveGroupedTermInLangIds(
		array $groupedTermInLangIds,
		?array $types = null,
		?array $languages = null
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

		$result = $this->newSelectQueryBuilder( $types, $languages )
			->where( [ 'wbtl_id' => $allTermInLangIds ] )
			->caller( __METHOD__ )->fetchResultSet();

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
		?array $types = null,
		?array $languages = null
	): array {
		$records = $this->newSelectQueryBuilder( $types, $languages )
			->select( $groupColumn )
			->join( $joinTable, null, $this->getDbr()->addIdentifierQuotes( $joinColumn ) . ' = wbtl_id' )
			->where( $conditions )
			->caller( __METHOD__ )->fetchResultSet();

		$termsByKeyColumn = [];
		foreach ( $records as $record ) {
			if ( !isset( $termsByKeyColumn[$record->$groupColumn] ) ) {
				$termsByKeyColumn[$record->$groupColumn] = [];
			}
			$this->addResultTerms( $termsByKeyColumn[$record->$groupColumn], $record );
		}

		return $termsByKeyColumn;
	}

	private function newSelectQueryBuilder(
		?array $types,
		?array $languages
	): SelectQueryBuilder {
		$this->incrementForQuery( 'DatabaseTermIdsResolver_selectTermsViaJoin' ); // old method name kept for b/c
		$queryBuilder = $this->getDbr()->newSelectQueryBuilder()
			->select( [ 'wbtl_id', 'wbtl_type_id', 'wbxl_language', 'wbx_text' ] )
			->from( 'wbt_term_in_lang' )
			->join( 'wbt_text_in_lang', null, 'wbtl_text_in_lang_id=wbxl_id' )
			->join( 'wbt_text', null, 'wbxl_text_id=wbx_id' );
		if ( $types !== null ) {
			$queryBuilder->where( [ 'wbtl_type_id' => array_values( $this->lookupTypeIds( $types ) ) ] );
		}
		if ( $languages !== null ) {
			$queryBuilder->where( [ 'wbxl_language' => $languages ] );
		}
		return $queryBuilder;
	}

	private function addResultTerms( array &$terms, stdClass $row ) {
		$type = $this->lookupTypeName( $row->wbtl_type_id );
		$lang = $row->wbxl_language;
		$text = $row->wbx_text;
		$terms[$type][$lang][] = $text;
	}

	private function lookupTypeName( string $typeId ): string {
		$typeName = array_flip( TermTypeIds::TYPE_IDS )[$typeId] ?? null;
		if ( $typeName === null ) {
			throw new InvalidArgumentException( 'Unknown type ID: ' . $typeId );
		}
		return $typeName;
	}

	private function lookupTypeIds( array $typeNames ): array {
		return array_intersect_key( TermTypeIds::TYPE_IDS, array_flip( $typeNames ) );
	}

	private function getDbr(): IReadableDatabase {
		return $this->termsDb->getReadConnection();
	}

}
