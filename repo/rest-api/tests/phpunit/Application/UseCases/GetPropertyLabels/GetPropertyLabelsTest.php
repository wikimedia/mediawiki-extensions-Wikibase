<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabelsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelsRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation\TestValidatingRequestFieldDeserializerFactory;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelsTest extends TestCase {

	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyLabelsRetriever $labelsRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->labelsRetriever = $this->createStub( PropertyLabelsRetriever::class );
	}

	public function testSuccess(): void {
		$labels = new Labels(
			new Label( 'en', 'English property' ),
			new Label( 'de', 'German property' ),
		);

		$propertyId = new NumericPropertyId( 'P10' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->labelsRetriever = $this->createMock( PropertyLabelsRetriever::class );
		$this->labelsRetriever->expects( $this->once() )
			->method( 'getLabels' )
			->with( $propertyId )
			->willReturn( $labels );

		$request = new GetPropertyLabelsRequest( 'P10' );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetPropertyLabelsResponse( $labels, $lastModified, $revisionId ), $response );
	}

	public function testGivenInvalidPropertyId_throws(): void {
		try {
			$this->newUseCase()->execute(
				new GetPropertyLabelsRequest( 'X321' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid property ID: X321', $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PROPERTY_ID => 'X321' ], $e->getErrorContext() );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$propertyId = new NumericPropertyId( 'P10' );

		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetPropertyLabelsRequest( $propertyId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): GetPropertyLabels {
		return new GetPropertyLabels(
			$this->getRevisionMetadata,
			$this->labelsRetriever,
			new ValidatingRequestDeserializer( TestValidatingRequestFieldDeserializerFactory::newFactory() )
		);
	}

}
