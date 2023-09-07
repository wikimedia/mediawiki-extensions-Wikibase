<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterStatementRemover;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\StatementSubjectDisappeared;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterStatementRemover
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterStatementRemoverTest extends TestCase {
	private EntityUpdater $entityUpdater;
	private StatementSubjectRetriever $statementSubjectRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->entityUpdater = $this->createStub( EntityUpdater::class );
		$this->statementSubjectRetriever = $this->createStub( StatementSubjectRetriever::class );
	}

	/**
	 * @dataProvider provideStatementIdAndEntityWithStatement
	 */
	public function testRemove( StatementGuid $statementId, StatementListProvidingEntity $statementSubject ): void {
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );

		$entityRevision = new EntityRevision(
			$statementSubject,
			$expectedRevisionId,
			$expectedRevisionTimestamp
		);

		$this->entityUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $statementSubject, $editMetaData )
			->willReturn( $entityRevision );

		$this->statementSubjectRetriever->method( 'getStatementSubject' )
			->willReturn( $statementSubject );

		$this->newStatementRemover()->remove( $statementId, $editMetaData );
	}

	public function testRemoveStatementWithNotFoundSubject_throws(): void {
		$statementId = new StatementGuid( new ItemId( 'Q999999' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );

		$this->statementSubjectRetriever->method( 'getStatementSubject' )
			->willReturn( null );

		$this->expectException( StatementSubjectDisappeared::class );
		$this->newStatementRemover()->remove( $statementId, $editMetaData );
	}

	public function provideStatementIdAndEntityWithStatement(): Generator {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$statement = NewStatement::forProperty( 'P321' )
			->withGuid( $statementId )
			->withValue( 'a statement value' )
			->build();
		yield 'item with statement' => [ $statementId, NewItem::withStatement( $statement )->build() ];

		$statementId = new StatementGuid( new NumericPropertyId( 'P123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$statement = NewStatement::forProperty( 'P321' )
			->withGuid( $statementId )
			->withValue( 'a statement value' )
			->build();
		$property = Property::newFromType( 'string' );
		$property->setStatements( new StatementList( $statement ) );
		yield 'property with statement' => [ $statementId, $property ];
	}

	private function newStatementRemover(): EntityUpdaterStatementRemover {
		return new EntityUpdaterStatementRemover(
			$this->statementSubjectRetriever,
			$this->entityUpdater
		);
	}

}
