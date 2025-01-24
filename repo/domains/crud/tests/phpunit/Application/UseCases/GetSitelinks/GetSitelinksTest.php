<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetSitelinks;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetSitelinks\GetSitelinks;
use Wikibase\Repo\RestApi\Application\UseCases\GetSitelinks\GetSitelinksRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\RestApi\Domain\Services\SitelinksRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetSitelinks\GetSitelinks
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetSitelinksTest extends TestCase {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private SitelinksRetriever $sitelinksRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->sitelinksRetriever = $this->createStub( SitelinksRetriever::class );
	}

	public function testGetSitelinks(): void {
		$itemId = new ItemId( 'Q123' );
		$lastModifiedTimestamp = '20201111070707';
		$revisionId = 42;

		$sitelinksReadModel = new Sitelinks(
			new Sitelink( 'arwiki', 'صفحة للاختبار', [], '' ),
			new Sitelink( 'enwiki', 'test page', [], '' )
		);

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModifiedTimestamp ] );

		$this->sitelinksRetriever = $this->createMock( SitelinksRetriever::class );
		$this->sitelinksRetriever->method( 'getSitelinks' )
			->with( $itemId )
			->willReturn( $sitelinksReadModel );

		$response = $this->newUseCase()->execute( new GetSitelinksRequest( "$itemId" ) );

		$this->assertEquals( $response->getSitelinks(), $sitelinksReadModel );
		$this->assertSame( $response->getLastModified(), $lastModifiedTimestamp );
		$this->assertSame( $response->getRevisionId(), $revisionId );
	}

	public function testGetSitelinks_returnsEmptyObject(): void {
		$itemId = new ItemId( 'Q123' );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 42, '20201111070707' ] );

		$this->sitelinksRetriever = $this->createMock( SitelinksRetriever::class );
		$this->sitelinksRetriever->method( 'getSitelinks' )
			->with( $itemId )
			->willReturn( new Sitelinks() );

		$response = $this->newUseCase()->execute( new GetSitelinksRequest( "$itemId" ) );

		$this->assertEquals( $response->getSitelinks(), new Sitelinks() );
	}

	public function testGivenInvalidItemId_throws(): void {
		try {
			$this->newUseCase()->execute(
				new GetSitelinksRequest( 'X321' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'item_id'", $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'item_id' ], $e->getErrorContext() );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetSitelinksRequest( 'Q999999' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): GetSitelinks {
		return new GetSitelinks(
			new TestValidatingRequestDeserializer(),
			$this->getRevisionMetadata,
			$this->sitelinksRetriever
		);
	}

}
