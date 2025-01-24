<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallbackRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallbackResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyLabelWithFallbackRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallback
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelWithFallbackTest extends TestCase {

	private PropertyLabelWithFallbackRetriever $labelRetriever;
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->labelRetriever = $this->createStub( PropertyLabelWithFallbackRetriever::class );
	}

	public function testSuccess(): void {
		$label = new Label( 'en', 'instance of' );
		$propertyId = new NumericPropertyId( 'P31' );
		$lastModified = '20230922070707';
		$revisionId = 432;

		$this->labelRetriever = $this->createMock( PropertyLabelWithFallbackRetriever::class );
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
