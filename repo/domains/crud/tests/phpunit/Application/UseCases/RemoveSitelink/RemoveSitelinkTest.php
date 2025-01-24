<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\RemoveSitelink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\RemoveSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\RemoveSitelinkRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\RemoveSitelinkValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseException;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\SitelinkEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\RemoveSitelink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RemoveSitelinkTest extends TestCase {

	private ItemWriteModelRetriever $itemRetriever;

	private ItemUpdater $itemUpdater;

	private AssertItemExists $assertItemExists;

	private RemoveSitelinkValidator $validator;

	private AssertUserIsAuthorized $assertUserIsAuthorized;

	private const VALID_SITE = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];

	protected function setUp(): void {
		parent::setUp();
		$this->itemRetriever = $this->createStub( ItemWriteModelRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$isBot = true;
		$tags = [];
		$comment = __METHOD__;

		$item = NewItem::withId( $itemId )->andSiteLink( self::VALID_SITE, 'dog page' )->build();
		$removedSitelink = $item->getSiteLink( self::VALID_SITE );
		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( $item );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$request = new RemoveSitelinkRequest( "$itemId", self::VALID_SITE, $tags, $isBot, $comment, null );
		$this->newUseCase()->execute( $request );

		$this->assertFalse( $itemRepo->getItemWriteModel( $itemId )->hasLinkToSite( self::VALID_SITE ) );
		$this->assertEquals(
			$itemRepo->getLatestRevisionEditMetadata( $itemId ),
			new EditMetadata( $tags, $isBot, SitelinkEditSummary::newRemoveSummary( $comment, $removedSitelink ) )
		);
	}

	public function testGivenSitelinkNotFound_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = self::VALID_SITE;

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		try {
			$this->newUseCase()
				->execute( new RemoveSitelinkRequest( "$itemId", $siteId, [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::RESOURCE_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( 'The requested resource does not exist', $e->getErrorMessage() );
			$this->assertSame( [ 'resource_type' => 'sitelink' ], $e->getErrorContext() );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertItemExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()
				->execute( new RemoveSitelinkRequest( "$itemId", self::VALID_SITE, [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-item-id' );
		$this->validator = $this->createStub( RemoveSitelinkValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( RemoveSitelinkRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedError = new UseCaseError( UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON, 'You have no permission to edit this item.' );
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'checkEditPermissions' )
			->with( $itemId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute( new RemoveSitelinkRequest( "$itemId", self::VALID_SITE, [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): RemoveSitelink {
		return new RemoveSitelink(
			$this->itemRetriever,
			$this->itemUpdater,
			$this->assertItemExists,
			$this->validator,
			$this->assertUserIsAuthorized
		);
	}

}
