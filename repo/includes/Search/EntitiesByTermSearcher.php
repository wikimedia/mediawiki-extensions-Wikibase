<?php

namespace Wikibase\Search;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityFactory;
use Wikibase\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Term;
use Wikibase\Utils;

/**
 * @licence GNU GPL v2+
 *
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Mätti
 */
class EntitiesByTermSearcher {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	private $limit;

	public function __construct( $limit ) {
		$this->titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$this->idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$this->limit = $limit;
	}

	/**
	 * Get the entities corresponding to the provided language and term pair.
	 * Term means it is either a label or an alias.
	 *
	 * @since 0.2
	 *
	 * @param string $term
	 * @param string|null $entityType
	 * @param string $language
	 * @param int $limit
	 * @param bool $prefixSearch
	 *
	 * @return EntityId[]
	 */
	protected function searchEntities( $term, $entityType, $language, $limit, $prefixSearch ) {
		wfProfileIn( __METHOD__ );

		$ids = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex()->getMatchingIDs(
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
		$ids = array_merge( $ids, $this->getRankedMatches( $params['search'], $params['type'],
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
	 * @param EntityId[] $ids
	 * @param string $search
	 * @param string $entityType
	 * @param string|null $language language code
	 *
	 * @return array[]
	 */
	private function getEntries( array $ids, $search, $entityType, $language ) {
		/**
		 * @var array[] $entries
		 */
		$entries = array();

		foreach ( $ids as $id ) {
			$key = $id->getSerialization();
			$title = $this->titleLookup->getTitleForId( $id );
			$entries[ $key ] = array(
				'id' => $id->getPrefixedId(),
				'url' => $title->getFullUrl()
			);
		}

		// Find all the remaining terms for the given entities
		$terms = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex()->getTermsOfEntities( $ids, $entityType,
			$language );
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
						// @fixme
						//	$this->getResult()->setIndexedTagName( $entry['aliases'], 'alias' );
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

	public function search( $params ) {
		wfProfileIn( __METHOD__ );

		$entries = $this->getSearchEntries( $params );

		// Actual result set.
		$entries = array_slice( $entries, $params['continue'], $params['limit'] );

		$hits = count( $entries );
		$nextContinuation = $params['continue'] + $params['limit'];

		if ( $hits > $nextContinuation && $nextContinuation <= $this->limit ) {
			$nextContinuation = $params['continue'] + $params['limit'];
		} else {
			$nextContinuation = null;
		}

		wfProfileOut( __METHOD__ );

		return array( $entries, $nextContinuation );
	}

}
