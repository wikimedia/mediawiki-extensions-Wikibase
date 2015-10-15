<?php

namespace Wikibase\Repo\Test;

use PHPUnit_Framework_TestCase;
use Status;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\PropertyDataTypeChanger;

/**
 * @covers Wikibase\Repo\PropertyDataTypeChanger;
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Marius Hoch
 */
class PropertyDataTypeChangerTest extends PHPUnit_Framework_TestCase {

	public function testChangeDataType_success() {
		$propertyId = new PropertyId( 'P42' );

		$expectedProperty = new Property( $propertyId, null, 'shinydata' );

		$entityStore = $this->getMock( 'Wikibase\Lib\Store\EntityStore' );
		$entityStore->expects( $this->once() )
			->method( 'saveEntity' )
			->with(
				$expectedProperty,
				'Changing DataType from rustydata to shinydata',
				$this->isInstanceOf( 'User' ),
				EDIT_UPDATE, 6789
			)
			->will( $this->returnValue( Status::newGood() ) );

		$propertyDataTypeChanger = $this->getPropertyDataTypeChanger( $entityStore );
		$propertyDataTypeChanger->changeDataType( $propertyId, 'shinydata' );
	}

	public function testChangeDataType_propertyNotFound() {
		$propertyId = new PropertyId( 'P43' );

		$entityStore = $this->getMock( 'Wikibase\Lib\Store\EntityStore' );

		$propertyDataTypeChanger = $this->getPropertyDataTypeChanger( $entityStore );

		$this->setExpectedException( 'Wikibase\Lib\Store\StorageException' );
		$propertyDataTypeChanger->changeDataType( $propertyId, 'shinydata' );
	}

	public function testChangeDataType_saveFailed() {
		$propertyId = new PropertyId( 'P42' );

		$expectedProperty = new Property( $propertyId, null, 'shinydata' );
		$storageException = new StorageException( 'whatever' );

		$entityStore = $this->getMock( 'Wikibase\Lib\Store\EntityStore' );
		$entityStore->expects( $this->once() )
			->method( 'saveEntity' )
			->with(
				$expectedProperty,
				'Changing DataType from rustydata to shinydata',
				$this->isInstanceOf( 'User' ),
				EDIT_UPDATE, 6789
			)
			->will( $this->throwException( $storageException ) );

		$propertyDataTypeChanger = $this->getPropertyDataTypeChanger( $entityStore );

		$this->setExpectedException( 'Wikibase\Lib\Store\StorageException' );
		$propertyDataTypeChanger->changeDataType( $propertyId, 'shinydata' );
	}

	private function getPropertyDataTypeChanger( EntityStore $entityStore ) {
		$entityRevisionLookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );

		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->will( $this->returnCallback( function( PropertyId $propertyId ) {
				if ( $propertyId->getSerialization() === 'P42' ) {
					$property = new Property(
						new PropertyId( 'P42' ),
						null,
						'rustydata'
					);

					return new EntityRevision( $property, 6789, '20151015195144' );
				} else {
					return null;
				}
			} ) );

		return new PropertyDataTypeChanger( $entityRevisionLookup, $entityStore );
	}

}
