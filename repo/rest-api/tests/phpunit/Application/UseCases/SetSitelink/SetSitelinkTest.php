<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetSitelink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelink;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelinkRequest;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetSitelinkTest extends TestCase {

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testAddSitelink(): void {
		$itemId = 'Q123';
		$siteId = InMemoryItemRepository::EN_WIKI_SITE_ID;
		$title = 'Potato';
		$badge = 'Q567';

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new SetSitelinkRequest(
				$itemId,
				$siteId,
				[ 'title' => $title, 'badges' => [ $badge ] ],
				[],
				false,
				''
			)
		);

		$this->assertEquals(
			new SiteLink( $siteId, $title, [ new ItemId( $badge ) ], InMemoryItemRepository::EN_WIKI_URL_PREFIX . $title
			),
			$response->getSitelink()
		);
		$this->assertSame( $itemRepo->getLatestRevisionId( new ItemId( $itemId ) ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( new ItemId( $itemId ) ), $response->getLastModified() );
		$this->assertFalse( $response->wasReplaced() );
	}

	public function testReplaceSitelink(): void {
		$itemId = 'Q123';
		$siteId = InMemoryItemRepository::EN_WIKI_SITE_ID;
		$title = 'Potato';
		$badge = 'Q567';

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->andSiteLink( $siteId, $title, [] )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new SetSitelinkRequest(
				$itemId,
				$siteId,
				[ 'title' => $title, 'badges' => [ $badge ] ],
				[],
				false,
				''
			)
		);

		$this->assertEquals(
			new SiteLink( $siteId, $title, [ new ItemId( $badge ) ], InMemoryItemRepository::EN_WIKI_URL_PREFIX . $title
			),
			$response->getSitelink()
		);
		$this->assertSame( $itemRepo->getLatestRevisionId( new ItemId( $itemId ) ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( new ItemId( $itemId ) ), $response->getLastModified() );
		$this->assertTrue( $response->wasReplaced() );
	}

	private function newUseCase(): SetSitelink {
		return new SetSitelink( new SitelinkDeserializer(), $this->itemRetriever, $this->itemUpdater );
	}

}
