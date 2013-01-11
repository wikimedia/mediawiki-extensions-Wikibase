<?php

namespace Wikibase;
use ApiBase;

/**
 * API module to search for Wikibase entities.
 *
 * FIXME: the post-query filter can result in fewer rows returned then the limit
 * FIXME: an entity content is obtained for each term (even in a separate query)
 * FIXME: this module is doing to much work. Ranking terms is not its job and should be delegated
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
class ApiSearchEntities extends ApiBase {

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
	 * @return EntityContent[]
	 */
	protected function searchEntities( $language, $term, $entityType, $limit, $prefixSearch  ) {
		wfProfileIn( __METHOD__ );

		$terms = StoreFactory::getStore()->newTermCache()->getMatchingTerms(
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
			null,
			$entityType,
			array(
				'caseSensitive' => false,
				'prefixSearch' => $prefixSearch,
				'LIMIT' => $limit,
			)
		);

		$entities = array();

		/**
		 * @var Term $term
		 */
		foreach ( $terms as $term ) {
			$entityId = new EntityId( $entityType, $term->getEntityId() );
			$entity = EntityContentFactory::singleton()->getFromId( $entityId );

			if ( $entity !== null ) {
				$entities[] = $entity;
			}
		}

		wfProfileOut( __METHOD__ );
		return $entities;
	}

	/**
	 * Populate the search result entries
	 *
	 * @since 0.4
	 *
	 * @param EntityContent[] $results
	 * @param string $language
	 * @param string $search
	 */
	private function getSearchEntries( $params ) {
		wfProfileIn( __METHOD__ );

		// Gets exact matches. If there are not enough exact matches, it gets prefixed matches
		$limit = $params['limit'] + $params['continue'] + 1;
		$results = $this->searchEntities( $params['language'], $params['search'], $params['type'], $limit, false );
		if ( count( $results ) < $limit ) {
			$results = $this->searchEntities( $params['language'], $params['search'], $params['type'], $limit, true );
		}

		$entries = array();

		foreach ( $results as $result ) {
			$entry = array();
			$entity = $result->getEntity();

			$entry['id'] = $entity->getPrefixedId();
			$entry['url'] = EntityContentFactory::singleton()->getTitleForId( $entity->getId() )->getFullUrl();

			if ( $entity->getLabel( $params['language'] ) !== false ) {
				$entry['label'] = $entity->getLabel( $params['language'] );
			}

			if ( $entity->getDescription( $params['language'] ) !== false ) {
				$entry['description'] = $entity->getDescription( $params['language'] );
			}

			// Only include matching aliases
			$aliases = $entity->getAliases( $params['language'], $params['search'] );
			$aliasEntries = array();

			foreach ( $aliases as $alias ) {
				if ( preg_match( "/^" . preg_quote( $params['search'] ) . "/i", $alias ) !== 0 ) {
					$aliasEntries[] = $alias;
				}
			}

			if ( count( $aliasEntries ) > 0 ) {
				$entry['aliases'] = $aliasEntries;
				$this->getResult()->setIndexedTagName( $entry['aliases'], 'alias' );
			}

			if ( !in_array( $entry, $entries ) ) {
				$entries[] = $entry;
			}
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

		$totalHits = count ( $entries );
		$entries = array_slice( $entries, $params['continue'], $params['limit'] );

		if ( $totalHits > ( $params['continue'] + $params['limit'] ) )  {
			$this->getResult()->addValue(
				null,
				'search-continue',
				$totalHits - 1
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
	 * @see ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		// TODO: We probably need a flag for fuzzy searches. This is
		// only a boolean flag.
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
			),
			'continue' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
			),
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 */
	public function getParamDescription() {
		// we probably need a flag for fuzzy searches
		return array(
			'search' => 'Search for this initial text.',
			'language' => 'Search within this language.',
			'type' => 'Search for this type of entity.',
			'limit' => array( 'Limit to this number of non-exact matches',
				"The value '0' will return all found matches." ),
			'continue' => 'Offset where to continue when in a (limited) search continuation',
		);
	}

	/**
	 * @see ApiBase::getDescription
	 */
	public function getDescription() {
		return array(
			'API module to search for entities.'
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array() );
	}

	/**
	 * @see ApiBase::getExamples
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsearchentities&search=abc&language=en'
			=> 'Search for "abc" in English language, with defaults for type and limit.',
		);
	}

	/**
	 * @see ApiBase::getHelpUrls
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsearchentity';
	}

	/**
	 * @see ApiBase::getVersion
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
