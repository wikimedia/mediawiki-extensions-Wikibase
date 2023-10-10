<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequestValidatingDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyLabelEditRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsLabel(): void {
		$request = $this->createStub( PropertyLabelEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P1' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'some-property-label' );

		$this->assertEquals(
			new Term( 'en', 'some-property-label' ),
			( new PropertyLabelEditRequestValidatingDeserializer() )->validateAndDeserialize( $request )
		);
	}

}
