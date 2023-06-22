<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetrieverTest extends TestCase {

	/**
	 * @dataProvider provideStatementSubjectId
	 */
	public function testGivenConcreteRevision_getLatestRevisionMetadataReturnsMetadata( EntityId $subjectId ): void {
		$expectedRevisionId = 777;
		$expectedRevisionTimestamp = '20201111070707';
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $subjectId )
			->willReturn( LatestRevisionIdResult::concreteRevision( $expectedRevisionId, $expectedRevisionTimestamp ) );

		$metaDataRetriever = new WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $subjectId );

		$this->assertSame( $expectedRevisionId, $result->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $result->getRevisionTimestamp() );
	}

	public function testGivenRedirect_getLatestRevisionMetadataReturnsRedirectResult(): void {
		$itemWithRedirect = new ItemId( 'Q4321' );

		$statementWithRedirectItem = new StatementGuid(
			$itemWithRedirect,
			'FFFFFFFF-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
		);

		$redirectTarget = new ItemId( 'Q1234' );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $itemWithRedirect )
			->willReturn( LatestRevisionIdResult::redirect( 9876, $redirectTarget ) );

		$metaDataRetriever = new WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $itemWithRedirect );

		$this->assertTrue( $result->isRedirect() );
		$this->assertSame( $redirectTarget, $result->getRedirectTarget() );
	}

	/**
	 * @dataProvider provideStatementSubjectId
	 */
	public function testGivenStatementSubjectDoesNotExist_getLatestRevisionMetadataReturnsStatementSubjectNotFoundResult(
		EntityId $subjectId
	): void {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $subjectId )
			->willReturn( LatestRevisionIdResult::nonexistentEntity() );

		$metaDataRetriever = new WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $subjectId );

		$this->assertFalse( $result->subjectExists() );
	}

	public function provideStatementSubjectId(): Generator {
		yield 'item id' => [ new ItemId( 'Q123' ) ];
		yield 'property id' => [ new NumericPropertyId( 'P123' ) ];
	}

}
