<?php

namespace Wikibase\Repo;

use Wikibase\Lib\DataTypeFactory;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use User;

/**
 * Class for changing a property's data type.
 * Please be aware of the implication such an operation has.
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class PropertyDataTypeChanger {

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		DataTypeFactory $dataTypeFactory
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * @param PropertyId $propertyId
	 * @param User $user User to attribute the changes made to.
	 * @param string $dataTypeId
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 */
	public function changeDataType( PropertyId $propertyId, User $user, $dataTypeId ) {
		$entityRevision = $this->entityRevisionLookup->getEntityRevision(
			$propertyId,
			0,
			EntityRevisionLookup::LATEST_FROM_MASTER
		);

		if ( $entityRevision === null ) {
			throw new StorageException( "Could not load property: " . $propertyId->getSerialization() );
		}

		/* @var $property Property */
		$property = $entityRevision->getEntity();

		$oldDataTypeId = $property->getDataTypeId();
		$this->assertDataTypesCompatible( $oldDataTypeId, $dataTypeId );

		$property->setDataTypeId( $dataTypeId );

		$this->entityStore->saveEntity(
			$property,
			'Changed data type from ' . $oldDataTypeId . ' to ' . $dataTypeId,
			$user,
			EDIT_UPDATE,
			$entityRevision->getRevisionId()
		);
	}

	/**
	 * @param string $oldTypeId
	 * @param string $newTypeId
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertDataTypesCompatible( $oldTypeId, $newTypeId ) {
		$oldType = $this->dataTypeFactory->getType( $oldTypeId );
		$newType = $this->dataTypeFactory->getType( $newTypeId );

		if ( $oldType->getDataValueType() !== $newType->getDataValueType() ) {
			throw new InvalidArgumentException( "New and old data type must have the same data value type." );
		}
	}

}
