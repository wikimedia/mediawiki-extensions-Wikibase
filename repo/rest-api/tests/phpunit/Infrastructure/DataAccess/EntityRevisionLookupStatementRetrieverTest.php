<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\StatementListProvidingEntity as StatementSubject;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupStatementRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupStatementRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupStatementRetrieverTest extends TestCase {

	use StatementReadModelHelper;

	private EntityRevisionLookup $entityRevisionLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->entityRevisionLookup = $this->createStub( EntityRevisionLookup::class );
	}

	/**
	 * @dataProvider provideStatementSubjectWithStatement
	 */
	public function testGetStatement( StatementGuid $statementId, StatementSubject $subject, Statement $expected ): void {
		$this->entityRevisionLookup = $this->newLookupForIdWithReturnValue( $subject->getId(), $subject );
		$this->assertEquals( $expected, $this->newRetriever()->getStatement( $statementId ) );
	}

	public function provideStatementSubjectWithStatement(): Generator {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$statement = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->withGuid( $statementId )
			->build();

		yield 'Item with Statement' => [
			$statementId,
			NewItem::withId( $itemId )->andStatement( $statement )->build(),
			$this->newStatementReadModelConverter()->convert( $statement ),
		];

		$propertyId = new NumericPropertyId( 'P567' );
		$statementId = new StatementGuid( $propertyId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$statement = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->withGuid( $statementId )
			->build();

		yield 'Property with Statement' => [
			$statementId,
			new Property( $propertyId, new Fingerprint(), 'string', new StatementList( $statement ) ),
			$this->newStatementReadModelConverter()->convert( $statement ),
		];
	}

	/**
	 * @dataProvider provideStatementSubjectWithStatementWriteModel
	 */
	public function testGetStatementWriteModel(
		StatementGuid $statementId,
		StatementSubject $subject,
		StatementWriteModel $expected
	): void {
		$this->entityRevisionLookup = $this->newLookupForIdWithReturnValue( $subject->getId(), $subject );
		$this->assertEquals( $expected, $this->newRetriever()->getStatementWriteModel( $statementId ) );
	}

	public function provideStatementSubjectWithStatementWriteModel(): Generator {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$statement = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->withGuid( $statementId )
			->build();

		yield 'Item with Statement' => [
			$statementId,
			NewItem::withId( $itemId )->andStatement( $statement )->build(),
			$statement,
		];

		$propertyId = new NumericPropertyId( 'P567' );
		$statementId = new StatementGuid( $propertyId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$statement = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->withGuid( $statementId )
			->build();

		yield 'Property with Statement' => [
			$statementId,
			new Property( $propertyId, new Fingerprint(), 'string', new StatementList( $statement ) ),
			$statement,
		];
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testGivenSubjectDoesNotExist_getStatementReturnsNull( EntityId $subjectId ): void {
		$statementId = new StatementGuid( $subjectId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$this->entityRevisionLookup = $this->newLookupForIdWithReturnValue( $subjectId, null );

		$this->assertNull( $this->newRetriever()->getStatement( $statementId ) );
	}

	public function provideSubjectId(): Generator {
		yield 'Item ID' => [ new ItemId( 'Q123' ) ];
		yield 'Property ID' => [ new NumericPropertyId( 'P123' ) ];
	}

	/**
	 * @dataProvider provideStatementSubjectWithoutStatement
	 */
	public function testGivenStatementDoesNotExist_getStatementReturnsNull( StatementSubject $subject ): void {
		$statementId = new StatementGuid( $subject->getId(), '69460e7f-4c45-d417-9420-9431a47969a8' );
		$this->entityRevisionLookup = $this->newLookupForIdWithReturnValue( $subject->getId(), $subject );

		$this->assertNull( $this->newRetriever()->getStatement( $statementId ) );
	}

	public function provideStatementSubjectWithoutStatement(): Generator {
		yield 'Item without Statement' => [
			NewItem::withId( new ItemId( 'Q123' ) )->build(),
		];

		yield 'Property without Statement' => [
			new Property( new NumericPropertyId( 'P567' ), new Fingerprint(), 'string' ),
		];
	}

	private function newLookupForIdWithReturnValue( EntityId $id, ?EntityDocument $returnValue ): EntityRevisionLookup {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $id )
			->willReturn( $returnValue ? new EntityRevision( $returnValue ) : null );

		return $entityRevisionLookup;
	}

	private function newRetriever(): EntityRevisionLookupStatementRetriever {
		return new EntityRevisionLookupStatementRetriever(
			new StatementSubjectRetriever( $this->entityRevisionLookup ),
			$this->newStatementReadModelConverter()
		);
	}

}
