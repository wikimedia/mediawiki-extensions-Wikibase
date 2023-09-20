<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabel
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelTest extends TestCase {

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

		$request = new GetItemLabelRequest( 'Q2', 'en' );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetItemLabelResponse( $label, $lastModified, $revisionId ), $response );
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q10' );

		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetItemLabelRequest( $itemId->getSerialization(), 'en' )
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
				new GetItemLabelRequest( $itemId->getSerialization(), 'en' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::LABEL_NOT_DEFINED, $e->getErrorCode() );
			$this->assertSame( 'Item with the ID Q11 does not have a label in the language: en', $e->getErrorMessage() );
			$this->assertSame( [], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidItemId_throws(): void {
		try {
			$this->newUseCase()->execute( new GetItemLabelRequest( 'X321', 'en' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X321', $e->getErrorMessage() );
			$this->assertSame( [], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidLanguageCode_throwsUseCaseException(): void {
		try {
			$this->newUseCase()->execute( new GetItemLabelRequest( 'Q123', '1e' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $error->getErrorCode() );
			$this->assertSame( 'Not a valid language code: 1e', $error->getErrorMessage() );
			$this->assertSame( [], $error->getErrorContext() );
		}
	}

	private function newUseCase(): GetItemLabel {
		return new GetItemLabel(
			$this->getRevisionMetadata,
			$this->labelRetriever,
			new TestValidatingRequestDeserializer()
		);
	}

}
