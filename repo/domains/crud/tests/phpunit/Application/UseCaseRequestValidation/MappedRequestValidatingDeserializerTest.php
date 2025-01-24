<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\MappedRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\MappedRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MappedRequestValidatingDeserializerTest extends TestCase {

	public function testValidateAndDeserialize(): void {
		$expectedRequest = $this->createStub( UseCaseRequest::class );
		$expectedDeserializedValue = 'some-value';
		$mapFunction = function ( $request ) use ( $expectedRequest, $expectedDeserializedValue ) {
			$this->assertSame( $expectedRequest, $request );
			return $expectedDeserializedValue;
		};

		$this->assertSame(
			$expectedDeserializedValue,
			( new MappedRequestValidatingDeserializer( $mapFunction ) )
				->validateAndDeserialize( $expectedRequest )
		);
	}

}
