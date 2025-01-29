<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\GetPropertyDescriptions;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseException;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyDescriptionsRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionsTest extends TestCase {

	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyDescriptionsRetriever $descriptionsRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->descriptionsRetriever = $this->createStub( PropertyDescriptionsRetriever::class );
	}

	public function testSuccess(): void {
		$descriptions = new Descriptions(
			new Description( 'en', 'English test property' ),
			new Description( 'de', 'deutsche Test-Eigenschaft' ),
		);

		$propertyId = new NumericPropertyId( 'P10' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->descriptionsRetriever = $this->createMock( PropertyDescriptionsRetriever::class );
		$this->descriptionsRetriever->expects( $this->once() )
			->method( 'getDescriptions' )
			->with( $propertyId )
			->willReturn( $descriptions );

		$response = $this->newUseCase()
			->execute( new GetPropertyDescriptionsRequest( 'P10' ) );
		$this->assertEquals( new GetPropertyDescriptionsResponse( $descriptions, $lastModified, $revisionId ), $response );
	}

	public function testGivenPropertyNotFound_throws(): void {
		$propertyId = new NumericPropertyId( 'P10' );

		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetPropertyDescriptionsRequest( $propertyId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenInvalidPropertyId_throws(): void {
		try {
			$this->newUseCase()->execute(
				new GetPropertyDescriptionsRequest( 'X321' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'property_id'", $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'property_id' ], $e->getErrorContext() );
		}
	}

	private function newUseCase(): GetPropertyDescriptions {
		return new GetPropertyDescriptions(
			$this->getRevisionMetadata,
			$this->descriptionsRetriever,
			new TestValidatingRequestDeserializer()
		);
	}
}
