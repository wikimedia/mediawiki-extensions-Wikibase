<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * Helper class to search for entities by ID
 *
 * @license GPL-2.0-or-later
 */
class EntityIdSearchHelper implements EntitySearchHelper {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string[] Entity type names
	 */
	private $entityTypes;

	/**
	 * @param EntityLookup $entityLookup
	 * @param EntityIdParser $idParser
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param string[] $entityTypes list of "names" of known entity types
	 */
	public function __construct(
		EntityLookup $entityLookup,
		EntityIdParser $idParser,
		LabelDescriptionLookup $labelDescriptionLookup,
		array $entityTypes
	) {
		$this->entityLookup = $entityLookup;
		$this->idParser = $idParser;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->entityTypes = $entityTypes;
	}

	/**
	 * Gets exact matches. If there are not enough exact matches, it gets prefixed matches.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 * @param string|null $profileContext
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage,
		?string $profileContext
	) {
		$allSearchResults = [];

		// If $text is the ID of an existing item (with repository prefix or without), include it in the result.
		$entityId = $this->getEntityIdMatchingSearchTerm( $text, $entityType );
		if ( !$entityId ) {
			return $allSearchResults;
		}

		// This is nothing to do with terms, but make it look a normal result so everything is easier
		$displayTerms = $this->getDisplayTerms( $entityId );
		$allSearchResults[$entityId->getSerialization()] = new TermSearchResult(
			new Term( 'qid', $entityId->getSerialization() ),
			'entityId',
			$entityId,
			$displayTerms['label'],
			$displayTerms['description']
		);

		return $allSearchResults;
	}

	/**
	 * Returns EntityId matching the search term.
	 *
	 * @param string $term
	 * @param string $entityType
	 *
	 * @return EntityId|null
	 */
	private function getEntityIdMatchingSearchTerm( $term, $entityType ) {
		$entityId = null;
		foreach ( $this->getEntityIdCandidatesForSearchTerm( $term ) as $candidate ) {
			try {
				$entityId = $this->idParser->parse( $candidate );
				break;
			} catch ( EntityIdParsingException $ex ) {
				continue;
			}
		}

		if ( $entityId === null ) {
			return null;
		}

		if ( $entityId->getEntityType() !== $entityType ) {
			return null;
		}

		return $this->getMatchingId( $entityId );
	}

	/**
	 * Returns a generator for candidates of entity ID serializations from a search term.
	 * Callers should attempt to parse each candidate in turn
	 * and use the first one that does not result in an {@link EntityIdParsingException}.
	 *
	 * @param string $term
	 * @return \Generator
	 */
	private function getEntityIdCandidatesForSearchTerm( $term ) {
		// Trim whitespace.
		$term = trim( $term );
		yield $term;

		// Uppercase the search term. Entity IDs are *usually* all-uppercase,
		// and we want to allow case-insensitive search for them.
		$term = strtoupper( $term );
		yield $term;

		// Extract the last (ASCII-only) word. This covers URIs and input strings like "(Q42)".
		// Note that this only supports single-word IDs and not IDs like "L1-F1".
		if ( preg_match( '/.*(\b\w{2,})/s', $term, $matches ) ) {
			$term = $matches[1];
			yield $term;
		}
	}

	/**
	 * Returns an entity ID matching the pattern defined by $entityId: existing entity
	 * of type of $entityId, and serialized id equal to $entityId.
	 *
	 * @param EntityId $entityId
	 *
	 * @return EntityId|null
	 */
	private function getMatchingId( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		if ( !in_array( $entityType, $this->entityTypes ) ) {
			// Unknown entity type, nothing we can do here.
			return null;
		}

		if ( $this->entityLookup->hasEntity( $entityId ) ) {
			return $entityId;
		}

		return null;
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

}
