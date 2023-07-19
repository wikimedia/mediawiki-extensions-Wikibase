<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescriptionResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionRetriever;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionTest extends TestCase {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemDescriptionRetriever $descriptionRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 42, '20201111070707' ] );
		$this->descriptionRetriever = $this->createStub( ItemDescriptionRetriever::class );
	}

	public function testSuccess(): void {
		$languageCode = 'en';
		$description = new Description(
			$languageCode,
			'third planet from the Sun in the Solar System'
		);

		$itemId = new ItemId( 'Q2' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->getRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willReturn( [ $revisionId, $lastModified ] );

		$this->descriptionRetriever = $this->createMock( ItemDescriptionRetriever::class );
		$this->descriptionRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( $itemId, $languageCode )
			->willReturn( $description );

		$request = new GetItemDescriptionRequest( 'Q2', $languageCode );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetItemDescriptionResponse( $description, $lastModified, $revisionId ), $response );
	}

	public function testGivenRequestedItemDoesNotExistOrRedirect_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q10' );
		$expectedException = $this->createStub( UseCaseException::class );
		$this->getRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute(
				new GetItemDescriptionRequest( $itemId->getSerialization(), 'en' )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenDescriptionNotDefined_throws(): void {
		$itemId = new ItemId( 'Q2' );

		$this->descriptionRetriever = $this->createStub( ItemDescriptionRetriever::class );

		try {
			$this->newUseCase()->execute(
				new GetItemDescriptionRequest( $itemId->getSerialization(), 'en' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::DESCRIPTION_NOT_DEFINED, $e->getErrorCode() );
			$this->assertSame( 'Item with the ID Q2 does not have a description in the language: en', $e->getErrorMessage() );
			$this->assertSame( [], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidItemId_throws(): void {
		try {
			$this->newUseCase()->execute( new GetItemDescriptionRequest( 'X321', 'en' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X321', $e->getErrorMessage() );
			$this->assertSame( [], $e->getErrorContext() );
		}
	}

	public function testGivenInvalidLanguageCode_throwsUseCaseException(): void {
		try {
			$this->newUseCase()->execute( new GetItemDescriptionRequest( 'Q123', '1e' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid language code: 1e', $useCaseEx->getErrorMessage() );
			$this->assertSame( [], $useCaseEx->getErrorContext() );
		}
	}

	private function newUseCase(): GetItemDescription {
		return new GetItemDescription(
			$this->getRevisionMetadata,
			$this->descriptionRetriever,
			new GetItemDescriptionValidator(
				new ItemIdValidator(),
				new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() )
			)
		);
	}
}
