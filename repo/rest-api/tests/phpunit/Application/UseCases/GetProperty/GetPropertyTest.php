<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetProperty;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\RestApi\Domain\Services\PropertyPartsRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation\TestValidatingRequestFieldDeserializerFactory;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetProperty
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyTest extends TestCase {

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$expectedPropertyParts = $this->createStub( PropertyParts::class );

		$lastModifiedTimestamp = '20201111070707';
		$revisionId = 42;

		$getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$getRevisionMetadata->method( 'execute' )
			->willReturn( [ $revisionId, $lastModifiedTimestamp ] );

		$propertyPartsRetriever = $this->createMock( PropertyPartsRetriever::class );
		$propertyPartsRetriever->expects( $this->once() )
			->method( 'getPropertyParts' )
			->with( $propertyId )
			->willReturn( $expectedPropertyParts );

		$response = ( new GetProperty(
			$getRevisionMetadata,
			$propertyPartsRetriever,
			$this->newValidator()
		) )->execute(
			new GetPropertyRequest( "$propertyId" )
		);

		$this->assertSame( $expectedPropertyParts, $response->getPropertyParts() );
		$this->assertSame( $lastModifiedTimestamp, $response->getLastModified() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
	}

	public function testInvalidPropertyId(): void {
		$propertyId = 'X123';
		try {
			( new GetProperty(
				$this->createStub( GetLatestPropertyRevisionMetadata::class ),
				$this->createStub( PropertyPartsRetriever::class ),
				$this->newValidator()
			) )->execute( new GetPropertyRequest( $propertyId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
		}
	}

	private function newValidator(): GetPropertyValidator {
		return new ValidatingRequestDeserializer( TestValidatingRequestFieldDeserializerFactory::newFactory() );
	}
}
