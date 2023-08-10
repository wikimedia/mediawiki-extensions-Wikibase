<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterStatementUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\StatementUpdateFailed;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterStatementUpdaterTest extends TestCase {

	use StatementReadModelHelper;

	private StatementGuidParser $statementGuidParser;
	private StatementSubjectRetriever $statementSubjectRetriever;
	private EntityUpdater $entityUpdater;
	private StatementReadModelConverter $statementReadModelConverter;

	protected function setUp(): void {
		parent::setUp();

		$this->statementGuidParser = $this->createMock( StatementGuidParser::class );
		$this->statementSubjectRetriever = $this->createMock( StatementSubjectRetriever::class );
		$this->statementReadModelConverter = $this->newStatementReadModelConverter();
		$this->entityUpdater = $this->createMock( EntityUpdater::class );
	}

	/**
	 * @dataProvider provideStatementIdAndEntityWithStatement
	 */
	public function testUpdate( StatementGuid $statementId, StatementListProvidingEntity $statementSubject ): void {
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );

		$statementToUpdate = $statementSubject->getStatements()->getFirstStatementWithGuid( (string)$statementId );

		$expectedResultingStatement = $this->statementReadModelConverter->convert( $statementToUpdate );

		$entityRevision = new EntityRevision(
			$statementSubject,
			$expectedRevisionId,
			$expectedRevisionTimestamp
		);

		$this->entityUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $statementSubject, $editMetaData )
			->willReturn( $entityRevision );

		$this->statementGuidParser->method( 'parse' )
			->willReturn( $statementId );

		$this->statementSubjectRetriever->method( 'getStatementSubject' )
			->willReturn( $statementSubject );

		$statementRevision = $this->newStatementUpdater()->update( $statementToUpdate, $editMetaData );

		$this->assertEquals( $expectedResultingStatement, $statementRevision->getStatement() );
		$this->assertSame( $expectedRevisionId, $statementRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $statementRevision->getLastModified() );
	}

	public function testUpdateStatementWithNotFoundSubject_throws(): void {
		$statementId = new StatementGuid( new ItemId( 'Q999999' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );
		$statementToUpdate = NewStatement::forProperty( 'P321' )
			->withGuid( $statementId )
			->withValue( 'a statement value' )
			->build();

		$this->statementGuidParser->method( 'parse' )
			->willReturn( $statementId );

		$this->statementSubjectRetriever->method( 'getStatementSubject' )
			->willReturn( null );

		$this->expectException( StatementUpdateFailed::class );
		$this->newStatementUpdater()->update( $statementToUpdate, $editMetaData );
	}

	public function testUpdateStatementWithoutStatementId_throws(): void {
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );
		$statementToUpdate = NewStatement::forProperty( 'P321' )
			->withValue( 'a statement value' )
			->build();

		$this->expectException( LogicException::class );
		$this->newStatementUpdater()->update( $statementToUpdate, $editMetaData );
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

	private function newStatementUpdater(): EntityUpdaterStatementUpdater {
		return new EntityUpdaterStatementUpdater(
			$this->statementGuidParser,
			$this->statementSubjectRetriever,
			$this->entityUpdater,
			$this->statementReadModelConverter
		);
	}

}
