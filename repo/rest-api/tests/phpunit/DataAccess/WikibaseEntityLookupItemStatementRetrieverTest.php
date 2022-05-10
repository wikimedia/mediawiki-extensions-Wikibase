<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemStatementRetriever;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemStatementRetriever
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemStatementRetrieverTest extends TestCase {

	public function testGetStatement(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, "c48c32c3-42b5-498f-9586-84608b88747c" );

		$statement = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->withGuid( $statementId )
			->build();
		$item = NewItem::withId( $itemId )
			->andStatement( $statement )
			->build();

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $item->getId() )
			->willReturn( $item );

		$retriever = new WikibaseEntityLookupItemStatementRetriever( $entityLookup );

		$this->assertEquals(
			$statement,
			$retriever->getStatement( $statementId )
		);
	}

	public function testGivenItemDoesNotExist_returnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$statementId = new StatementGuid( $itemId, "c48c32c3-42b5-498f-9586-84608b88747c" );

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $itemId )
			->willReturn( null );

		$retriever = new WikibaseEntityLookupItemStatementRetriever( $entityLookup );

		$this->assertNull( $retriever->getStatement( $statementId ) );
	}

	public function testGivenStatementDoesNotExist_returnsNull(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, "c48c32c3-42b5-498f-9586-84608b88747c" );

		$item = NewItem::withId( $itemId )
			->build();

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $itemId )
			->willReturn( $item );

		$retriever = new WikibaseEntityLookupItemStatementRetriever( $entityLookup );

		$this->assertNull( $retriever->getStatement( $statementId ) );
	}
}
