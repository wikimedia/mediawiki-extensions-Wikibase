<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyPatcher implements EntityPatcherStrategy {

	/**
	 * @param string $entityType
	 *
	 * @return boolean
	 */
	public function canPatchEntityType( $entityType ) {
		return $entityType === 'property';
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @return Property
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		$this->assertIsProperty( $entity );

		return $this->getPatchedProperty( $entity, $patch );
	}

	private function assertIsProperty( EntityDocument $property ) {
		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( 'All entities need to be properties' );
		}
	}

	private function getPatchedProperty( Property $property, EntityDiff $patch ) {
		$patcher = new MapPatcher();

		$property->setLabels( $patcher->patch( $property->getLabels(), $patch->getLabelsDiff() ) );
		$property->setDescriptions( $patcher->patch( $property->getDescriptions(), $patch->getDescriptionsDiff() ) );
		$property->setAllAliases( $patcher->patch( $property->getAllAliases(), $patch->getAliasesDiff() ) );

		return $property;
	}

}
