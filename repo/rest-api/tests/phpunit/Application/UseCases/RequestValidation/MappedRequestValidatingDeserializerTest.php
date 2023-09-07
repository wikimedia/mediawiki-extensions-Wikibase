<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\MappedRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\MappedRequestValidatingDeserializer
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
