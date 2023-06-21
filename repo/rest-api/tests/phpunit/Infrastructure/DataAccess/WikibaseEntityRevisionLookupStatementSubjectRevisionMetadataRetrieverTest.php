<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
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
	 * @dataProvider provideStatementId
	 */
	public function testGivenConcreteRevision_getLatestRevisionMetadataReturnsMetadata( StatementGuid $statementId ): void {
		$expectedRevisionId = 777;
		$expectedRevisionTimestamp = '20201111070707';
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $statementId->getEntityId() )
			->willReturn( LatestRevisionIdResult::concreteRevision( $expectedRevisionId, $expectedRevisionTimestamp ) );

		$metaDataRetriever = new WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $statementId );

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
		$result = $metaDataRetriever->getLatestRevisionMetadata( $statementWithRedirectItem );

		$this->assertTrue( $result->isRedirect() );
		$this->assertSame( $redirectTarget, $result->getRedirectTarget() );
	}

	/**
	 * @dataProvider provideStatementId
	 */
	public function testGivenStatementSubjectDoesNotExist_getLatestRevisionMetadataReturnsStatementSubjectNotFoundResult(
		StatementGuid $statementId
	): void {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $statementId->getEntityId() )
			->willReturn( LatestRevisionIdResult::nonexistentEntity() );

		$metaDataRetriever = new WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $statementId );

		$this->assertFalse( $result->subjectExists() );
	}

	public function provideStatementId(): Generator {
		yield 'statement ID on an item' => [
			new StatementGuid(
				new ItemId( 'Q123' ),
				'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF'
			),
		];
		yield 'statement ID on a property' => [
			new StatementGuid(
				new NumericPropertyId( 'P123' ),
				'D8404CDA-25E4-4334-AF13-A3290BCD9C0N'
			),
		];
	}

}
