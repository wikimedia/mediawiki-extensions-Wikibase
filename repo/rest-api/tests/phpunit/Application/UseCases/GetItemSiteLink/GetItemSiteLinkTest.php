<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemSiteLink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink\GetItemSiteLink;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink\GetItemSiteLinkRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;
use Wikibase\Repo\RestApi\Domain\Services\SiteLinkRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink\GetItemSiteLink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinkTest extends TestCase {

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$site = 'enwiki';

		$expectedRevisionId = 321;
		$expectedRevisionTimestamp = '20241111070707';
		$expectedSiteLink = new SiteLink(
			$site,
			'Dog',
			[],
			'https://en.wikipedia.org/wiki/Dog'
		);

		$siteLinkRetriever = $this->createMock( SiteLinkRetriever::class );
		$siteLinkRetriever->expects( $this->once() )
			->method( 'getSiteLink' )
			->with( $itemId, $site )
			->willReturn( $expectedSiteLink );

		$getLatestRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$getLatestRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willReturn( [ $expectedRevisionId, $expectedRevisionTimestamp ] );

		$request = new GetItemSiteLinkRequest( "$itemId", $site );
		$useCase = new GetItemSiteLink( $getLatestRevisionMetadata, $siteLinkRetriever );

		$response = $useCase->execute( $request );

		$this->assertEquals( $expectedSiteLink, $response->getSiteLink() );
		$this->assertSame( $expectedRevisionId, $response->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $response->getLastModified() );
	}

}
