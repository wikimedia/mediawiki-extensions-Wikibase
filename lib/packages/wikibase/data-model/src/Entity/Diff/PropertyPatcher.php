<?php

namespace Wikibase\DataModel\Entity\Diff;

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
	 * @var FingerprintPatcher
	 */
	private $fingerprintPatcher;

	public function __construct() {
		$this->fingerprintPatcher = new FingerprintPatcher();
	}

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

		$this->patchProperty( $entity, $patch );
	}

	private function assertIsProperty( EntityDocument $property ) {
		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( 'All entities need to be properties' );
		}
	}

	private function patchProperty( Property $property, EntityDiff $patch ) {
		$this->fingerprintPatcher->patchFingerprint( $property->getFingerprint(), $patch );
	}

}
