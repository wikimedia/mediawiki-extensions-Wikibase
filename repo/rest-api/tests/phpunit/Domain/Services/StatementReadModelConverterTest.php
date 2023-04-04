<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Services;

use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementReadModelConverterTest extends TestCase {

	private const STRING_PROPERTY = 'P123';
	private const ITEM_ID_PROPERTY_ID = 'P456';

	public function testConvert_simpleStatement(): void {
		$id = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$dataModelStatement = NewStatement::forProperty( 'P123' )
			->withGuid( (string)$id )
			->withValue( 'potato' )
			->build();

		$readModel = $this->newConverter()->convert( $dataModelStatement );

		$this->assertEquals( $id, $readModel->getGuid() );
		$this->assertSame( $dataModelStatement->getRank(), $readModel->getRank()->asInt() );
		$this->assertSame( $dataModelStatement->getPropertyId(), $readModel->getProperty()->getId() );
		$this->assertSame( 'string', $readModel->getProperty()->getDataType() );
		$this->assertSame( $dataModelStatement->getMainSnak()->getDataValue(), $readModel->getValue()->getContent() );
	}

	public function testConvert_withQualifiers(): void {
		$dataModelStatement = NewStatement::forProperty( 'P123' )
			->withGuid( 'Q321$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->withQualifier( self::STRING_PROPERTY, 'my P123 qualifier value' )
			->withQualifier( self::ITEM_ID_PROPERTY_ID, new ItemId( 'Q456' ) )
			->build();

		$readModel = $this->newConverter()->convert( $dataModelStatement );

		$dataModelQualifier = $dataModelStatement->getQualifiers()[0];
		$readModelQualifier = $readModel->getQualifiers()[0];

		$this->assertEquals(
			$dataModelQualifier->getPropertyId(),
			$readModelQualifier->getProperty()->getId()
		);
		$this->assertSame( 'string', $readModelQualifier->getProperty()->getDataType() );
		$this->assertEquals( $dataModelQualifier->getDataValue(), $readModelQualifier->getValue()->getContent() );
		$this->assertSame( 'wikibase-item', $readModel->getQualifiers()[1]->getProperty()->getDataType() );
	}

	public function testConvert_withReferences(): void {
		$ref1Value1 = new StringValue( 'ref-1-1' );
		$ref1Value2 = new EntityIdValue( new ItemId( 'Q123' ) );
		$ref2Value1 = new StringValue( 'ref-2-1' );

		$dataModelStatement = NewStatement::forProperty( 'P123' )
			->withGuid( 'Q321$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->withReference( new Reference( [
				new PropertyValueSnak( new NumericPropertyId( self::STRING_PROPERTY ), $ref1Value1 ),
				new PropertyValueSnak( new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ), $ref1Value2 ),
			] ) )
			->withReference( new Reference( [
				new PropertyValueSnak( new NumericPropertyId( self::STRING_PROPERTY ), $ref2Value1 ),
			] ) )
			->build();

		[ $ref1, $ref2 ] = $this->newConverter()->convert( $dataModelStatement )->getReferences();

		$this->assertSame( 'string', $ref1->getParts()[0]->getProperty()->getDataType() );
		$this->assertSame( $ref1Value1, $ref1->getParts()[0]->getValue()->getContent() );

		$this->assertSame( 'wikibase-item', $ref1->getParts()[1]->getProperty()->getDataType() );
		$this->assertSame( $ref1Value2, $ref1->getParts()[1]->getValue()->getContent() );

		$this->assertSame( 'string', $ref2->getParts()[0]->getProperty()->getDataType() );
		$this->assertSame( $ref2Value1, $ref2->getParts()[0]->getValue()->getContent() );
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
