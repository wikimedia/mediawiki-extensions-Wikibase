<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\SerializableEntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikimedia\Assert\Assert;

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
	 * @var string[][] Associative array mapping entity type names (strings) to names of
	 *   repositories providing entities of this type.
	 */
	private $entityTypeToRepositoryMapping;

	/**
	 * @param EntityLookup $entityLookup
	 * @param EntityIdParser $idParser
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param string[][] $entityTypeToRepositoryMapping Associative array (string => string[][])
	 *   mapping entity types to a list of repository names which provide entities of the given type.
	 */
	public function __construct(
		EntityLookup $entityLookup,
		EntityIdParser $idParser,
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
	 * Returns EntityIds matching the search term (possibly with some repository prefix).
	 * If search term is a serialized entity id of the requested type, and multiple repositories provide
	 * entities of the type, prefixes of each of repositories are added to the search term and those repositories
	 * are searched for the result entity ID. If such concatenated entity IDs are found in several respective
	 * repositories, this returns all relevant matches.
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
	 * Returns a list of entity IDs matching the pattern defined by $entityId: existing entities
	 * of type of $entityId, and serialized id equal to $entityId, possibly including prefixes
	 * of configured repositories.
	 *
	 * @param EntityId $entityId
	 *
	 * @return EntityId|null
	 */
	private function getMatchingId( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		if ( !array_key_exists( $entityType, $this->entityTypeToRepositoryMapping ) ) {
			// Unknown entity type, nothing we can do here.
			return null;
		}

		// NOTE: this assumes entities of the particular type are only provided by a single repository
		// This assumption is currently valid but might change in the future.
		$repositoryPrefix = $this->entityTypeToRepositoryMapping[$entityType][0];

		if ( $entityId->getRepositoryName() !== '' && $repositoryPrefix !== $entityId->getRepositoryName() ) {
			// If a repository is explicitly specified and it is not the one (and only) we know about abort.
			return null;
		}

		// Note: EntityLookup::hasEntity may return true even if the getRepositoryName of the entity id is
		// unknown, as the lookup doesn't about its entity source setting.
		if ( $this->entityLookup->hasEntity( $entityId ) ) {
			return $entityId;
		}

		// Entity ID without repository prefix, let's try prepending known prefixes
		$unprefixedIdPart = $entityId->getLocalPart();

		try {
			$id = $this->idParser->parse( SerializableEntityId::joinSerialization( [
				$repositoryPrefix,
				'',
				$unprefixedIdPart,
			] ) );
		} catch ( EntityIdParsingException $ex ) {
			return null;
		}

		if ( $this->entityLookup->hasEntity( $id ) ) {
			return $id;
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
