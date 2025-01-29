<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\RouteHandlers;

use Generator;
use MediaWiki\Rest\HttpException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\RouteHandlers\AssertValidTopLevelFields;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @covers \Wikibase\Repo\Domains\Crud\RouteHandlers\AssertValidTopLevelFields
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AssertValidTopLevelFieldsTest extends TestCase {
	use AssertValidTopLevelFields;

	/**
	 * @doesNotPerformAssertions
	 *
	 * @dataProvider validBodyProvider
	 */
	public function testValid( ?array $body, array $bodyParamSettings ): void {
		$this->assertValidTopLevelTypes( $body, $bodyParamSettings );
	}

	public static function validBodyProvider(): Generator {
		yield 'null body' => [ null, [] ];

		yield 'valid fields' => [
			[
				'stringy' => 'some string',
				'inty' => 123,
				'arrayy' => [],
				'booly' => true,
			],
			[
				'stringy' => [ ParamValidator::PARAM_TYPE => 'string' ],
				'inty' => [ ParamValidator::PARAM_TYPE => 'integer' ],
				'arrayy' => [ ParamValidator::PARAM_TYPE => 'array' ],
				'booly' => [ ParamValidator::PARAM_TYPE => 'boolean' ],
			],
		];
	}

	/**
	 * @dataProvider invalidBodyProvider
	 */
	public function testInvalid( array $body, array $bodyParamSettings, HttpException $expectedException ): void {
		try {
			$this->assertValidTopLevelTypes( $body, $bodyParamSettings );
			$this->fail( 'expected exception was not thrown' );
		} catch ( HttpException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function invalidBodyProvider(): Generator {
		yield 'int not a string' => [
			[ 'stringy' => 123 ],
			[ 'stringy' => [ ParamValidator::PARAM_TYPE => 'string' ] ],
			new HttpException(
				"Invalid value at '/stringy'",
				400,
				[
					'code' => 'invalid-value',
					'context' => [ 'path' => '/stringy' ],
				]
			),
		];

		yield 'bool not an int' => [
			[ 'inty' => true ],
			[ 'inty' => [ ParamValidator::PARAM_TYPE => 'integer' ] ],
			new HttpException(
				"Invalid value at '/inty'",
				400,
				[
					'code' => 'invalid-value',
					'context' => [ 'path' => '/inty' ],
				]
			),
		];

		yield 'missing top-level field' => [
			[],
			[ 'patch' => [ ParamValidator::PARAM_REQUIRED => true ] ],
			new HttpException(
				'Required field missing',
				400,
				[
					'code' => 'missing-field',
					'context' => [
						'path' => '',
						'field' => 'patch',
					],
				]
			),
		];
	}

}
