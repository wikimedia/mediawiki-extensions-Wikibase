<?php

namespace Wikibase\Repo;

use InvalidArgumentException;
use User;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;

/**
 * Class for changing a property's data type.
 * Please be aware of the implication such an operation has.
 *
 * @license GPL-2.0-or-later
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
	 * @param NumericPropertyId $propertyId
	 * @param User $user User to attribute the changes made to.
	 * @param string $dataTypeId
	 * @param string $customSummary Optional custom summary to append to the automatic one.
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 */
	public function changeDataType(
		NumericPropertyId $propertyId,
		User $user,
		string $dataTypeId,
		string $customSummary = ''
	) {
		$entityRevision = $this->entityRevisionLookup->getEntityRevision(
			$propertyId,
			0,
			 LookupConstants::LATEST_FROM_MASTER
		);

		if ( $entityRevision === null ) {
			throw new StorageException( "Could not load property: " . $propertyId->getSerialization() );
		}

		/** @var Property $property */
		$property = $entityRevision->getEntity();
		'@phan-var Property $property';

		$oldDataTypeId = $property->getDataTypeId();
		$this->assertDataTypesCompatible( $oldDataTypeId, $dataTypeId );

		$property->setDataTypeId( $dataTypeId );

		$summary = 'Changed data type from ' . $oldDataTypeId . ' to ' . $dataTypeId;
		if ( $customSummary ) {
			$summary .= ': ' . $customSummary;
		}

		$this->entityStore->saveEntity(
			$property,
			$summary,
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
