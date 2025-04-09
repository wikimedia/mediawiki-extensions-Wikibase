<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\Util\StatsMonitoring;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LikeValue;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * MatchingTermsLookup implementation in the new term store. Mostly used for search.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseMatchingTermsLookup implements MatchingTermsLookup {

	use StatsMonitoring;

	private TermsDomainDb $termsDb;

	private LoggerInterface $logger;

	private EntityIdComposer $entityIdComposer;

	public function __construct(
		TermsDomainDb $termsDb,
		EntityIdComposer $entityIdComposer,
		LoggerInterface $logger
	) {
		$this->termsDb = $termsDb;
		$this->entityIdComposer = $entityIdComposer;
		$this->logger = $logger;
	}

	/** @inheritDoc */
	public function getMatchingTerms(
		string $termText,
		string $entityType,
		$searchLanguage = null,
		$termType = null,
		array $options = []
	): array {
		$dbr = $this->getDbr();
		$queryBuilder = $this->buildQuery( $dbr, $termText, $entityType, $searchLanguage, $termType, $options );
		$queryResults = $queryBuilder->caller( __METHOD__ )->fetchResultSet();

		$this->incrementForQuery( 'MatchingTermsLookup_getMatchingTerms' );

		return $this->buildTermResult( $queryResults );
	}

	/**
	 * @param IReadableDatabase $dbr Used for query construction and selects
	 * @param string $termText
	 * @param string $entityType
	 * @param string|string[]|null $searchLanguage
	 * @param string|string[]|null $termType
	 * @param array $options
	 *
	 * @return SelectQueryBuilder
	 */
	private function buildQuery(
		IReadableDatabase $dbr,
		string $termText,
		string $entityType,
		$searchLanguage = null,
		$termType = null,
		array $options = []
	): SelectQueryBuilder {
		$options = array_merge(
			[
				'caseSensitive' => true,
				'prefixSearch' => false,
			],
			$options
		);
		// TODO: Fix case insensitive: T242644

		$queryBuilder = $dbr->newSelectQueryBuilder();

		$queryBuilder->select( [ 'wbtl_id', 'wbtl_type_id', 'wbxl_language', 'wbx_text' ] );

		if ( $entityType === 'item' ) {
			$queryBuilder->select( 'wbit_item_id' )
				->from( 'wbt_item_terms' )
				->join( 'wbt_term_in_lang', null, 'wbit_term_in_lang_id=wbtl_id' );
		} elseif ( $entityType === 'property' ) {
			$queryBuilder->select( 'wbpt_property_id' )
				->from( 'wbt_property_terms' )
				->join( 'wbt_term_in_lang', null, 'wbpt_term_in_lang_id=wbtl_id' );
		} else {
			throw new InvalidArgumentException( 'Unknown entity type for search: ' . $entityType );
		}

		$queryBuilder->join( 'wbt_text_in_lang', null, 'wbtl_text_in_lang_id=wbxl_id' )
			->join( 'wbt_text', null, 'wbxl_text_id=wbx_id' );

		if ( $searchLanguage ) {
			$queryBuilder->where( [ 'wbxl_language' => $searchLanguage ] );
			if ( is_array( $searchLanguage ) ) {
				$orderByExpressions = array_map(
					fn( $lang ) => "wbxl_language={$dbr->addQuotes( $lang )}",
					// need to reverse the order as ordering via the same column multiple times
					array_reverse( $searchLanguage )
				);
				$queryBuilder->orderBy( implode( ', ', $orderByExpressions ) );
			}
		}

		if ( $options['prefixSearch'] ) {
			$queryBuilder->where( $dbr->expr(
				'wbx_text',
				IExpression::LIKE,
				new LikeValue( $termText, $dbr->anyString() )
			) );
		} else {
			$queryBuilder->where( [ 'wbx_text' => $termText ] );
		}

		if ( $termType ) {
			$termType = is_array( $termType ) ? $termType : [ $termType ];
			$queryBuilder->where( [ 'wbtl_type_id' => array_map( fn ( $t ) => TermTypeIds::TYPE_IDS[$t], $termType ) ] );
		}

		if ( isset( $options['LIMIT'] ) && $options['LIMIT'] > 0 ) {
			// @phan-suppress-next-line PhanTypeMismatchArgument False positive
			$queryBuilder->limit( $options['LIMIT'] );
		}

		if ( isset( $options['OFFSET'] ) && $options['OFFSET'] > 0 ) {
			// @phan-suppress-next-line PhanTypeMismatchArgument False positive
			$queryBuilder->offset( $options['OFFSET'] );
		}

		return $queryBuilder;
	}

	/**
	 * Modifies the provided terms to use the field names expected by the interface
	 * rather than the table field names. Also ensures the values are of the correct type.
	 *
	 * @return TermIndexEntry[]
	 */
	private function buildTermResult( IResultWrapper $results ): array {
		$matchingTerms = [];
		foreach ( $results as $result ) {
			$typeId = (int)$result->wbtl_type_id;
			$matchingTerms[] = new TermIndexEntry( [
				'entityId' => $this->getEntityId( $result ),
				'termType' => array_flip( TermTypeIds::TYPE_IDS )[$typeId],
				'termLanguage' => $result->wbxl_language,
				'termText' => $result->wbx_text,
			] );
		}

		return $matchingTerms;
	}

	private function getEntityId( object $termRow ): ?EntityId {
		if ( isset( $termRow->wbpt_property_id ) ) {
			return $this->entityIdComposer->composeEntityId(
				'property', $termRow->wbpt_property_id
			);
		} elseif ( isset( $termRow->wbit_item_id ) ) {
			return $this->entityIdComposer->composeEntityId(
				'item', $termRow->wbit_item_id
			);
		} else {
			return null;
		}
	}

	private function getDbr(): IReadableDatabase {
		return $this->termsDb->getReadConnection();
	}
}
