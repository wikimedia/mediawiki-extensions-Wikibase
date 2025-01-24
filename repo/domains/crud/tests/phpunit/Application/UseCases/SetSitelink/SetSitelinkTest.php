<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\SetSitelink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink as SitelinkWriteModel;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelinkRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelinkValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseException;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\SitelinkEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\SiteLink;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetSitelinkTest extends TestCase {

	private SetSitelinkValidator $validator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();
		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->itemRetriever = $this->createStub( ItemWriteModelRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testAddSitelink(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$title = 'Potato';
		$badge = TestValidatingRequestDeserializer::ALLOWED_BADGES[ 0 ];

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new SetSitelinkRequest(
				"$itemId",
				$siteId,
				[ 'title' => $title, 'badges' => [ $badge ] ],
				[],
				false,
				'',
				null
			)
		);

		$this->assertEquals(
			new SiteLink( $siteId, $title, [ new ItemId( $badge ) ], $itemRepo->urlForSitelink( $siteId, $title )
			),
			$response->getSitelink()
		);
		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				[],
				false,
				SitelinkEditSummary::newAddSummary(
					'',
					new SitelinkWriteModel( $siteId, $title, [ new ItemId( $badge ) ] )
				)
			),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
		$this->assertFalse( $response->wasReplaced() );
	}

	public function testReplaceSitelink(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$title = 'New_Potato';
		$badge = TestValidatingRequestDeserializer::ALLOWED_BADGES[ 1 ];
		$item = NewItem::withId( $itemId )->andSiteLink( $siteId, 'Old_Potato', [] )->build();
		$replacedSitelink = $item->getSiteLink( $siteId );

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( $item );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new SetSitelinkRequest(
				"$itemId",
				$siteId,
				[ 'title' => $title, 'badges' => [ $badge ] ],
				[],
				false,
				'',
				null
			)
		);

		$this->assertEquals(
			new SiteLink( $siteId, $title, [ new ItemId( $badge ) ], $itemRepo->urlForSitelink( $siteId, $title )
			),
			$response->getSitelink()
		);
		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				[],
				false,
				SitelinkEditSummary::newReplaceSummary(
					'',
					new SitelinkWriteModel( $siteId, $title, [ new ItemId( $badge ) ] ),
					$replacedSitelink
				)
			),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
		$this->assertTrue( $response->wasReplaced() );
	}

	public function testGivenInvalidRequest_throws(): void {
		$expectedUseCaseRequest = $this->createStub( SetSitelinkRequest::class );
		$expectedUseCaseError = $this->createStub( UseCaseError::class );

		$this->validator = $this->createMock( SetSitelinkValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->with( $expectedUseCaseRequest )->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute( $expectedUseCaseRequest );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new SetSitelinkRequest(
					'Q123',
					TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0],
					[ 'title' => 'title', 'badges' => [ TestValidatingRequestDeserializer::ALLOWED_BADGES[ 2 ] ] ],
					[],
					false,
					'',
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
			'You have no permission to edit this item.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'checkEditPermissions' )
			->with( $itemId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				new SetSitelinkRequest(
					"$itemId",
					TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1],
					[ 'title' => 'title', 'badges' => [ TestValidatingRequestDeserializer::ALLOWED_BADGES[ 0 ] ] ],
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): SetSitelink {
		return new SetSitelink(
			$this->validator,
			$this->assertItemExists,
			$this->assertUserIsAuthorized,
			$this->itemRetriever,
			$this->itemUpdater
		);
	}

}
