<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\TermIndexEntry;
use Wikimedia\Assert\Assert;

/**
 * Helper class to search for entities.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntitySearchTermIndex implements EntitySearchHelper {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var ConfigurableTermSearchInteractor
	 */
	private $termSearchInteractor;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string[] Associative array mapping entity type names (strings) to names of repositories providing
	 *               entities of this type.
	 */
	private $entityTypeToRepositoryMapping;

	/**
	 * @param EntityLookup $entityLookup
	 * @param EntityIdParser $idParser
	 * @param ConfigurableTermSearchInteractor $termSearchInteractor
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param array $entityTypeToRepositoryMapping Associative array (string => string[]) mapping entity types to a list of repository names
	 *              which provide entities of the given type.
	 */
	public function __construct(
		EntityLookup $entityLookup,
		EntityIdParser $idParser,
		ConfigurableTermSearchInteractor $termSearchInteractor,
		LabelDescriptionLookup $labelDescriptionLookup,
		array $entityTypeToRepositoryMapping
	) {
		foreach ( $entityTypeToRepositoryMapping as $entityType => $repositoryNames ) {
			Assert::parameter(
				count( $repositoryNames ) === 1,
				'$entityTypeToRepositoryMapping',
				'Expected entities of type: "' . $entityType . '" to only be provided by single repository.'
			);
		}

		$this->entityLookup = $entityLookup;
		$this->idParser = $idParser;
		$this->termSearchInteractor = $termSearchInteractor;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->entityTypeToRepositoryMapping = $entityTypeToRepositoryMapping;
	}

	/**
	 * Gets exact matches. If there are not enough exact matches, it gets prefixed matches.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults( $text, $languageCode, $entityType, $limit, $strictLanguage ) {
		$allSearchResults = [];

		// If $text is the ID of an existing item (with repository prefix or without), include it in the result.
		$entityIds = $this->getEntityIdsMatchingSearchTerm( $text, $entityType );
		foreach ( $entityIds as $entityId ) {
			// This is nothing to do with terms, but make it look a normal result so everything is easier
			$displayTerms = $this->getDisplayTerms( $entityId );
			$allSearchResults[$entityId->getSerialization()] = new TermSearchResult(
				new Term( 'qid', $entityId->getSerialization() ),
				'entityId',
				$entityId,
				$displayTerms['label'],
				$displayTerms['description']
			);
		}

		// If not matched enough then search for full term matches
		$missing = $limit - count( $allSearchResults );
		if ( $missing > 0 ) {
			$exactSearchResults = $this->searchEntities(
				$text,
				$languageCode,
				$entityType,
				$missing,
				false,
				$strictLanguage
			);
			$allSearchResults = $this->mergeSearchResults( $allSearchResults, $exactSearchResults, $limit );

			// If still not enough matched then search for prefix matches
			$missing = $limit - count( $allSearchResults );
			if ( $missing > 0 ) {
				$prefixSearchResults = $this->searchEntities(
					$text,
					$languageCode,
					$entityType,
					$limit, // needs to be the full limit as exact matches are also contained in the prefix search
					true,
					$strictLanguage
				);
				$allSearchResults = $this->mergeSearchResults( $allSearchResults, $prefixSearchResults, $limit );
			}
		}

		return $allSearchResults;
	}

	/**
	 * Returns EntityIds matching the search term (possibly with some repository prefix).
	 * If search term is a serialized entity id of the requested type, and multiple repositories provide
	 * entities of the type, prefixes of each of repositories are added to the search term and those repositories
	 * are searched for the result entity ID. If such concatenated entity IDs are found in several respective
	 * repositories, this returns all relevant matches.
	 *
	 * @param string $term
	 * @param string $entityType
	 *
	 * @return EntityId[]
	 */
	private function getEntityIdsMatchingSearchTerm( $term, $entityType ) {
		try {
			$entityId = $this->idParser->parse( trim( $term ) );
		} catch ( EntityIdParsingException $ex ) {
			// Extract the last (ASCII-only) word. This covers URIs and input strings like "(Q42)".
			if ( !preg_match( '/.*(\b\w{2,})/s', $term, $matches ) ) {
				return [];
			}

			try {
				$entityId = $this->idParser->parse( $matches[1] );
			} catch ( EntityIdParsingException $ex ) {
				return [];
			}
		}

		if ( $entityId->getEntityType() !== $entityType ) {
			return [];
		}

		return $this->getMatchingIdsIncludingPrefixes( $entityId );
	}

	/**
	 * Returns a list of entity IDs matching the pattern defined by $entityId: existing entities
	 * of type of $entityId, and serialized id equal to $entityId, possibly including prefixes
	 * of configured repositories.
	 *
	 * @param EntityId $entityId
	 *
	 * @return EntityId[]
	 */
	private function getMatchingIdsIncludingPrefixes( EntityId $entityId ) {
		$entityIds = [];

		if ( $this->entityLookup->hasEntity( $entityId ) ) {
			$entityIds[] = $entityId;
		}

		// Entity ID without repository prefix, let's try prepending known prefixes
		$entityType = $entityId->getEntityType();
		$unprefixedIdPart = $entityId->getLocalPart();

		if ( !array_key_exists( $entityType, $this->entityTypeToRepositoryMapping ) ) {
			return $entityIds;
		}

		// NOTE: this assumes entities of the particular type are only provided by a single repository
		// This assumption is currently valid but might change in the future.
		list ( $repositoryPrefix, ) = $this->entityTypeToRepositoryMapping[$entityType][0];

		try {
			$id = $this->idParser->parse( EntityId::joinSerialization( [
				$repositoryPrefix,
				'',
				$unprefixedIdPart
			] ) );
		} catch ( EntityIdParsingException $ex ) {
			return [];
		}

		if ( $this->entityLookup->hasEntity( $id ) ) {
			$entityIds[] = $id;
		}

		return $entityIds;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Term[] array with keys 'label' and 'description'
	 */
	private function getDisplayTerms( EntityId $entityId ) {
		$displayTerms = [];

		$displayTerms['label'] = $this->labelDescriptionLookup->getLabel( $entityId );
		$displayTerms['description'] = $this->labelDescriptionLookup->getDescription( $entityId );

		return $displayTerms;
	}

	/**
	 * @param TermSearchResult[] $searchResults
	 * @param TermSearchResult[] $newSearchResults
	 * @param int $limit
	 *
	 * @return TermSearchResult[]
	 */
	private function mergeSearchResults( array $searchResults, array $newSearchResults, $limit ) {
		$searchResultEntityIdSerializations = array_keys( $searchResults );

		foreach ( $newSearchResults as $searchResultToAdd ) {
			$entityIdString = $searchResultToAdd->getEntityId()->getSerialization();

			if ( !in_array( $entityIdString, $searchResultEntityIdSerializations ) ) {
				$searchResults[$entityIdString] = $searchResultToAdd;
				$searchResultEntityIdSerializations[] = $entityIdString;
				$missing = $limit - count( $searchResults );

				if ( $missing <= 0 ) {
					return $searchResults;
				}
			}
		}

		return $searchResults;
	}

	/**
	 * Wrapper around TermSearchInteractor::searchForEntities
	 *
	 * @see TermSearchInteractor::searchForEntities
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $prefixSearch
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[]
	 */
	private function searchEntities( $text, $languageCode, $entityType, $limit, $prefixSearch, $strictLanguage ) {
		$searchOptions = new TermSearchOptions();
		$searchOptions->setLimit( $limit );
		$searchOptions->setIsPrefixSearch( $prefixSearch );
		$searchOptions->setIsCaseSensitive( false );
		$searchOptions->setUseLanguageFallback( !$strictLanguage );

		$this->termSearchInteractor->setTermSearchOptions( $searchOptions );

		return $this->termSearchInteractor->searchForEntities(
			$text,
			$languageCode,
			$entityType,
			[ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ]
		);
	}

}
