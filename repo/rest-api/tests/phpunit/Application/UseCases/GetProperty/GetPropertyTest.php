<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetProperty;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDataRetriever;

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
		$expectedPropertyData = $this->createStub( PropertyData::class );

		$lastModifiedTimestamp = '20201111070707';
		$revisionId = 42;

		$getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$getRevisionMetadata->method( 'execute' )
			->willReturn( [ $revisionId, $lastModifiedTimestamp ] );

		$propertyDataRetriever = $this->createMock( PropertyDataRetriever::class );
		$propertyDataRetriever->expects( $this->once() )
			->method( 'getPropertyData' )
			->with( $propertyId )
			->willReturn( $expectedPropertyData );

		$response = ( new GetProperty(
			$getRevisionMetadata,
			$propertyDataRetriever,
			new GetPropertyValidator( new PropertyIdValidator() )
		) )->execute(
			new GetPropertyRequest( "$propertyId" )
		);

		$this->assertSame( $expectedPropertyData, $response->getPropertyData() );
		$this->assertSame( $lastModifiedTimestamp, $response->getLastModified() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
	}

	public function testInvalidPropertyId(): void {
		$propertyId = 'X123';
		try {
			( new GetProperty(
				$this->createStub( GetLatestPropertyRevisionMetadata::class ),
				$this->createStub( PropertyDataRetriever::class ),
				new GetPropertyValidator( new PropertyIdValidator() )
			) )->execute( new GetPropertyRequest( $propertyId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
		}
	}
}
