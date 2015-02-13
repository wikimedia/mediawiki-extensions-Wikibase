<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Term;

/**
 * API module to search for Wikibase entities.
 *
 * FIXME: this module is doing to much work. Ranking terms is not its job and should be delegated
 * FIXME: the continuation currently relies on the search order returned by the TermStore
 *
 * Note: Continuation only works for a rather small number of entities. It is assumed that a large
 * number of entities will not be searched through by human editors, and that bots cannot search
 * through them anyway.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Mättig
 */
class SearchEntities extends ApiBase {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		//TODO: provide a mechanism to override the services
		$this->titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$this->idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
	}

	/**
	 * Get the entities corresponding to the provided language and term pair.
	 * Term means it is either a label or an alias.
	 *
	 * @param string $term
	 * @param string|null $entityType
	 * @param string $language
	 * @param int $limit
	 * @param bool $prefixSearch
	 *
	 * @return EntityId[]
	 */
	private function searchEntities( $term, $entityType, $language, $limit, $prefixSearch ) {
		wfProfileIn( __METHOD__ );

		$ids = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex()->getMatchingIDs(
			array(
				new Term( array(
					'termType' 		=> Term::TYPE_LABEL,
					'termLanguage' 	=> $language,
					'termText' 		=> $term
				) ),
				new Term( array(
					'termType' 		=> Term::TYPE_ALIAS,
					'termLanguage' 	=> $language,
					'termText' 		=> $term
				) )
			),
			$entityType,
			array(
				'caseSensitive' => false,
				'prefixSearch' => $prefixSearch,
				'LIMIT' => $limit,
			)
		);

		wfProfileOut( __METHOD__ );
		return $ids;
	}

	/**
	 * Populates the search result returning the number of requested matches plus one additional
	 * item for being able to determine if there would be any more results.
	 * If there are not enough exact matches, the list of returned entries will be additionally
	 * filled with prefixed matches.
	 *
	 * @param array $params
	 *
	 * @return array[]
	 */
	private function getSearchEntries( array $params ) {
		wfProfileIn( __METHOD__ );

		$ids = array();
		$required = $params['continue'] + $params['limit'] + 1;

		$entityId = $this->getExactMatchForEntityId( $params['search'], $params['type'] );
		if ( $entityId !== null ) {
			$ids[] = $entityId;
		}

		$missing = $required - count( $ids );
		$ids = array_merge(
			$ids,
			$this->getRankedMatches( $params['search'], $params['type'], $params['language'], $missing )
		);
		$ids = array_unique( $ids );

		$entries = $this->getEntries( $ids, $params['search'], $params['language'] );

		wfProfileOut( __METHOD__ );
		return $entries;
	}

	/**
	 * Gets exact match for the search term as an EntityId if it can be found.
	 *
	 * @param string $term
	 * @param string $entityType
	 *
	 * @return EntityId|null
	 */
	private function getExactMatchForEntityId( $term, $entityType ) {
		try {
			$entityId = $this->idParser->parse( $term );
			$title = $this->titleLookup->getTitleForId( $entityId );

			if ( $title && $title->exists() && ( $entityId->getEntityType() === $entityType ) ) {
				return $entityId;
			}
		} catch ( EntityIdParsingException $ex ) {
			// never mind, doesn't look like an ID.
		}

		return null;
	}

	/**
	 * Gets exact matches. If there are not enough exact matches, it gets prefixed matches.
	 *
	 * @param string $term
	 * @param string|null $entityType
	 * @param string $language
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	private function getRankedMatches( $term, $entityType, $language, $limit ) {
		/**
		 * @var EntityId[] $ids
		 */
		$ids = array();

		// If still space, then merge in exact matches
		$missing = $limit - count( $ids );
		if ( $missing > 0 ) {
			$ids = array_merge( $ids, $this->searchEntities( $term, $entityType, $language,
				$missing, false ) );
			$ids = array_unique( $ids );
		}

		// If still space, then merge in prefix matches
		$missing = $limit - count( $ids );
		if ( $missing > 0 ) {
			$ids = array_merge( $ids, $this->searchEntities( $term, $entityType, $language,
				$missing, true ) );
			$ids = array_unique( $ids );
		}

		// Reduce overflow, if any
		$ids = array_slice( $ids, 0, $limit );

		return $ids;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string $search
	 * @param string $languageCode
	 *
	 * @return array[]
	 */
	private function getEntries( array $entityIds, $search, $languageCode ) {
		/**
		 * @var array[] $entries
		 */
		$entries = array();

		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();
			$title = $this->titleLookup->getTitleForId( $id );
			$entries[ $key ] = array(
				'id' => $id->getSerialization(),
				'url' => $title->getFullUrl()
			);
		}

		// Find all the remaining terms for the given entities
		$terms = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex()->getTermsOfEntities(
			$entityIds, null, array( $languageCode ) );
		// TODO: This needs to be rethought when a different search engine is used
		$aliasPattern = '/^' . preg_quote( $search, '/' ) . '/i';

		foreach ( $terms as $term ) {
			$key = $term->getEntityId()->getSerialization();
			if ( !isset( $entries[$key] ) ) {
				continue;
			}

			$entry = $entries[$key];

			switch ( $term->getType() ) {
				case Term::TYPE_LABEL:
					$entry['label'] = $term->getText();
					break;
				case Term::TYPE_DESCRIPTION:
					$entry['description'] = $term->getText();
					break;
				case Term::TYPE_ALIAS:
					// Only include matching aliases
					if ( preg_match( $aliasPattern, $term->getText() ) ) {
						if ( !isset( $entry['aliases'] ) ) {
							$entry['aliases'] = array();
							$this->getResult()->setIndexedTagName( $entry['aliases'], 'alias' );
						}
						$entry['aliases'][] = $term->getText();
					}
					break;
			}

			$entries[$key] = $entry;
		}

		$entries = array_values( $entries );

		return $entries;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();

		$entries = $this->getSearchEntries( $params );

		$this->getResult()->addValue(
			null,
			'searchinfo',
			array(
				'search' => $params['search']
			)
		);

		$this->getResult()->addValue(
			null,
			'search',
			array()
		);

		// getSearchEntities returns one more item than requested in order to determine if there
		// would be any more results coming up.
		$hits = count( $entries );

		// Actual result set.
		$entries = array_slice( $entries, $params['continue'], $params['limit'] );

		$nextContinuation = $params['continue'] + $params['limit'];

		// Only pass search-continue param if there are more results and the maximum continuation
		// limit is not exceeded.
		if ( $hits > $nextContinuation && $nextContinuation <= ApiBase::LIMIT_SML1 ) {
			$this->getResult()->addValue(
				null,
				'search-continue',
				$nextContinuation
			);
		}

		$this->getResult()->addValue(
			null,
			'search',
			$entries
		);

		$this->getResult()->setIndexedTagName_internal( array( 'search' ), 'entity' );

		// @todo use result builder?
		$this->getResult()->addValue(
			null,
			'success',
			(int)true
		);

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		$entityFactory = WikibaseRepo::getDefaultInstance()->getEntityFactory();

		return array(
			'search' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getTermsLanguages()->getLanguages(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'type' => array(
				ApiBase::PARAM_TYPE => $entityFactory->getEntityTypes(),
				ApiBase::PARAM_DFLT => 'item',
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_DFLT => 7,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_SML1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_SML2,
				ApiBase::PARAM_MIN => 0,
				ApiBase::PARAM_RANGE_ENFORCE => true,
			),
			'continue' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsearchentities&search=abc&language=en' => 'apihelp-wbsearchentities-example-1',
			'action=wbsearchentities&search=abc&language=en&limit=50' => 'apihelp-wbsearchentities-example-2',
			'action=wbsearchentities&search=alphabet&language=en&type=property' => 'apihelp-wbsearchentities-example-3',
		);
	}

}
