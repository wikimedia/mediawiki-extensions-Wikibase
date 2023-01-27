<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
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

	public function testConvert(): void {
		$id = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$dataModelStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( (string)$id )
			->build();

		$readModel = ( new StatementReadModelConverter( WikibaseRepo::getStatementGuidParser() ) )
			->convert( $dataModelStatement );

		$this->assertEquals( $id, $readModel->getGuid() );
		$this->assertSame( $dataModelStatement->getRank(), $readModel->getRank() );
		$this->assertSame( $dataModelStatement->getMainSnak(), $readModel->getMainSnak() );
		$this->assertSame( $dataModelStatement->getQualifiers(), $readModel->getQualifiers() );
		$this->assertSame( $dataModelStatement->getReferences(), $readModel->getReferences() );
	}

}
