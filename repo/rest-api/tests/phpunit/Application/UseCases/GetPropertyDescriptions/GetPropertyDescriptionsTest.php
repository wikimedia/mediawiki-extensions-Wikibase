<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyDescriptions;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsResponse;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionsRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyDescriptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionsTest extends TestCase {

	private PropertyDescriptionsRetriever $descriptionsRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->descriptionsRetriever = $this->createStub( PropertyDescriptionsRetriever::class );
	}

	public function testSuccess(): void {
		$descriptions = new Descriptions(
			new Description( 'en', 'English test property' ),
			new Description( 'de', 'deutsche Test-Eigenschaft' ),
		);

		$propertyId = new NumericPropertyId( 'P10' );

		$this->descriptionsRetriever = $this->createMock( PropertyDescriptionsRetriever::class );
		$this->descriptionsRetriever->expects( $this->once() )
			->method( 'getDescriptions' )
			->with( $propertyId )
			->willReturn( $descriptions );

		$response = ( new GetPropertyDescriptions( $this->descriptionsRetriever ) )
			->execute( new GetPropertyDescriptionsRequest( 'P10' ) );
		$this->assertEquals( new GetPropertyDescriptionsResponse( $descriptions ), $response );
	}
}
