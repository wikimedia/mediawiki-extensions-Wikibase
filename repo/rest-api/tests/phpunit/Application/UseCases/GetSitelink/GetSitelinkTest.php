<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetSitelink;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetSitelink\GetSitelink;
use Wikibase\Repo\RestApi\Application\UseCases\GetSitelink\GetSitelinkRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink;
use Wikibase\Repo\RestApi\Domain\Services\SitelinkRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetSitelink\GetSitelink
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetSitelinkTest extends TestCase {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private SitelinkRetriever $sitelinkRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getLatestRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->sitelinkRetriever = $this->createStub( SitelinkRetriever::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$site = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];

		$expectedRevisionId = 321;
		$expectedRevisionTimestamp = '20241111070707';
		$expectedSitelink = new Sitelink(
			$site,
			'Dog',
			[],
			'https://en.wikipedia.org/wiki/Dog'
		);

		$this->sitelinkRetriever = $this->createMock( SitelinkRetriever::class );
		$this->sitelinkRetriever->expects( $this->once() )
			->method( 'getSitelink' )
			->with( $itemId, $site )
			->willReturn( $expectedSitelink );

		$this->getLatestRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getLatestRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willReturn( [ $expectedRevisionId, $expectedRevisionTimestamp ] );

		$request = new GetSitelinkRequest( "$itemId", $site );

		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( $expectedSitelink, $response->getSitelink() );
		$this->assertSame( $expectedRevisionId, $response->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $response->getLastModified() );
	}

	public function testGivenInvalidRequest_throws(): void {
		try {
			$this->newUseCase()->execute(
				new GetSitelinkRequest( 'X321', TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X321', $e->getErrorMessage() );
			$this->assertSame( [], $e->getErrorContext() );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );

		$this->getLatestRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getLatestRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetSitelinkRequest( 'Q999999', TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenRequestedSitelinkDoesNotExist_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q11' );

		$this->getLatestRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getLatestRevisionMetadata->method( 'execute' )->willReturn( [ 2, '20201111070707' ] );

		$this->sitelinkRetriever = $this->createStub( SitelinkRetriever::class );

		try {
			$this->newUseCase()->execute(
				new GetSitelinkRequest( $itemId->getSerialization(), TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::SITELINK_NOT_DEFINED, $e->getErrorCode() );
			$this->assertSame(
				'No sitelink found for the ID: Q11 for the site ' . TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0],
				$e->getErrorMessage()
			);
			$this->assertSame( [], $e->getErrorContext() );
		}
	}

	private function newUseCase(): GetSitelink {
		return new GetSitelink(
			new TestValidatingRequestDeserializer(),
			$this->getLatestRevisionMetadata,
			$this->sitelinkRetriever
		);
	}

}
