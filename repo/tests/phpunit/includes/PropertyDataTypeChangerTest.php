<?php

namespace Wikibase\Repo\Tests;

use InvalidArgumentException;
use User;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\PropertyDataTypeChanger;

/**
 * @covers \Wikibase\Repo\PropertyDataTypeChanger
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class PropertyDataTypeChangerTest extends \PHPUnit\Framework\TestCase {

	public function testChangeDataType_success() {
		$propertyId = new NumericPropertyId( 'P42' );

		$expectedProperty = new Property( $propertyId, null, 'shinydata' );

		$entityStore = $this->createMock( EntityStore::class );
		$entityStore->expects( $this->once() )
			->method( 'saveEntity' )
			->with(
				$expectedProperty,
				'Changed data type from rustydata to shinydata',
				$this->isInstanceOf( User::class ),
				EDIT_UPDATE, 6789
			)
			->willReturn( new EntityRevision( $expectedProperty, 6790 ) );

		$propertyDataTypeChanger = $this->getPropertyDataTypeChanger( $entityStore );
		$propertyDataTypeChanger->changeDataType( $propertyId, $this->createMock( User::class ), 'shinydata' );
	}

	public function testChangeDataType_customSummary() {
		$propertyId = new NumericPropertyId( 'P42' );

		$expectedProperty = new Property( $propertyId, null, 'shinydata' );

		$entityStore = $this->createMock( EntityStore::class );
		$entityStore->expects( $this->once() )
			->method( 'saveEntity' )
			->with(
				$expectedProperty,
				'Changed data type from rustydata to shinydata: [[phabricator:T1|T1]]',
				$this->isInstanceOf( User::class ),
				EDIT_UPDATE, 6789
			)
			->willReturn( new EntityRevision( $expectedProperty, 6790 ) );

		$propertyDataTypeChanger = $this->getPropertyDataTypeChanger( $entityStore );
		$propertyDataTypeChanger->changeDataType(
			$propertyId,
			$this->createMock( User::class ),
			'shinydata',
			'[[phabricator:T1|T1]]'
		);
	}

	public function testChangeDataType_propertyNotFound() {
		$propertyId = new NumericPropertyId( 'P43' );

		$entityStore = $this->createMock( EntityStore::class );

		$propertyDataTypeChanger = $this->getPropertyDataTypeChanger( $entityStore );

		$this->expectException( StorageException::class );
		$this->expectExceptionMessage( "Could not load property: P43" );
		$propertyDataTypeChanger->changeDataType( $propertyId, $this->createMock( User::class ), 'shinydata' );
	}

	public function testChangeDataType_saveFailed() {
		$propertyId = new NumericPropertyId( 'P42' );

		$expectedProperty = new Property( $propertyId, null, 'shinydata' );
		$storageException = new StorageException( 'whatever' );

		$entityStore = $this->createMock( EntityStore::class );
		$entityStore->expects( $this->once() )
			->method( 'saveEntity' )
			->with(
				$expectedProperty,
				'Changed data type from rustydata to shinydata',
				$this->isInstanceOf( User::class ),
				EDIT_UPDATE, 6789
			)
			->willThrowException( $storageException );

		$propertyDataTypeChanger = $this->getPropertyDataTypeChanger( $entityStore );

		$this->expectException( StorageException::class );
		$propertyDataTypeChanger->changeDataType( $propertyId, $this->createMock( User::class ), 'shinydata' );
	}

	public function testChangeDataType_mismatchingDataValueTypes() {
		$propertyId = new NumericPropertyId( 'P42' );

		$entityStore = $this->createMock( EntityStore::class );

		$propertyDataTypeChanger = $this->getPropertyDataTypeChanger( $entityStore );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( "New and old data type must have the same data value type." );
		$propertyDataTypeChanger->changeDataType( $propertyId, $this->createMock( User::class ), 'otherdatatype' );
	}

	private function getPropertyDataTypeChanger( EntityStore $entityStore ) {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );

		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with(
				$this->isInstanceOf( NumericPropertyId::class ),
				0,
				 LookupConstants::LATEST_FROM_MASTER
			)
			->willReturnCallback( function( NumericPropertyId $propertyId ) {
				if ( $propertyId->getSerialization() === 'P42' ) {
					$property = new Property(
						new NumericPropertyId( 'P42' ),
						null,
						'rustydata'
					);

					return new EntityRevision( $property, 6789, '20151015195144' );
				} else {
					return null;
				}
			} );

		return new PropertyDataTypeChanger( $entityRevisionLookup, $entityStore, $this->getDataTypeFactory() );
	}

	private function getDataTypeFactory() {
		$dataTypes = [];
		$dataTypes[] = new DataType( 'rustydata', 'kittens' );
		$dataTypes[] = new DataType( 'shinydata', 'kittens' );
		$dataTypes[] = new DataType( 'otherdatatype', 'puppies' );

		return DataTypeFactory::newFromTypes( $dataTypes );
	}

}
