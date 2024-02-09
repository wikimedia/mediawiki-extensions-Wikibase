<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetSitelink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink as DataModelSitelink;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelink;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelinkRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelinkValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SitelinkEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetSitelinkTest extends TestCase {

	private SetSitelinkValidator $validator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();
		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
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
					new DataModelSitelink( $siteId, $title, [ new ItemId( $badge ) ] )
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
					new DataModelSitelink( $siteId, $title, [ new ItemId( $badge ) ] ),
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
					'enwiki',
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
			UseCaseError::PERMISSION_DENIED,
			'You have no permission to edit this item.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $itemId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				new SetSitelinkRequest(
					"$itemId",
					'enwiki',
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
