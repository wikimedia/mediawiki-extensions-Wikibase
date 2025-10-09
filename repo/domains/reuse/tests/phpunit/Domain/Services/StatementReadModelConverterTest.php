<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Domain\Services;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Statement\StatementGuid;
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

	public function testConvert_simpleStatement(): void {
		$id = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$statementWriteModel = NewStatement::noValueFor( 'P123' )
			->withGuid( (string)$id )
			->build();

		$readModel = $this->newConverter()->convert( $statementWriteModel );

		$this->assertEquals( $id, $readModel->id );
		$this->assertSame( $statementWriteModel->getPropertyId(), $readModel->property->id );
		$this->assertSame( 'string', $readModel->property->dataType );
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
