<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use MediaWiki\Storage\NameTableAccessException;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\Util\StatsdMonitoring;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * MatchingTermsLookup implementation in the new term store. Mostly used for search.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseMatchingTermsLookup implements MatchingTermsLookup {

	use StatsdMonitoring;

	/** @var RepoDomainDb */
	private $repoDb;

	/** @var LoggerInterface */
	private $logger;

	/** @var TypeIdsAcquirer */
	private $typeIdsAcquirer;

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/** @var EntityIdComposer */
	private $entityIdComposer;

	public function __construct(
		RepoDomainDb $repoDb,
		TypeIdsAcquirer $typeIdsAcquirer,
		TypeIdsResolver $typeIdsResolver,
		EntityIdComposer $entityIdComposer,
		LoggerInterface $logger
	) {
		$this->repoDb = $repoDb;
		$this->typeIdsAcquirer = $typeIdsAcquirer;
		$this->typeIdsResolver = $typeIdsResolver;
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
		if ( empty( $criteria ) ) {
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
	 * @param IDatabase $dbr Used for query construction and selects
	 * @param TermIndexSearchCriteria[] $criteria
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *
	 * @return IResultWrapper[]
	 */
	private function criteriaToQueryResults(
		IDatabase $dbr,
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		$termQueries = [];

		foreach ( $criteria as $mask ) {
			if ( $entityType === null ) {
				$termQueries[] = $this->getTermMatchQueries( $dbr, $mask, 'item', $termType, $options );
				$termQueries[] = $this->getTermMatchQueries( $dbr, $mask, 'property', $termType, $options );
			} elseif ( is_array( $entityType ) === true ) {
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
	 * @param IDatabase $dbr Used for query construction and selects
	 * @param TermIndexSearchCriteria $mask
	 * @param string $entityType
	 * @param string|string[]|null $termType
	 * @param array $options
	 * @return IResultWrapper
	 */
	private function getTermMatchQueries(
		IDatabase $dbr,
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
				$queryBuilder->where( 'wbx_text ' . $dbr->buildLike( $text, $dbr->anyString() ) );
			} else {
				$queryBuilder->where( [ 'wbx_text' => $text ] );
			}
		}

		if ( $mask->getTermType() !== null ) {
			$termType = $mask->getTermType();
		}
		if ( $termType !== null ) {
			try {
				$queryBuilder->where( [
					'wbtl_type_id' => $this->typeIdsAcquirer->acquireTypeIds( [ $termType ] )[$termType],
				] );
			} catch ( NameTableAccessException $e ) {
				// Edge case: attempting to do a term lookup before the first insert of the respective term type. Unlikely to happen in
				// production, but annoying/confusing if it happens in tests.
				return new FakeResultWrapper( [] );
			}
		}

		if ( isset( $options['LIMIT'] ) && $options['LIMIT'] > 0 ) {
			$queryBuilder->limit( $options['LIMIT'] );
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
	private function buildTermResult( array $results, ?int $limit = null ) {
		$matchingTerms = [];
		// Union in SQL doesn't have limit, we need to enforce it here
		$counter = 0;

		foreach ( $results as $result ) {
			foreach ( $result as $obtainedTerm ) {
				$counter += 1;
				$typeId = (int)$obtainedTerm->wbtl_type_id;
				$matchingTerms[] = new TermIndexEntry( [
					'entityId' => $this->getEntityId( $obtainedTerm ),
					'termType' => $this->typeIdsResolver->resolveTypeIds( [ $typeId ] )[$typeId],
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

	/**
	 * @param object $termRow
	 *
	 * @return EntityId|null
	 */
	private function getEntityId( $termRow ) {
		if ( isset( $termRow->wbpt_property_id ) ) {
			return $this->entityIdComposer->composeEntityId(
				'', 'property', $termRow->wbpt_property_id
			);
		} elseif ( isset( $termRow->wbit_item_id ) ) {
			return $this->entityIdComposer->composeEntityId(
				'', 'item', $termRow->wbit_item_id
			);
		} else {
			return null;
		}
	}

	private function getDbr() {
		return $this->repoDb->connections()->getReadConnection();
	}
}
