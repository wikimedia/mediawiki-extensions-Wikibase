<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveItemSiteLink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLink;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLinkRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLinkValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SiteLinkEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RemoveItemSiteLinkTest extends TestCase {

	private ItemRetriever $itemRetriever;

	private ItemUpdater $itemUpdater;

	private AssertItemExists $assertItemExists;

	private RemoveItemSiteLinkValidator $validator;

	protected function setUp(): void {
		parent::setUp();
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->validator = new TestValidatingRequestDeserializer();
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = 'enwiki';
		$isBot = true;
		$tags = [];

		$item = NewItem::withId( $itemId )->andSiteLink( $siteId, 'dog page' )->build();
		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( $item );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$request = new RemoveItemSiteLinkRequest( "$itemId", $siteId, $tags, $isBot, null, null );
		$this->newUseCase()->execute( $request );

		$this->assertFalse( $itemRepo->getItem( $itemId )->hasLinkToSite( $siteId ) );
		$this->assertEquals(
			$itemRepo->getLatestRevisionEditMetadata( $itemId ),
			new EditMetadata( $tags, $isBot, new SiteLinkEditSummary() )
		);
	}

	public function testGivenSiteLinkNotFound_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = 'enwiki';

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		try {
			$this->newUseCase()
				->execute( new RemoveItemSiteLinkRequest( "$itemId", $siteId, [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::SITELINK_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "No sitelink found for the ID: $itemId for the site $siteId", $e->getErrorMessage() );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = 'enwiki';

		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertItemExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()
				->execute( new RemoveItemSiteLinkRequest( "$itemId", $siteId, [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-item-id' );
		$this->validator = $this->createStub( RemoveItemSiteLinkValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( RemoveItemSiteLinkRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): RemoveItemSiteLink {
		return new RemoveItemSiteLink( $this->itemRetriever, $this->itemUpdater, $this->assertItemExists, $this->validator );
	}

}
