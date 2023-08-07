<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Fixtures\CustomEntityId;
use Wikibase\DataModel\Services\Fixtures\FakeEntityDocument;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementSubjectRetrieverTest extends TestCase {

	private EntityRevisionLookup $entityRevisionLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->entityRevisionLookup = $this->createStub( EntityRevisionLookup::class );
	}

	/**
	 * @dataProvider provideSubjectAndSubjectId
	 */
	public function testGetStatementSubject( EntityId $subjectId, EntityDocument $expectedSubject ): void {
		$this->entityRevisionLookup = $this->newLookupForIdWithReturnValue( $subjectId, $expectedSubject );
		$subject = $this->newStatementSubjectRetriever()->getStatementSubject( $subjectId );

		$this->assertEquals( $expectedSubject, $subject );
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testGivenSubjectDoesNotExist_getStatementSubjectReturnsNull( EntityId $subjectId ): void {
		$this->entityRevisionLookup = $this->newLookupForIdWithReturnValue( $subjectId, null );

		$this->assertNull( $this->newStatementSubjectRetriever()->getStatementSubject( $subjectId ) );
	}

	public function testGivenEntityIsNotStatementSubject_getStatementSubjectThrows(): void {
		$subjectId = new CustomEntityId( 'A123' );
		$subject = new FakeEntityDocument( $subjectId );
		$this->entityRevisionLookup = $this->newLookupForIdWithReturnValue( $subjectId, $subject );

		try {
			$this->newStatementSubjectRetriever()->getStatementSubject( $subjectId );
			$this->fail( 'Expected exception not thrown' );
		} catch ( LogicException $e ) {
			$this->assertEquals( 'Entity is not a ' . StatementListProvidingEntity::class, $e->getMessage() );
		}
	}

	public function testGivenItemRedirected_getStatementSubjectReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$this->entityRevisionLookup = $this->newLookupForIdWithRedirect( $itemId );

		$this->assertNull( $this->newStatementSubjectRetriever()->getStatementSubject( $itemId ) );
	}

	public function provideSubjectAndSubjectId(): Generator {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$statement = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->withGuid( $statementId )
			->build();

		yield 'Item' => [ $itemId, NewItem::withId( $itemId )->andStatement( $statement )->build() ];

		$propertyId = new NumericPropertyId( 'P567' );
		$statementId = new StatementGuid( $propertyId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$statement = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->withGuid( $statementId )
			->build();

		yield 'Property' => [
			$propertyId,
			new Property( $propertyId, new Fingerprint(), 'string', new StatementList( $statement ) ),
		];
	}

	public function provideSubjectId(): Generator {
		yield 'Item ID' => [ new ItemId( 'Q123' ) ];
		yield 'Property ID' => [ new NumericPropertyId( 'P123' ) ];
	}

	private function newLookupForIdWithRedirect( EntityId $id ): EntityRevisionLookup {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $id )
			->willThrowException( $this->createStub( RevisionedUnresolvedRedirectException::class ) );

		return $entityRevisionLookup;
	}

	private function newLookupForIdWithReturnValue( EntityId $id, ?EntityDocument $returnValue ): EntityRevisionLookup {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $id )
			->willReturn( $returnValue ? new EntityRevision( $returnValue ) : null );

		return $entityRevisionLookup;
	}

	private function newStatementSubjectRetriever(): StatementSubjectRetriever {
		return new StatementSubjectRetriever( $this->entityRevisionLookup );
	}

}
