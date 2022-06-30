<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Serializers;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory as LegacySerializerFactory;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\RestApi\Domain\Model\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\Serializers\SerializerFactory;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Serializers\SerializerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SerializerFactoryTest extends TestCase {

	private const STUB_SNAK_SERIALIZATION = [ 'some' => 'snak' ];
	private const PROPERTY_DATA = [
		[ 'propertyId' => 'P1', 'datatype' => 'string' ],
		[ 'propertyId' => 'P123', 'datatype' => 'quantity' ],
		[ 'propertyId' => 'P321', 'datatype' => 'item' ],
	];

	public function testNewStatementSerializer(): void {
		$statement = NewStatement::someValueFor( self::PROPERTY_DATA[0]['propertyId'] )->build();

		$serializedStatement = $this->newSerializerFactory()
			->newStatementSerializer()
			->serialize( $statement );

		$this->assertEquals(
			self::STUB_SNAK_SERIALIZATION + [ 'datatype' => self::PROPERTY_DATA[0]['datatype'] ],
			$serializedStatement['mainsnak']
		);
	}

	public function testNewStatementListSerializer(): void {
		$statementList = new StatementList(
			NewStatement::someValueFor( self::PROPERTY_DATA[1]['propertyId'] )->build()
		);

		$serializedStatementList = $this->newSerializerFactory()
			->newStatementListSerializer()
			->serialize( $statementList );

		$this->assertEquals(
			self::STUB_SNAK_SERIALIZATION + [ 'datatype' => self::PROPERTY_DATA[1]['datatype'] ],
			$serializedStatementList->{self::PROPERTY_DATA[1]['propertyId']}[0]['mainsnak']
		);
	}

	public function testNewItemDataSerializer(): void {
		$itemData = ( new ItemDataBuilder() )->setId( new ItemId( 'Q123' ) )
			->setStatements( new StatementList(
				NewStatement::someValueFor( self::PROPERTY_DATA[2]['propertyId'] )->build()
			) )
			->build();

		$serializedItemData = $this->newSerializerFactory()
			->newItemDataSerializer()
			->serialize( $itemData );

		$this->assertEquals(
			self::STUB_SNAK_SERIALIZATION + [ 'datatype' => self::PROPERTY_DATA[2]['datatype'] ],
			$serializedItemData['statements']->{self::PROPERTY_DATA[2]['propertyId']}[0]['mainsnak']
		);
	}

	private function newSerializerFactory(): SerializerFactory {
		$propertyDataTypeLookup = new InMemoryDataTypeLookup();
		foreach ( self::PROPERTY_DATA as $property ) {
			$propertyDataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $property['propertyId'] ), $property['datatype'] );
		}

		$legacySnakSerializer = $this->createStub( SnakSerializer::class );
		$legacySnakSerializer->method( 'serialize' )->willReturn( self::STUB_SNAK_SERIALIZATION );

		$legacySerializerFactory = $this->createStub( LegacySerializerFactory::class );
		$legacySerializerFactory->method( 'newSnakSerializer' )->willReturn( $legacySnakSerializer );

		return new SerializerFactory(
			$legacySerializerFactory,
			$propertyDataTypeLookup
		);
	}

}
