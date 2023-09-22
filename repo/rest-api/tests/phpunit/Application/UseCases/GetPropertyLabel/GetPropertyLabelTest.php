<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabelResponse;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabel
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelTest extends TestCase {

	private PropertyLabelRetriever $labelRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->labelRetriever = $this->createStub( PropertyLabelRetriever::class );
	}

	public function testSuccess(): void {
		$label = new Label( 'en', 'instance of' );
		$propertyId = new NumericPropertyId( 'P31' );

		$this->labelRetriever = $this->createMock( PropertyLabelRetriever::class );
		$this->labelRetriever->expects( $this->once() )
			->method( 'getLabel' )
			->with( $propertyId, 'en' )
			->willReturn( $label );

		$response = $this->newUseCase()->execute(
			new GetPropertyLabelRequest( "$propertyId", 'en' )
		);

		$this->assertEquals( new GetPropertyLabelResponse( $label ), $response );
	}

	private function newUseCase(): GetPropertyLabel {
		return new GetPropertyLabel( $this->labelRetriever );
	}

}
