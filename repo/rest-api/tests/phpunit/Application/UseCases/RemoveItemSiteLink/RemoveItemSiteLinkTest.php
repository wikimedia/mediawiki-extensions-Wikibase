<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveItemSiteLink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLink;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLinkRequest;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RemoveItemSiteLinkTest extends TestCase {

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = 'enwiki';

		$item = NewItem::withId( $itemId )->andSiteLink( $siteId, 'dog page' )->build();
		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( $item );
		$request = new RemoveItemSiteLinkRequest( "$itemId", $siteId );

		( new RemoveItemSiteLink( $itemRepo, $itemRepo ) )->execute( $request );

		$this->assertFalse( $itemRepo->getItem( $itemId )->hasLinkToSite( $siteId ) );
	}
}
