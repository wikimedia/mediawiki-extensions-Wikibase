<?php

namespace Wikibase\Repo\Api;

use LogicException;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * EntitySearchHelper decorator that adds an entity concept URI to the TermSearchResult meta data if not already set.
 * This works in conjunction with ApiEntitySearchHelper for federated properties that already includes the concept URI in the metadata.
 *
 * @license GPL-2.0-or-later
 */
class ConceptUriSearchHelper implements EntitySearchHelper {

	public const CONCEPTURI_META_DATA_KEY = TermSearchResult::CONCEPTURI_META_DATA_KEY;

	/**
	 * @var EntitySearchHelper
	 */
	private $searchHelper;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	public function __construct( EntitySearchHelper $searchHelper, EntitySourceDefinitions $entitySourceDefinitions ) {
		$this->searchHelper = $searchHelper;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
	}

	public function getRankedSearchResults( $text, $languageCode, $entityType, $limit, $strictLanguage ) {
		$results = $this->searchHelper->getRankedSearchResults( $text, $languageCode, $entityType, $limit, $strictLanguage );

		return array_map( function ( TermSearchResult $searchResult ) {
			// Do not set the concept URI if it is already set.
			if ( array_key_exists( self::CONCEPTURI_META_DATA_KEY, $searchResult->getMetaData() ) ) {
				return $searchResult;
			}

			return new TermSearchResult(
				$searchResult->getMatchedTerm(),
				$searchResult->getMatchedTermType(),
				$searchResult->getEntityId(),
				$searchResult->getDisplayLabel(),
				$searchResult->getDisplayDescription(),
				array_merge(
					$searchResult->getMetaData(),
					[ self::CONCEPTURI_META_DATA_KEY => $this->getConceptUri( $searchResult->getEntityId() ) ]
				) );
		}, $results );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getConceptUri( EntityId $entityId ) {
		$baseUri = $this->getConceptBaseUri( $entityId );
		return $baseUri . wfUrlencode( $entityId->getLocalPart() );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LogicException when there is no base URI for the repository $entityId belongs to
	 *
	 * @return string
	 */
	private function getConceptBaseUri( EntityId $entityId ) {
		$source = $this->entitySourceDefinitions->getSourceForEntityType( $entityId->getEntityType() );
		if ( $source === null ) {
			throw new LogicException(
				'No source defined for entity of type: ' . $entityId->getEntityType()
			);
		}

		return $source->getConceptBaseUri();
	}

}
