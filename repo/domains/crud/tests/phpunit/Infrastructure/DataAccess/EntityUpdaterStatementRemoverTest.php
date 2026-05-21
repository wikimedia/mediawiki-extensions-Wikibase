<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

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
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditSummary;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterStatementRemover;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\Exceptions\StatementSubjectDisappeared;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\StatementSubjectRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterStatementRemover
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterStatementRemoverTest extends TestCase {
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

		$entityUpdater = $this->createMock( EntityUpdater::class );
		$entityUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $statementSubject, $editMetaData )
			->willReturn( $entityRevision );

		$statementSubjectRetriever = $this->createStub( StatementSubjectRetriever::class );
		$statementSubjectRetriever->method( 'getStatementSubject' )
			->willReturn( $statementSubject );

		$this->newStatementRemover( $statementSubjectRetriever, $entityUpdater )
			->remove( $statementId, $editMetaData );
	}

	public function testRemoveStatementWithNotFoundSubject_throws(): void {
		$statementId = new StatementGuid( new ItemId( 'Q999999' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );

		$entityUpdater = $this->createStub( EntityUpdater::class );
		$statementSubjectRetriever = $this->createStub( StatementSubjectRetriever::class );
		$statementSubjectRetriever->method( 'getStatementSubject' )
			->willReturn( null );

		$this->expectException( StatementSubjectDisappeared::class );
		$this->newStatementRemover( $statementSubjectRetriever, $entityUpdater )
			->remove( $statementId, $editMetaData );
	}

	public static function provideStatementIdAndEntityWithStatement(): Generator {
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

	private function newStatementRemover(
		StatementSubjectRetriever $statementSubjectRetriever,
		EntityUpdater $entityUpdater
	): EntityUpdaterStatementRemover {
		return new EntityUpdaterStatementRemover(
			$statementSubjectRetriever,
			$entityUpdater
		);
	}

}
