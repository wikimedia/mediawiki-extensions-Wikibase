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

	public function testConvert(): void {
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
		$this->assertSame( $dataModelStatement->getQualifiers(), $readModel->getQualifiers() );
		$this->assertSame( $dataModelStatement->getReferences(), $readModel->getReferences() );
	}

	private function newConverter(): StatementReadModelConverter {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::STRING_PROPERTY ), 'string' );

		return new StatementReadModelConverter(
			WikibaseRepo::getStatementGuidParser(),
			$dataTypeLookup
		);
	}

}
