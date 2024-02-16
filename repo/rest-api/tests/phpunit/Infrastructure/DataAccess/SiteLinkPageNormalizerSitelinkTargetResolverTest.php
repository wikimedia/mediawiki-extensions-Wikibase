<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use MediaWiki\Site\Site;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\SiteLinkPageNormalizerSitelinkTargetResolver;
use Wikibase\Repo\SiteLinkPageNormalizer;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\SiteLinkPageNormalizerSitelinkTargetResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkPageNormalizerSitelinkTargetResolverTest extends TestCase {

	public function testGivenExistingTitle_returnsResolvedTitle(): void {
		$siteId = 'enwiki';
		$title = 'Bonny Parker';
		$badge = new ItemId( 'Q123' );
		$resolvedTitle = 'Bonny & Clyde';

		$site = $this->createStub( Site::class );
		$siteLookup = $this->createMock( \SiteLookup::class );
		$siteLookup->expects( $this->once() )
			->method( 'getSite' )
			->with( $siteId )
			->willReturn( $site );

		$pageNormalizer = $this->createMock( SiteLinkPageNormalizer::class );
		$pageNormalizer->expects( $this->once() )
			->method( 'normalize' )
			->with( $site, $title, [ "$badge" ] )
			->willReturn( $resolvedTitle );

		$this->assertSame(
			$resolvedTitle,
			( new SiteLinkPageNormalizerSitelinkTargetResolver( $siteLookup, $pageNormalizer ) )
				->resolveTitle( $siteId, $title, [ $badge ] )
		);
	}

	public function testGivenTitleNotFound_throws(): void {
		$siteLookup = $this->createStub( \SiteLookup::class );
		$siteLookup->method( 'getSite' )->willReturn( $this->createStub( Site::class ) );

		$pageNormalizer = $this->createStub( SiteLinkPageNormalizer::class );
		$pageNormalizer->method( 'normalize' )->willReturn( false );

		$this->expectException( SitelinkTargetNotFound::class );

		( new SiteLinkPageNormalizerSitelinkTargetResolver( $siteLookup, $pageNormalizer ) )
			->resolveTitle( 'enwiki', 'A page that does not exist', [] );
	}

}
