<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use Generator;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\RequestData;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\TypeValidatingJsonBodyValidator;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\TypeValidatingJsonBodyValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TypeValidatingJsonBodyValidatorTest extends TestCase {

	/**
	 * @dataProvider validPayloadProvider
	 */
	public function testValidatesAndReturnsParsedBody( array $settings, string $payload, array $parsedBody ): void {
		$this->assertEquals(
			$parsedBody,
			( new TypeValidatingJsonBodyValidator( $settings ) )->validateBody(
				new RequestData( [ 'bodyContents' => $payload ] )
			)
		);
	}

	public function validPayloadProvider(): Generator {
		$payload = [
			'someString' => 'potato',
			'someInt' => 123,
			'someObject' => [ 'omg' => 'bbq' ],
			'someArray' => [ 1, 2, 3 ],
		];

		yield 'nothing to check' => [
			[],
			json_encode( $payload ),
			$payload,
		];

		yield 'all fields typed' => [
			[
				'someString' => [ ParamValidator::PARAM_TYPE => 'string' ],
				'someInt' => [ ParamValidator::PARAM_TYPE => 'integer' ],
				'someObject' => [ ParamValidator::PARAM_TYPE => 'object' ],
				'someArray' => [ ParamValidator::PARAM_TYPE => 'array' ],
			],
			json_encode( $payload ),
			$payload,
		];

		yield 'optional fields may be null' => [
			[
				'optional1' => [ ParamValidator::PARAM_TYPE => 'string' ],
				'optional2' => [ ParamValidator::PARAM_TYPE => 'array' ],
			],
			json_encode( [ 'optional1' => null ] ),
			[ 'optional1' => null, 'optional2' => null ],
		];
	}

	/**
	 * @dataProvider payloadWithTypeErrorProvider
	 */
	public function testGivenPayloadWithWrongType_throwsHttpException(
		array $settings,
		array $parsedBody,
		HttpException $expectedException
	): void {
		try {
			( new TypeValidatingJsonBodyValidator( $settings ) )->validateBody(
				new RequestData( [ 'bodyContents' => json_encode( $parsedBody ) ] )
			);
			$this->fail( 'Expected exception was not thrown' );
		} catch ( HttpException $exception ) {
			$this->assertEquals( $expectedException, $exception );
		}
	}

	public function payloadWithTypeErrorProvider(): Generator {
		yield 'not a string' => [
			[ 'someString' => [ ParamValidator::PARAM_TYPE => 'string' ] ],
			[ 'someString' => 123 ],
			$this->newHttpException( 'string', 'someString' ),
		];
		yield 'not an array' => [
			[ 'someArray' => [ ParamValidator::PARAM_TYPE => 'array' ] ],
			[ 'someArray' => '1, 2, 3' ],
			$this->newHttpException( 'array', 'someArray' ),
		];
		yield 'not an object' => [
			[ 'someObject' => [ ParamValidator::PARAM_TYPE => 'object' ] ],
			[ 'someObject' => 'not an object' ],
			$this->newHttpException( 'object', 'someObject' ),
		];
	}

	private function newHttpException( string $expectedType, string $fieldName ): HttpException {
		return new HttpException(
			TypeValidatingJsonBodyValidator::TYPE_MISMATCH_MESSAGE,
			400,
			[
				'fieldName' => $fieldName,
				'expectedType' => $expectedType,
				'code' => TypeValidatingJsonBodyValidator::TYPE_MISMATCH_CODE,
			]
		);
	}

}
