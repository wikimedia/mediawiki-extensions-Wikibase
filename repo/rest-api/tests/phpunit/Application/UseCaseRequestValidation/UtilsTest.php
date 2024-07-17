<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\Utils;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\Utils
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UtilsTest extends TestCase {

	/**
	 * @dataProvider successProvider
	 *
	 * @param mixed $value
	 * @param array $serialization
	 * @param int $index
	 */
	public function testGetIndexOfValueInSerialization_returnsIndex( $value, array $serialization, int $index ): void {
		$this->assertSame( $index, Utils::getIndexOfValueInSerialization( $value, $serialization ) );
	}

	public function successProvider(): Generator {
		yield 'int' => [
			1,
			[ 0, 1, 2, 3 ],
			1,
		];

		yield 'string' => [
			'c',
			[ 'a', 'b', 'c', 'd' ],
			2,
		];

		yield 'array' => [
			[ 'x' => 'x', 'y' => 'y', 'z' => 'z' ],
			[ 'a', 'b', 'c', [ 'x' => 'x', 'y' => 'y', 'z' => 'z' ] ],
			3,
		];

		yield 'object' => [
			new stdClass(),
			[ new stdClass(), 'a', 'b', 'c' ],
			0,
		];
	}

	/**
	 * @dataProvider failureProvider
	 *
	 * @param mixed $value
	 * @param array $serialization
	 */
	public function testGivenValueNotInSerialization_throwsLogicException( $value, array $serialization ): void {
		$this->expectException( LogicException::class );
		Utils::getIndexOfValueInSerialization( $value, $serialization );
	}

	public function failureProvider(): Generator {
		yield 'value not in serialization' => [
			'a',
			[ 'b' => 'b', 'c' => 'c' ],
		];

		yield 'associative array' => [
			'b',
			[ 'a' => 'a', 'b' => 'b', 'c' => 'c' ],
		];

		yield "doesn't do a nested search" => [
			'c',
			[ 'a', 'b', [ 'c' ] ],
		];
	}
}
