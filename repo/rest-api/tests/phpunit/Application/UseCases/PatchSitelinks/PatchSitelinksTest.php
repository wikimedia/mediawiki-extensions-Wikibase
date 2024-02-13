<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchSitelinks;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinks;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinksRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinksValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SitelinksEditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\SitelinksRetriever;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinks
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchSitelinksTest extends TestCase {

	private PatchSitelinksValidator $validator;
	private SitelinksRetriever $sitelinksRetriever;
	private SitelinksSerializer $sitelinksSerializer;
	private PatchJson $patcher;
	private ItemRetriever $itemRetriever;
	private SitelinksDeserializer $sitelinksDeserializer;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->sitelinksRetriever = $this->createStub( SitelinksRetriever::class );
		$this->sitelinksSerializer = new SitelinksSerializer( new SitelinkSerializer() );
		$this->patcher = new PatchJson( new JsonDiffJsonPatcher() );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->sitelinksDeserializer = new SitelinksDeserializer( new SitelinkDeserializer() );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$badgeItemId = new ItemId( 'Q321' );

		$enSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$enSitelinkTitle = 'enTitle';

		$deSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1];
		$deSitelinkTitle = 'deTitle';

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'sitelinks replaced by ' . __method__;

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem(
			NewItem::withId( $itemId )->andSiteLink( $enSiteId, $enSitelinkTitle, [ $badgeItemId ] )->build()
		);
		$this->sitelinksRetriever = $itemRepo;
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new PatchSitelinksRequest(
				"$itemId",
				[
					[
						'op' => 'add',
						'path' => "/$deSiteId",
						'value' => [
							'title' => $deSitelinkTitle,
							'badges' => [ "$badgeItemId" ],
						],
					],
				],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			$response->getSitelinks(),
			new Sitelinks(
				new Sitelink(
					$enSiteId,
					$enSitelinkTitle,
					[ $badgeItemId ],
					$itemRepo->urlForSitelink( $enSiteId, $enSitelinkTitle )
				), new Sitelink(
					$deSiteId,
					$deSitelinkTitle,
					[ $badgeItemId ],
					$itemRepo->urlForSitelink( $deSiteId, $deSitelinkTitle )
				)
			)
		);
		$this->assertEquals(
			new EditMetadata( $editTags, $isBot, SitelinksEditSummary::newPatchSummary( $comment ) ),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
	}

	private function newUseCase(): PatchSitelinks {
		return new PatchSitelinks(
			$this->validator,
			$this->sitelinksRetriever,
			$this->sitelinksSerializer,
			$this->patcher,
			$this->itemRetriever,
			$this->sitelinksDeserializer,
			$this->itemUpdater
		);
	}

}
