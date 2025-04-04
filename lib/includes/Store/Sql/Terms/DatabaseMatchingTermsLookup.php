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
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LikeValue;

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

	/**
	 * @inheritDoc
	 */
	public function getMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		if ( !$criteria ) {
			return [];
		}

		$dbr = $this->getDbr();

		$results = $this->criteriaToQueryResults( $dbr, $criteria, $termType, $entityType, $options );

		$this->incrementForQuery( 'MatchingTermsLookup_getMatchingTerms' );

		if ( isset( $options['LIMIT'] ) && $options['LIMIT'] > 0 ) {
			return $this->buildTermResult( $results, $options['LIMIT'] );
		} else {
			return $this->buildTermResult( $results );
		}
	}

	/**
	 * @param IReadableDatabase $dbr Used for query construction and selects
	 * @param TermIndexSearchCriteria[] $criteria
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *
	 * @return IResultWrapper[]
	 */
	private function criteriaToQueryResults(
		IReadableDatabase $dbr,
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	): array {
		$termQueries = [];

		foreach ( $criteria as $mask ) {
			if ( $entityType === null ) {
				$termQueries[] = $this->getTermMatchQueries( $dbr, $mask, 'item', $termType, $options );
				$termQueries[] = $this->getTermMatchQueries( $dbr, $mask, 'property', $termType, $options );
			} elseif ( is_array( $entityType ) ) {
				foreach ( $entityType as $entityTypeCase ) {
					$termQueries[] = $this->getTermMatchQueries( $dbr, $mask, $entityTypeCase, $termType, $options );
				}
			} else {
				$termQueries[] = $this->getTermMatchQueries( $dbr, $mask, $entityType, $termType, $options );
			}
		}

		return $termQueries;
	}

	/**
	 * @param IReadableDatabase $dbr Used for query construction and selects
	 * @param TermIndexSearchCriteria $mask
	 * @param string $entityType
	 * @param string|string[]|null $termType
	 * @param array $options
	 * @return IResultWrapper
	 */
	private function getTermMatchQueries(
		IReadableDatabase $dbr,
		TermIndexSearchCriteria $mask,
		string $entityType,
		$termType = null,
		array $options = []
	): IResultWrapper {
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

		$language = $mask->getLanguage();
		if ( $language !== null ) {
			$queryBuilder->where( [ 'wbxl_language' => $language ] );
		}

		$text = $mask->getText();
		if ( $text !== null ) {
			if ( $options['prefixSearch'] ) {
				$queryBuilder->where( $dbr->expr(
					'wbx_text',
					IExpression::LIKE,
					new LikeValue( $text, $dbr->anyString() )
				) );
			} else {
				$queryBuilder->where( [ 'wbx_text' => $text ] );
			}
		}

		if ( $mask->getTermType() !== null ) {
			$termType = $mask->getTermType();
		}
		if ( $termType !== null ) {
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

		return $queryBuilder->caller( __METHOD__ )->fetchResultSet();
	}

	/**
	 * Modifies the provided terms to use the field names expected by the interface
	 * rather then the table field names. Also ensures the values are of the correct type.
	 *
	 * @param IResultWrapper[] $results
	 * @param int|null $limit
	 * @return TermIndexEntry[]
	 */
	private function buildTermResult( array $results, ?int $limit = null ): array {
		$matchingTerms = [];
		// Union in SQL doesn't have limit, we need to enforce it here
		$counter = 0;

		foreach ( $results as $result ) {
			foreach ( $result as $obtainedTerm ) {
				$counter += 1;
				$typeId = (int)$obtainedTerm->wbtl_type_id;
				$matchingTerms[] = new TermIndexEntry( [
					'entityId' => $this->getEntityId( $obtainedTerm ),
					'termType' => array_flip( TermTypeIds::TYPE_IDS )[$typeId],
					'termLanguage' => $obtainedTerm->wbxl_language,
					'termText' => $obtainedTerm->wbx_text,
				] );

				if ( $counter === $limit ) {
					return $matchingTerms;
				}
			}
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
