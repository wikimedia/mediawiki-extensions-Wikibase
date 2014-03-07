<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;
use Wikibase\SqlEntityInfoBuilder;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class EntityInfoTermLookup implements EntityTermLookup {

	/**
	 * @var array[]
	 */
	var $entityInfo;

	/**
	 * @param array[] $entityInfo
	 */
	public function __construct( array $entityInfo ) {
		$this->entityInfo = $entityInfo;
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
		$labels = $this->getEntityInfoFieldForId(
			$entityId,
			SqlEntityInfoBuilder::$termTypeFields['label']
		);
		if ( isset( $labels[$languageCode] ) && isset( $labels[$languageCode]['value'] ) ) {
			return $labels[$languageCode]['value'];
		}

		return null;
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
		$descriptions = $this->getEntityInfoFieldForId(
			$entityId,
			SqlEntityInfoBuilder::$termTypeFields['description']
		);
		if ( isset( $descriptions[$languageCode] ) && isset( $descriptions[$languageCode]['value'] ) ) {
			return $descriptions[$languageCode]['value'];
		}

		return null;
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
		$labels = $this->getEntityInfoFieldForId(
			$entityId,
			SqlEntityInfoBuilder::$termTypeFields['label']
		);

		return $languageFallbackChain->extractPreferredValue( $labels );
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
		$descriptions = $this->getEntityInfoFieldForId(
			$entityId,
			SqlEntityInfoBuilder::$termTypeFields['description']
		);

		return $languageFallbackChain->extractPreferredValue( $descriptions );
	}

	/**
	 * @see SqlEntityInfoBuilder::termTypeFields
	 *
	 * @param EntityId $entityId
	 * @param string $termType One of the SqlEntityInfoBuilder::termTypeFields values.
	 *
	 * @throws OutOfBoundsException
	 * @return array|null
	 */
	private function getEntityInfoFieldForId( EntityId $entityId, $termType ) {
		$entityInfo = $this->getEntityInfoForId( $entityId );
		if ( is_array( $entityInfo ) && array_key_exists( $termType, $entityInfo ) ) {
			return $entityInfo[$termType];
		}

		return null;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException
	 * @return array
	 */
	private function getEntityInfoForId( EntityId $entityId ) {
		$id = $entityId->getSerialization();
		if ( array_key_exists( $id, $this->entityInfo ) ) {
			return $this->entityInfo[$id];
		}

		throw new OutOfBoundsException( "An entity with the id $id does not exist." );
	}

}
