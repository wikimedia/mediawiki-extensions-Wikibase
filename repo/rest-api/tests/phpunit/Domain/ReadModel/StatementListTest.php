<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement as DataModelStatement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\StatementList
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementListTest extends TestCase {

	public function testGetStatementById(): void {
		$id = new StatementGuid( new ItemId( 'Q42' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$expectedStatement = $this->newStatementWithId( $id );

		$this->assertSame(
			$expectedStatement,
			( new StatementList( $expectedStatement ) )->getStatementById( $id )
		);
	}

	public function testGivenStatementWithIdDoesNotExist_getStatementByIdReturnsNull(): void {
		$actualStatementId = new StatementGuid( new ItemId( 'Q42' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$requestedStatementId = new StatementGuid( new ItemId( 'Q42' ), 'FFFFFFFF-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );

		$this->assertNull(
			( new StatementList( $this->newStatementWithId( $actualStatementId ) ) )
				->getStatementById( $requestedStatementId )
		);
	}

	private function newStatementWithId( StatementGuid $id ): Statement {
		return new Statement(
			$id,
			DataModelStatement::RANK_NORMAL,
			$this->createStub( Snak::class ),
			new SnakList(),
			new ReferenceList()
		);
	}
}
