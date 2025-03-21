<?php

namespace Wikibase\DataModel\Services\Diff;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\Internal\FingerprintPatcher;

/**
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyPatcher implements EntityPatcherStrategy {

	/**
	 * @var FingerprintPatcher
	 */
	private $fingerprintPatcher;

	/**
	 * @var StatementListPatcher
	 */
	private $statementListPatcher;

	public function __construct() {
		$this->fingerprintPatcher = new FingerprintPatcher();
		$this->statementListPatcher = new StatementListPatcher();
	}

	/**
	 * @param string $entityType
	 *
	 * @return bool
	 */
	public function canPatchEntityType( $entityType ) {
		return $entityType === 'property';
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		$property = $this->assertIsPropertyAndCast( $entity );

		$this->patchProperty( $property, $patch );
	}

	private function assertIsPropertyAndCast( EntityDocument $property ): Property {
		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( '$property must be an instance of Property' );
		}
		return $property;
	}

	private function patchProperty( Property $property, EntityDiff $patch ) {
		$this->fingerprintPatcher->patchFingerprint( $property->getFingerprint(), $patch );

		$this->statementListPatcher->patchStatementList(
			$property->getStatements(),
			$patch->getClaimsDiff()
		);
	}

}
