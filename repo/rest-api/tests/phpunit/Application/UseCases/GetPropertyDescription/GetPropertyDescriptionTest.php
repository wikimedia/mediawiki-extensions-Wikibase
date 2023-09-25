<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescriptionResponse;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionTest extends TestCase {

	private PropertyDescriptionRetriever $descriptionRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->descriptionRetriever = $this->createStub( PropertyDescriptionRetriever::class );
	}

	public function testSuccess(): void {
		$languageCode = 'en';
		$description = new Description( $languageCode, 'English test property' );

		$propertyId = new NumericPropertyId( 'P10' );

		$this->descriptionRetriever = $this->createMock( PropertyDescriptionRetriever::class );
		$this->descriptionRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( $propertyId, $languageCode )
			->willReturn( $description );

		$response = $this->newUseCase()
			->execute( new GetPropertyDescriptionRequest( "$propertyId", $languageCode ) );
		$this->assertEquals( new GetPropertyDescriptionResponse( $description ), $response );
	}

	private function newUseCase(): GetPropertyDescription {
		return new GetPropertyDescription( $this->descriptionRetriever );
	}
}
