<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StoreFactory;
use Wikibase\Term;
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
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Mättig < thiemo.maettig@wikimedia.de >
 */
class SearchEntities extends ApiBase {

	/**
	 * Get the entities corresponding to the provided language and term pair.
	 * Term means it is either a label or an alias.
	 *
	 * @since 0.2
	 *
	 * @param string $search
	 * @param string|null $type
	 * @param string $language
	 * @param int $limit
	 * @param bool $prefixSearch
	 *
	 * @return EntityId[]
	 */
	protected function searchEntities( $search, $type, $language, $limit, $prefixSearch ) {
		wfProfileIn( __METHOD__ );

		$ids = StoreFactory::getStore()->getTermIndex()->getMatchingIDs(
			array(
				new \Wikibase\Term( array(
					'termType' 		=> \Wikibase\Term::TYPE_LABEL,
					'termLanguage' 	=> $language,
					'termText' 		=> $search
				) ),
				new \Wikibase\Term( array(
					'termType' 		=> \Wikibase\Term::TYPE_ALIAS,
					'termLanguage' 	=> $language,
					'termText' 		=> $search
				) )
			),
			$type,
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
		array_merge( $ids, $this->getRankedMatches( $params['search'], $params['type'],
			$params['language'], $missing ) );
		$ids = array_unique( $ids );

		$entries = $this->getEntries( $ids, $params['search'], $params['type'],
			$params['language'] );

		wfProfileOut( __METHOD__ );
		return $entries;
	}

	/**
	 * Gets exact match for the search term as an EntityId if it can be found.
	 *
	 * @param string $search
	 * @param string $type
	 *
	 * @return EntityId|null
	 */
	private function getExactMatchForEntityId( $search, $type ) {
		// FIXME: Use an EntityIdParser
		$entityId = \Wikibase\EntityId::newFromPrefixedId( $search );
		if ( $entityId !== null ) {
			$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
			$page = $entityContentFactory->getWikiPageForId( $entityId );
			if ( $page->exists() ) {
				$content = $page->getContent();
				if ( $content instanceof \Wikibase\EntityContent &&
					( $type === null || $type === $content->getEntity()->getType() ) ) {
					return $entityId;
				}
			}
		}

		return null;
	}

	/**
	 * Gets exact matches. If there are not enough exact matches, it gets prefixed matches.
	 *
	 * @param string $search
	 * @param string|null $type
	 * @param string $language
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	private function getRankedMatches( $search, $type, $language, $limit ) {
		/**
		 * @var EntityId[] $ids
		 */
		$ids = array();

		// If still space, then merge in exact matches
		$missing = $limit - count( $ids );
		if ( $missing > 0 ) {
			$ids = array_merge( $ids, $this->searchEntities( $search, $type, $language,
				$missing, false ) );
			$ids = array_unique( $ids );
		}

		// If still space, then merge in prefix matches
		$missing = $limit - count( $ids );
		if ( $missing > 0 ) {
			$ids = array_merge( $ids, $this->searchEntities( $search, $type, $language,
				$missing, true ) );
			$ids = array_unique( $ids );
		}

		// Reduce overflow, if any
		$ids = array_slice( $ids, 0, $limit );

		return $ids;
	}

	/**
	 * @param EntityId[] $ids
	 * @param string $search
	 * @param string $type
	 * @param string|null $language language code
	 *
	 * @return array[]
	 */
	private function getEntries( array $ids, $search, $type, $language ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		/**
		 * @var array[] $entries
		 */
		$entries = array();

		foreach ( $ids as $id ) {
			// FIXME: Change this to the actual ID if the Term class stops returning integers.
			$numericId = $id->getNumericId();
			$entries[ $numericId ] = array(
				'id' => $id->getPrefixedId(),
				'url' => $entityContentFactory->getTitleForId( $id )->getFullUrl()
			);
		}

		// Find all the remaining terms for the given entities
		$terms = StoreFactory::getStore()->getTermIndex()->getTermsOfEntities( $ids, $type,
			$language );
		// TODO: This needs to be rethought when a different search engine is used
		$aliasPattern = '/^' . preg_quote( $search, '/' ) . '/i';

		foreach ( $terms as $term ) {
			// FIXME: Change this to the actual ID if the Term class stops returning integers.
			$numericId = $term->getEntityId();
			if ( !isset( $entries[ $numericId ] ) ) {
				continue;
			}

			$entry = $entries[$numericId];

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

			$entries[$numericId] = $entry;
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

		// @todo use result builder?
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
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_DFLT => 7,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_SML1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_SML2,
				ApiBase::PARAM_MIN => 0,
				ApiBase::PARAM_RANGE_ENFORCE => true,
			),
			'continue' => array(
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_DFLT => 0,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_SML1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_SML2,
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
