<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemLabelWithFallback;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallback;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallbackRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallbackResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallback
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelWithFallbackTest extends TestCase {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemLabelRetriever $labelRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->labelRetriever = $this->createStub( ItemLabelRetriever::class );
	}

	public function testSuccess(): void {
		$label = new Label( 'en', 'earth' );

		$itemId = new ItemId( 'Q2' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->labelRetriever = $this->createMock( ItemLabelRetriever::class );
		$this->labelRetriever->expects( $this->once() )
			->method( 'getLabel' )
			->with( $itemId, 'en' )
			->willReturn( $label );

		$request = new GetItemLabelWithFallbackRequest( 'Q2', 'en' );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetItemLabelWithFallbackResponse( $label, $lastModified, $revisionId ), $response );
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q10' );

		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetItemLabelWithFallbackRequest( $itemId->getSerialization(), 'en' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenLabelInRequestedLanguageDoesNotExist_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q11' );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 2, '20201111070707' ] );

		$this->labelRetriever = $this->createStub( ItemLabelRetriever::class );

		try {
			$this->newUseCase()->execute(
				new GetItemLabelWithFallbackRequest( $itemId->getSerialization(), 'en' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::RESOURCE_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( 'The requested resource does not exist', $e->getErrorMessage() );
			$this->assertSame( [ 'resource_type' => 'label' ], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidItemId_throws(): void {
		try {
			$this->newUseCase()->execute( new GetItemLabelWithFallbackRequest( 'X321', 'en' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'item_id'", $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'item_id' ], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidLanguageCode_throwsUseCaseException(): void {
		try {
			$this->newUseCase()->execute( new GetItemLabelWithFallbackRequest( 'Q123', '1e' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $error->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'language_code'", $error->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'language_code' ], $error->getErrorContext() );
		}
	}

	private function newUseCase(): GetItemLabelWithFallback {
		return new GetItemLabelWithFallback(
			$this->getRevisionMetadata,
			$this->labelRetriever,
			new TestValidatingRequestDeserializer()
		);
	}

}
