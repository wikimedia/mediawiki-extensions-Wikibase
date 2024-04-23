<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchSitelinks;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchedSitelinksValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinks;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinksRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinksValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SitelinksEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\SitelinksRetriever;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinkLookupSitelinkValidator;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchSitelinks
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchSitelinksTest extends TestCase {

	private PatchSitelinksValidator $validator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private SitelinksRetriever $sitelinksRetriever;
	private SitelinksSerializer $sitelinksSerializer;
	private PatchJson $patcher;
	private ItemRetriever $itemRetriever;
	private PatchedSitelinksValidator $patchedSitelinksValidator;
	private ItemUpdater $itemUpdater;

	private const ALLOWED_BADGES = [ 'Q999' ];

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->sitelinksRetriever = $this->createStub( SitelinksRetriever::class );
		$this->sitelinksSerializer = new SitelinksSerializer( new SitelinkSerializer() );
		$this->patcher = new PatchJson( new JsonDiffJsonPatcher() );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->patchedSitelinksValidator = new PatchedSitelinksValidator( new SitelinksValidator(
			new SiteIdValidator( TestValidatingRequestDeserializer::ALLOWED_SITE_IDS ),
			new SiteLinkLookupSitelinkValidator(
				new SitelinkDeserializer(
					'/\?/',
					self::ALLOWED_BADGES,
					new SameTitleSitelinkTargetResolver(),
					new DummyItemRevisionMetaDataRetriever()
				),
				new HashSiteLinkStore()
			)
		) );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$badgeItemId = new ItemId( self::ALLOWED_BADGES[ 0 ] );

		$enSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$enSitelinkTitle = 'enTitle';

		$deSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1];
		$deSitelinkTitle = 'deTitle';

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'sitelinks replaced by ' . __METHOD__;

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
		$this->assertUserIsAuthorized->method( 'checkEditPermissions' )
			->with( $itemId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute( new PatchSitelinksRequest( "$itemId", [], [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new PatchSitelinksRequest( 'Q123', [], [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPatchedSitelinksInvalid_throws(): void {
		$itemId = 'Q123';
		$item = NewItem::withId( $itemId )->build();
		$patchResult = [ 'invalid-site-id' => [] ];

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( $item );
		$this->sitelinksRetriever = $itemRepo;
		$this->itemRetriever = $itemRepo;

		$expectedUseCaseError = $this->createStub( UseCaseError::class );
		$this->patchedSitelinksValidator = $this->createMock( PatchedSitelinksValidator::class );
		$this->patchedSitelinksValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $itemId, [], $patchResult )
			->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute(
				new PatchSitelinksRequest(
					$item->getId()->getSerialization(),
					[ [ 'op' => 'add', 'path' => '/invalid-site-id', 'value' => [] ] ],
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	public function testGivenPatchJsonError_throws(): void {
		$itemId = 'Q123';

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem(
			NewItem::withId( new ItemId( $itemId ) )->andSiteLink(
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0],
				'enTitle'
			)->build()
		);
		$this->sitelinksRetriever = $itemRepo;

		$this->patcher = $this->createStub( PatchJson::class );

		$expectedException = $this->createStub( UseCaseException::class );
		$this->patcher->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new PatchSitelinksRequest( $itemId, [], [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): PatchSitelinks {
		return new PatchSitelinks(
			$this->validator,
			$this->assertItemExists,
			$this->assertUserIsAuthorized,
			$this->sitelinksRetriever,
			$this->sitelinksSerializer,
			$this->patcher,
			$this->itemRetriever,
			$this->patchedSitelinksValidator,
			$this->itemUpdater
		);
	}

}
