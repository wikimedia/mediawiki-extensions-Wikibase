<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyDescriptionWithFallback;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallback;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallbackRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallbackResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallback
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionWithFallbackTest extends TestCase {

	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyDescriptionRetriever $descriptionRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->descriptionRetriever = $this->createStub( PropertyDescriptionRetriever::class );
	}

	public function testSuccess(): void {
		$languageCode = 'en';
		$description = new Description( $languageCode, 'English test property' );

		$propertyId = new NumericPropertyId( 'P10' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->descriptionRetriever = $this->createMock( PropertyDescriptionRetriever::class );
		$this->descriptionRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( $propertyId, $languageCode )
			->willReturn( $description );

		$response = $this->newUseCase()
			->execute( new GetPropertyDescriptionWIthFallbackRequest( "$propertyId", $languageCode ) );
		$this->assertEquals( new GetPropertyDescriptionWithFallbackResponse( $description, $lastModified, $revisionId ), $response );
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetPropertyDescriptionWithFallbackRequest( 'P999999', 'en' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenDescriptionDoesNotExist_throws(): void {
		$propertyId = 'P123';
		$languageCode = 'en';

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 123, '20230926070707' ] );

		try {
			$this->newUseCase()->execute( new GetPropertyDescriptionWithFallbackRequest( $propertyId, $languageCode ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals(
				UseCaseError::newResourceNotFound( 'description' ),
				$e
			);
		}
	}

	public function testGivenInvalidRequest_throws(): void {
		try {
			$this->newUseCase()->execute( new GetPropertyDescriptionWithFallbackRequest( 'X321', 'en' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
		}
	}

	private function newUseCase(): GetPropertyDescriptionWithFallback {
		return new GetPropertyDescriptionWithFallback(
			new TestValidatingRequestDeserializer(),
			$this->getRevisionMetadata,
			$this->descriptionRetriever
		);
	}
}
