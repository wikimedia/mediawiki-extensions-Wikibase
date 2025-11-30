<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Domain\Services;

use DataValues\StringValue;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\Domains\Reuse\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Domain\Services\StatementReadModelConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementReadModelConverterTest extends TestCase {

	private const STRING_PROPERTY = 'P123';
	private const ITEM_ID_PROPERTY_ID = 'P456';

	public function testConvert_simpleStatement(): void {
		$stringValue = new StringValue( 'string property' );
		$statementWriteModel = NewStatement::forProperty( self::STRING_PROPERTY )
			->withSomeGuid()
			->withValue( $stringValue )
			->build();

		$readModel = $this->newConverter()->convert( $statementWriteModel );

		$this->assertEquals( $statementWriteModel->getGuid(), $readModel->id );
		$this->assertEquals( $statementWriteModel->getRank(), $readModel->rank->asInt() );
		$this->assertEquals( $statementWriteModel->getPropertyId(), $readModel->property->id );
		$this->assertEquals( 'string', $readModel->property->dataType );
		$this->assertSame( $stringValue, $readModel->value );
		$this->assertEquals( 'value', $readModel->valueType->value );
		$this->assertCount( 0, $readModel->references );
		$this->assertCount( 0, $readModel->qualifiers->getQualifiersByPropertyId( new NumericPropertyId( 'P6' ) ) );
	}

	/**
	 * @dataProvider valueTypesProvider
	 */
	public function testValueTypesWithNullValue(
		NewStatement $statementBuilder,
		string $expectedValueType
	): void {
		$statementWriteModel = $statementBuilder->withSomeGuid()->build();
		$readModel = $this->newConverter()->convert( $statementWriteModel );

		$this->assertSame( $expectedValueType, $readModel->valueType->value );
		$this->assertNull( $readModel->value );
	}

	public static function valueTypesProvider(): Generator {
		yield 'no value' => [ NewStatement::noValueFor( 'P123' ), 'novalue' ];
		yield 'some value' => [ NewStatement::someValueFor( 'P123' ), 'somevalue' ];
	}

	public function testConvert_withQualifiers(): void {
		$statementWriteModel = NewStatement::forProperty( 'P123' )
			->withSomeGuid()
			->withQualifier( self::STRING_PROPERTY, 'my P123 qualifier value' )
			->withQualifier( self::ITEM_ID_PROPERTY_ID, new ItemId( 'Q456' ) )
			->build();

		$readModel = $this->newConverter()->convert( $statementWriteModel );

		$qualifierWriteModel = $statementWriteModel->getQualifiers();
		$readModelStringQualifier = $readModel->qualifiers->getQualifiersByPropertyId(
			new NumericPropertyId( self::STRING_PROPERTY )
		);
		$readModelItemQualifier = $readModel->qualifiers->getQualifiersByPropertyId(
			new NumericPropertyId( self::ITEM_ID_PROPERTY_ID )
		);

		$this->assertSame( 'string', $readModelStringQualifier[0]->property->dataType );
		$this->assertEquals( $qualifierWriteModel[0]->getPropertyId(), $readModelStringQualifier[0]->property->id );
		$this->assertEquals( $qualifierWriteModel[0]->getDataValue(), $readModelStringQualifier[0]->value );
		$this->assertEquals( 'value', $readModelStringQualifier[0]->valueType->value );

		$this->assertSame( 'wikibase-item', $readModelItemQualifier[0]->property->dataType );
		$this->assertEquals( $qualifierWriteModel[1]->getPropertyId(), $readModelItemQualifier[0]->property->id );
		$this->assertEquals( $qualifierWriteModel[1]->getDataValue(), $readModelItemQualifier[0]->value );
		$this->assertEquals( 'value', $readModelItemQualifier[0]->valueType->value );
	}

	public function testConvert_withReferences(): void {
		$ref1Value1 = new StringValue( 'ref-1-1' );
		$ref1Value2 = new EntityIdValue( new ItemId( 'Q123' ) );
		$ref2Value1 = new StringValue( 'ref-2-1' );

		$statementWriteModel = NewStatement::forProperty( 'P123' )
			->withGuid( 'Q321$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->withReference( new Reference( [
				new PropertyValueSnak( new NumericPropertyId( self::STRING_PROPERTY ), $ref1Value1 ),
				new PropertyValueSnak( new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ), $ref1Value2 ),
			] ) )
			->withReference( new Reference( [
				new PropertyValueSnak( new NumericPropertyId( self::STRING_PROPERTY ), $ref2Value1 ),
			] ) )
			->build();

		[ $ref1, $ref2 ] = $this->newConverter()->convert( $statementWriteModel )->references;

		$this->assertSame( 'string', $ref1->parts[0]->property->dataType );
		$this->assertSame( $ref1Value1, $ref1->parts[0]->value );

		$this->assertSame( 'wikibase-item', $ref1->parts[1]->property->dataType );
		$this->assertSame( $ref1Value2, $ref1->parts[1]->value );

		$this->assertSame( 'string', $ref2->parts[0]->property->dataType );
		$this->assertSame( $ref2Value1, $ref2->parts[0]->value );
	}

	public function testConvert_throwsWhenGuidIsNull(): void {
		$statementWriteModel = NewStatement::noValueFor( 'P123' )->build();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can only convert statements that have a non-null GUID' );

		$this->newConverter()->convert( $statementWriteModel );
	}

	public function testDeletedPropertyYieldsNullDataType(): void {
		$statementWriteModel = NewStatement::forProperty( 'P3' )
			->withSomeGuid()
			->build();

		$readModel = $this->newConverter()->convert( $statementWriteModel );

		$this->assertNull( $readModel->property->dataType );
	}

	private function newConverter(): StatementReadModelConverter {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::STRING_PROPERTY ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ), 'wikibase-item' );

		return new StatementReadModelConverter(
			WikibaseRepo::getStatementGuidParser(),
			$dataTypeLookup
		);
	}

}
