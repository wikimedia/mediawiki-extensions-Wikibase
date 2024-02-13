<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchSitelinks;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinks;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinksRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinksValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SitelinksEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
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
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private SitelinksRetriever $sitelinksRetriever;
	private SitelinksSerializer $sitelinksSerializer;
	private PatchJson $patcher;
	private ItemRetriever $itemRetriever;
	private SitelinksDeserializer $sitelinksDeserializer;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
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

		$enSiteId = InMemoryItemRepository::EN_WIKI_SITE_ID;
		$enSitelinkTitle = 'enTitle';

		$deSiteId = InMemoryItemRepository::DE_WIKI_SITE_ID;
		$deSitelinkTitle = 'deTitle';

		$enSitelink = new Sitelink(
			$enSiteId,
			$enSitelinkTitle,
			[ $badgeItemId ],
			InMemoryItemRepository::EN_WIKI_URL_PREFIX . $enSitelinkTitle
		);

		$deSitelink = new Sitelink(
			$deSiteId,
			$deSitelinkTitle,
			[ $badgeItemId ],
			InMemoryItemRepository::DE_WIKI_URL_PREFIX . $deSitelinkTitle
		);

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
		$this->assertEquals( $response->getSitelinks(), new Sitelinks( $enSitelink, $deSitelink ) );
		$this->assertEquals(
			new EditMetadata( $editTags, $isBot, SitelinksEditSummary::newPatchSummary( $comment ) ),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-sitelinks-patch-test' );
		$this->validator = $this->createStub( PatchSitelinksValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( PatchSitelinksRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED,
			'You have no permission to edit this item.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $itemId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute( new PatchSitelinksRequest( "$itemId", [], [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): PatchSitelinks {
		return new PatchSitelinks(
			$this->validator,
			$this->assertUserIsAuthorized,
			$this->sitelinksRetriever,
			$this->sitelinksSerializer,
			$this->patcher,
			$this->itemRetriever,
			$this->sitelinksDeserializer,
			$this->itemUpdater
		);
	}

}
