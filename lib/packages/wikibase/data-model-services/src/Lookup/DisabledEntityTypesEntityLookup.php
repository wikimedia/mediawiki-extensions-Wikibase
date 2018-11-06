<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;

/**
 * EntityLookup that checks entities have been loaded through it and throws
 * an exception if the accessing to that entity type is disabled.
 *
 * @since 3.9
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class DisabledEntityTypesEntityLookup implements EntityLookup {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string[]
	 */
	private $disabledEntityTypes = [];

	/**
	 * @param EntityLookup $entityLookup
	 * @param string[] $disabledEntityTypes
	 */
	public function __construct( EntityLookup $entityLookup, array $disabledEntityTypes ) {
		Assert::parameterElementType( 'string', $disabledEntityTypes, '$disabledEntityTypes' );

		$this->entityLookup = $entityLookup;
		$this->disabledEntityTypes = $disabledEntityTypes;
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws EntityLookupException
	 * @return EntityDocument
	 */
	public function getEntity( EntityId $entityId ) {
		if ( in_array( $entityId->getEntityType(), $this->disabledEntityTypes ) ) {
			throw new EntityLookupException(
				$entityId,
				'Entity access for this type of entity is disabled: ' . $entityId->getEntityType()
			);
		}

		return $this->entityLookup->getEntity( $entityId );
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 * @throws EntityLookupException
	 */
	public function hasEntity( EntityId $entityId ) {
		return $this->entityLookup->hasEntity( $entityId );
	}

}
