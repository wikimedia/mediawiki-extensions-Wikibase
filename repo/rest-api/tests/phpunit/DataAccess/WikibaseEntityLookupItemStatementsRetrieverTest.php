<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemStatementsRetriever;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemStatementsRetriever
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemStatementsRetrieverTest extends TestCase {

	public function testGetStatements(): void {
		$statement1 = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->build();
		$statement2 = NewStatement::forProperty( 'P321' )
			->withValue( 'banana' )
			->build();

		$item = NewItem::withId( 'Q123' )
			->andStatement( $statement1 )
			->andStatement( $statement2 )
			->build();

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $item->getId() )
			->willReturn( $item );

		$retriever = new WikibaseEntityLookupItemStatementsRetriever( $entityLookup );

		$this->assertEquals(
			new StatementList( $statement1, $statement2 ),
			$retriever->getStatements( $item->getId() )
		);
	}

	public function testGivenItemDoesNotExist_returnsNull(): void {
		$nonexistentItemId = new ItemId( 'Q321' );
		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $nonexistentItemId )
			->willReturn( null );

		$retriever = new WikibaseEntityLookupItemStatementsRetriever( $entityLookup );

		$this->assertNull( $retriever->getStatements( $nonexistentItemId ) );
	}

}
