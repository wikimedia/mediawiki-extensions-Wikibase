<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemSiteLink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink\GetItemSiteLink;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink\GetItemSiteLinkRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;
use Wikibase\Repo\RestApi\Domain\Services\SiteLinkRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink\GetItemSiteLink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinkTest extends TestCase {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private SiteLinkRetriever $siteLinkRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getLatestRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->siteLinkRetriever = $this->createStub( SiteLinkRetriever::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$site = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];

		$expectedRevisionId = 321;
		$expectedRevisionTimestamp = '20241111070707';
		$expectedSiteLink = new SiteLink(
			$site,
			'Dog',
			[],
			'https://en.wikipedia.org/wiki/Dog'
		);

		$this->siteLinkRetriever = $this->createMock( SiteLinkRetriever::class );
		$this->siteLinkRetriever->expects( $this->once() )
			->method( 'getSiteLink' )
			->with( $itemId, $site )
			->willReturn( $expectedSiteLink );

		$this->getLatestRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getLatestRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willReturn( [ $expectedRevisionId, $expectedRevisionTimestamp ] );

		$request = new GetItemSiteLinkRequest( "$itemId", $site );

		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( $expectedSiteLink, $response->getSiteLink() );
		$this->assertSame( $expectedRevisionId, $response->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $response->getLastModified() );
	}

	public function testGivenInvalidRequest_throws(): void {
		try {
			$this->newUseCase()->execute(
				new GetItemSiteLinkRequest( 'X321', TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X321', $e->getErrorMessage() );
			$this->assertSame( [], $e->getErrorContext() );
		}
	}

	private function newUseCase(): GetItemSiteLink {
		return new GetItemSiteLink(
			new TestValidatingRequestDeserializer(),
			$this->getLatestRevisionMetadata,
			$this->siteLinkRetriever
		);
	}

}
