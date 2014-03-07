<?php

namespace Wikibase;

use OutOfBoundsException;
use Wikibase\Entity;
use Wikibase\EntityId;

/**
 * TODO: Write test!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class EntityRetrievingTermLookup implements EntityTermLookup {

	/**
	 * @var EntityLookup
	 */
	var $entityLookup;

	/**
	 * @param EntityLookup $entityLookup
	 */
	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @see EntityTermLookup::getLabelForId
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 * @return string|null
	 */
	public function getLabelForId( EntityId $entityId, $languageCode ) {
		$entity = $this->getEntityForId( $entityId );
		return $entity->getLabel( $languageCode );
	}

	/**
	 * @see EntityTermLookup::getDescriptionForId
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 * @return string|null
	 */
	public function getDescriptionForId( EntityId $entityId, $languageCode ) {
		$entity = $this->getEntityForId( $entityId );
		return $entity->getDescription( $languageCode );
	}

	/**
	 * @see EntityTermLookup::getLabelValueForId
	 *
	 * @param EntityId $entityId
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws OutOfBoundsException
	 * @return string[]|null
	 */
	public function getLabelValueForId(
		EntityId $entityId,
		LanguageFallbackChain $languageFallbackChain
	) {
		$entity = $this->getEntityForId( $entityId );
		$preferredValue = $languageFallbackChain->extractPreferredValue( $entity->getLabels() );
		if ( $preferredValue !== null ) {
			return $preferredValue['value'];
		}

		return null;
	}

	/**
	 * @see EntityTermLookup::getDescriptionValueForId
	 *
	 * @param EntityId $entityId
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws OutOfBoundsException
	 * @return string[]|null
	 */
	public function getDescriptionValueForId(
		EntityId $entityId,
		LanguageFallbackChain $languageFallbackChain
	) {
		$entity = $this->getEntityForId( $entityId );
		$preferredValue = $languageFallbackChain->extractPreferredValue( $entity->getDescriptions() );
		if ( $preferredValue !== null ) {
			return $preferredValue['value'];
		}

		return null;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException
	 * @return Entity
	 */
	private function getEntityForId( EntityId $entityId ) {
		try {
			$entity = $this->entityLookup->getEntity( $entityId );
			if ( $entity !== null ) {
				return $entity;
			}
		} catch ( StorageException $ex ) {
			// Throw an OutOfBoundsException instead
		}

		throw new OutOfBoundsException( "An Entity with the EntityId $entityId does not exist" );
	}

}
