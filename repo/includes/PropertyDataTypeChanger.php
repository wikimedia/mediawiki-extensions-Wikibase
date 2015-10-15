<?php

namespace Wikibase\Repo;

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
 * @since 0.5
 *
 * @license GNU GPL v2+
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
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityStore $entityStore
	 */
	public function __construct( EntityRevisionLookup $entityRevisionLookup, EntityStore $entityStore ) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
	}

	/**
	 * @throws StorageException
	 *
	 * @param PropertyId $propertyId
	 * @param string $dataTypeId
	 */
	public function changeDataType( PropertyId $propertyId, $dataTypeId ) {
		$entityRevision = $this->entityRevisionLookup->getEntityRevision(
			$propertyId,
			EntityRevisionLookup::LATEST_FROM_MASTER
		);

		if ( $entityRevision === null ) {
			throw new StorageException( "Couldn't load property: " . $propertyId->getSerialization() );
		}

		/* @var $property Property */
		$property = $entityRevision->getEntity();

		$oldDataTypeId = $property->getDataTypeId();
		$property->setDataTypeId( $dataTypeId );

		// XXX: Use some(thing|one) else? Inject?
		$user = User::newFromId( 0 );
		$user->setName( '127.0.0.1' );

		$this->entityStore->saveEntity(
			$property,
			'Changing DataType from ' . $oldDataTypeId . ' to ' . $dataTypeId,
			$user,
			EDIT_UPDATE,
			$entityRevision->getRevisionId()
		);
	}

}
