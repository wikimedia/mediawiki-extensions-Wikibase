<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * TermLookup based on plain array data structures.
 * This allows term lookups to be performed directly on prefetched data,
 * such as the data structured generated by EntityInfoBuilder.
 *
 * @see EntityInfoBuilder
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityInfoTermLookup implements TermLookup {

	/**
	 * @var EntityInfo
	 */
	private $entityInfo;

	/**
	 * @param EntityInfo $entityInfo An array of entity records, as returned
	 * by EntityInfoBuilder::getEntityInfo.
	 */
	public function __construct( EntityInfo $entityInfo ) {
		$this->entityInfo = $entityInfo;
	}

	/**
	 * Gets the label of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$label = $this->entityInfo->getLabel( $entityId, $languageCode );

		$this->checkOutOfBOunds( $label, $languageCode );
		return $label;
	}

	/**
	 * Gets all labels of an Entity with the specified EntityId.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId ) {
		$labels = $this->entityInfo->getLabels( $entityId );
		return $labels;
	}

	/**
	 * Gets the description of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$description = $this->entityInfo->getDescription( $entityId, $languageCode );

		$this->checkOutOfBOunds( $description, $languageCode );
		return $description;
	}

	/**
	 * Gets all descriptions of an Entity with the specified EntityId.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId ) {
		$descriptions = $this->entityInfo->getDescriptions( $entityId );
		return $descriptions;
	}

	/**
	 * @param string $value
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 */
	private function checkOutOfBOunds( $value, $languageCode ) {
		if ( $value === null ) {
			throw new OutOfBoundsException( 'No entry found for language ' . $languageCode );
		}
	}
}
