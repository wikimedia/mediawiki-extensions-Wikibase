<?php

namespace Wikibase\Api;

use ApiBase;

use Wikibase\StoreFactory;
use Wikibase\EntityContentFactory;
use Wikibase\EntityFactory;
use Wikibase\Utils;

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
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SearchEntities extends ApiBase {

	/**
	 * Get the entities corresponding to the provided language and term pair.
	 * Term means it is either a label or an alias.
	 *
	 * @since 0.2
	 *
	 * @param string $language
	 * @param string $term
	 * @param string|null $entityType
	 * @param int $limit
	 * @param bool $prefixSearch
	 *
	 * @return \Wikibase\EntityContent[]
	 */
	protected function searchEntities( $language, $term, $entityType, $limit, $prefixSearch  ) {
		wfProfileIn( __METHOD__ );

		$ids = StoreFactory::getStore()->getTermIndex()->getMatchingIDs(
			array(
				new \Wikibase\Term( array(
					'termType' 		=> \Wikibase\Term::TYPE_LABEL,
					'termLanguage' 	=> $language,
					'termText' 		=> $term
				) ),
				new \Wikibase\Term( array(
					'termType' 		=> \Wikibase\Term::TYPE_ALIAS,
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
	 * @since 0.4
	 *
	 * @param array $params
	 * @return array
	 */
	private function getSearchEntries( $params ) {
		wfProfileIn( __METHOD__ );

		// Gets exact matches. If there are not enough exact matches, it gets prefixed matches
		// TODO: This is a work around for the broken code - it should be fixed
		$limit = $params['limit'] + $params['continue'] + 1;
		$ids = array();
		// Gets exact match for the search term as an id if it can be found
		$entityId = \Wikibase\EntityId::newFromPrefixedId( $params['search'] );
		if ( $entityId ) {
			$page = EntityContentFactory::singleton()->getWikiPageForId( $entityId );
			if ( $page->exists() ) {
				$entityContent = $page->getContent();
				if ( ( $entityContent instanceof \Wikibase\EntityContent ) && ( $entityContent->getEntity()->getType() === $params['type'] ) ) {
					$ids[] = $entityId;
				}
			}
		}

		// If still space, then merge in exact matches
		if ( count( $ids ) < $limit ) {
			$ids = array_merge( $ids, $this->searchEntities( $params['language'], $params['search'], $params['type'], $limit, false ) );
			$ids = array_unique( $ids );
		}

		// If still space, then merge in prefix matches
		if ( count( $ids ) < $limit ) {
			$ids = array_merge( $ids, $this->searchEntities( $params['language'], $params['search'], $params['type'], $limit, true ) );
			$ids = array_unique( $ids );
		}

		// reduce any overflow
		$ids = array_slice ( $ids, 0, $limit );

		// Find all the remaining terms for the given entities
		$terms = StoreFactory::getStore()->getTermIndex()->getTermsOfEntities( $ids, $params['type'], $params['language'] );

		$entries = array();

		/**
		 * var EntityId $id
		 */
		foreach ( $ids as $id ) {
			$entry = array();

			$entry['id'] = $id->getPrefixedId();
			$entry['url'] = EntityContentFactory::singleton()->getTitleForId( $id )->getFullUrl();

			$aliases = array();
			foreach ( $terms as $term ) {
				if ( $term->getEntityId() === $id->getNumericId() ) {
					if ( $term->getType() === 'label' ) {
						$entry['label'] = $term->getText();
					}
					if ( $term->getType() === 'description' ) {
						$entry['description'] = $term->getText();
					}
					if ( $term->getType() === 'alias' ) {
						// Only include matching aliases
						// TODO This needs to be rethought when a different search engine is used
						if ( preg_match( "/^" . preg_quote( $params['search'], '/' ) . "/i", $term->getText() ) !== 0 ) {
							$aliases[] = $term->getText();
						}
					}
				}
			}

			if ( count( $aliases ) > 0 ) {
				$entry['aliases'] = $aliases;
				$this->getResult()->setIndexedTagName( $entry['aliases'], 'alias' );
			}

			$entries[] = $entry;
		}

		wfProfileOut( __METHOD__ );
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

		$allowedParams = $this->getAllowedParams();
		$nextContinuation = $params['continue'] + $params['limit'];
		$maxContinuation = $allowedParams['continue'][ApiBase::PARAM_MAX];

		// Only pass search-continue param if there are more results and the maximum continuation
		// limit is not exceeded.
		if ( $hits > $nextContinuation && $nextContinuation <= $maxContinuation ) {
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

		$this->getResult()->addValue(
			null,
			'success',
			(int)true
		);

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		return array(
			'search' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'type' => array(
				ApiBase::PARAM_TYPE => EntityFactory::singleton()->getEntityTypes(),
				ApiBase::PARAM_DFLT => 'item',
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 7,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_SML1,
				ApiBase::PARAM_MIN => 0,
				ApiBase::PARAM_RANGE_ENFORCE => true,
			),
			'continue' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_SML1,
				ApiBase::PARAM_MIN => 0,
				ApiBase::PARAM_RANGE_ENFORCE => true,
			),
		);
	}

	/**
	 * @see \ApiBase::getParamDescription
	 */
	public function getParamDescription() {
		return array(
			'search' => 'Search for this text.',
			'language' => 'Search in this language.',
			'type' => 'Search for this type of entity.',
			'limit' => 'Maximal number of results',
			'continue' => 'Offset where to continue a search',
		);
	}

	/**
	 * @see \ApiBase::getDescription
	 */
	public function getDescription() {
		return array(
			'API module to search for entities.'
		);
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array() );
	}

	/**
	 * @see \ApiBase::getExamples
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsearchentities&search=abc&language=en' => 'Search for "abc" in English language, with defaults for type and limit',
			'api.php?action=wbsearchentities&search=abc&language=en&limit=50' => 'Search for "abc" in English language with a limit of 50',
			'api.php?action=wbsearchentities&search=alphabet&language=en&type=property' => 'Search for "alphabet" in English language for type property',
		);
	}

	/**
	 * @see \ApiBase::getHelpUrls
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsearchentities';
	}

	/**
	 * @see \ApiBase::getVersion
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
