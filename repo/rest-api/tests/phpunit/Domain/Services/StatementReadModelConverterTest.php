<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
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

	public function testConvert(): void {
		$id = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$dataModelStatement = NewStatement::forProperty( 'P123' )
			->withGuid( (string)$id )
			->withValue( 'potato' )
			->withQualifier( self::STRING_PROPERTY, 'my P123 qualifier value' )
			->withQualifier( self::ITEM_ID_PROPERTY_ID, new ItemId( 'Q456' ) )
			->build();

		$readModel = $this->newConverter()->convert( $dataModelStatement );

		$this->assertEquals( $id, $readModel->getGuid() );
		$this->assertSame( $dataModelStatement->getRank(), $readModel->getRank()->asInt() );
		$this->assertSame( $dataModelStatement->getPropertyId(), $readModel->getProperty()->getId() );
		$this->assertSame( 'string', $readModel->getProperty()->getDataType() );
		$this->assertSame( $dataModelStatement->getMainSnak()->getDataValue(), $readModel->getValue()->getContent() );

		$dataModelQualifier = $dataModelStatement->getQualifiers()[0];
		$readModelQualifier = $readModel->getQualifiers()[0];

		$this->assertEquals(
			$dataModelQualifier->getPropertyId(),
			$readModelQualifier->getProperty()->getId()
		);
		$this->assertSame( 'string', $readModelQualifier->getProperty()->getDataType() );
		$this->assertEquals( $dataModelQualifier->getDataValue(), $readModelQualifier->getValue()->getContent() );
		$this->assertSame( 'wikibase-item', $readModel->getQualifiers()[1]->getProperty()->getDataType() );

		$this->assertSame( $dataModelStatement->getReferences(), $readModel->getReferences() );
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
