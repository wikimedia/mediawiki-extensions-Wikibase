<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemRetriever;
use Wikibase\Repo\Tests\NewItem;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemRetriever
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemRetrieverTest extends MediaWikiIntegrationTestCase {

	public function testGetItem(): void {
		$item = NewItem::withId( 'Q123' )->build();
		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $item->getId() )
			->willReturn( $item );

		$retriever = new WikibaseEntityLookupItemRetriever( $entityLookup );

		$this->assertEquals(
			$item,
			$retriever->getItem( $item->getId() )
		);
	}

}
