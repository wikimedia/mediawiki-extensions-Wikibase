<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemDescriptionWithFallback;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallback;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallbackRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallbackResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionWithFallbackRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallback
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionWithFallbackTest extends TestCase {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemDescriptionWithFallbackRetriever $descriptionRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 42, '20201111070707' ] );
		$this->descriptionRetriever = $this->createStub( ItemDescriptionWithFallbackRetriever::class );
	}

	public function testSuccess(): void {
		$languageCode = 'en';
		$description = new Description( $languageCode, 'third planet from the Sun in the Solar System' );

		$itemId = new ItemId( 'Q2' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->getRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willReturn( [ $revisionId, $lastModified ] );

		$this->descriptionRetriever = $this->createMock( ItemDescriptionWithFallbackRetriever::class );
		$this->descriptionRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( $itemId, $languageCode )
			->willReturn( $description );

		$request = new GetItemDescriptionWithFallbackRequest( 'Q2', $languageCode );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetItemDescriptionWithFallbackResponse( $description, $lastModified, $revisionId ), $response );
	}

	public function testGivenRequestedItemDoesNotExistOrRedirect_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q10' );
		$expectedException = $this->createStub( UseCaseException::class );
		$this->getRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute(
				new GetItemDescriptionWithFallbackRequest( $itemId->getSerialization(), 'en' )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenDescriptionNotDefined_throws(): void {
		$itemId = new ItemId( 'Q2' );

		$this->descriptionRetriever = $this->createStub( ItemDescriptionWithFallbackRetriever::class );

		try {
			$this->newUseCase()->execute(
				new GetItemDescriptionWithFallbackRequest( $itemId->getSerialization(), 'en' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::RESOURCE_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( 'The requested resource does not exist', $e->getErrorMessage() );
			$this->assertSame( [ 'resource_type' => 'description' ], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidItemId_throws(): void {
		try {
			$this->newUseCase()->execute( new GetItemDescriptionWithFallbackRequest( 'X321', 'en' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'item_id'", $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'item_id' ], $e->getErrorContext() );
		}
	}

	private function newUseCase(): GetItemDescriptionWithFallback {
		return new GetItemDescriptionWithFallback(
			new TestValidatingRequestDeserializer(),
			$this->getRevisionMetadata,
			$this->descriptionRetriever,
		);
	}
}
