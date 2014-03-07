<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;
use Wikibase\StorageException;

/**
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
		$label = $entity->getLabel( $languageCode );
		if ( $label === false ) {
			return null;
		}

		return $label;
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
		$description = $entity->getDescription( $languageCode );
		if ( $description === false ) {
			return null;
		}

		return $description;
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
		return $languageFallbackChain->extractPreferredValue( $entity->getLabels() );
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
		return $languageFallbackChain->extractPreferredValue( $entity->getDescriptions() );
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

		throw new OutOfBoundsException( "An entity with the id $entityId does not exist." );
	}

}
