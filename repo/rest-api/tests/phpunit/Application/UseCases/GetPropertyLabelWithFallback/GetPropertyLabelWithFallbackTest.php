<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyLabelWithFallback;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallback;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallbackRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallbackResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallback
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelWithFallbackTest extends TestCase {

	private PropertyLabelRetriever $labelRetriever;
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->labelRetriever = $this->createStub( PropertyLabelRetriever::class );
	}

	public function testSuccess(): void {
		$label = new Label( 'en', 'instance of' );
		$propertyId = new NumericPropertyId( 'P31' );
		$lastModified = '20230922070707';
		$revisionId = 432;

		$this->labelRetriever = $this->createMock( PropertyLabelRetriever::class );
		$this->labelRetriever->expects( $this->once() )
			->method( 'getLabel' )
			->with( $propertyId, 'en' )
			->willReturn( $label );

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$response = $this->newUseCase()->execute(
			new GetPropertyLabelWithFallbackRequest( "$propertyId", 'en' )
		);

		$this->assertEquals(
			new GetPropertyLabelWithFallbackResponse( $label, $lastModified, $revisionId ),
			$response
		);
	}

	public function testGivenPropertyDoesNotExist_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetPropertyLabelWithFallbackRequest( 'P999999', 'en' ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenLabelDoesNotExist_throws(): void {
		$propertyId = 'P123';
		$languageCode = 'en';

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 123, '20230922070707' ] );

		try {
			$this->newUseCase()->execute( new GetPropertyLabelWithFallbackRequest( $propertyId, $languageCode ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals(
				UseCaseError::newResourceNotFound( 'label' ),
				$e
			);
		}
	}

	public function testGivenInvalidRequest_throws(): void {
		try {
			$this->newUseCase()->execute( new GetPropertyLabelWithFallbackRequest( 'X123', 'en' ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
		}
	}

	private function newUseCase(): GetPropertyLabelWithFallback {
		return new GetPropertyLabelWithFallback(
			new TestValidatingRequestDeserializer(),
			$this->getRevisionMetadata,
			$this->labelRetriever
		);
	}

}
