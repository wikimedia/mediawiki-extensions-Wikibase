<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\ConvertArrayObjectsToArray;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ConvertArrayObjectsToArray
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ConvertArrayObjectToArrayTest extends TestCase {

	/**
	 * @dataProvider provideSerializationAndExpectedOutput
	 */
	public function testExecute( iterable $serialization, array $expectedOutput ): void {
		$this->assertSame( $expectedOutput, ( new ConvertArrayObjectsToArray() )->execute( $serialization ) );
	}

	public function provideSerializationAndExpectedOutput(): Generator {
		yield 'empty ArrayObject' => [ new ArrayObject(), [] ];
		yield 'empty array' => [ [], [] ];
		yield 'sequential ArrayObject' => [ new ArrayObject( [ 'a', 'b', 'c' ] ), [ 'a', 'b', 'c' ] ];
		yield 'sequential array' => [ [ 'a', 'b', 'c' ], [ 'a', 'b', 'c' ] ];
		yield 'associative ArrayObject' => [
			new ArrayObject( [ 'key a' => 'value a', 'key b' => 'value b', 'key c' => 'value c' ] ),
			[ 'key a' => 'value a', 'key b' => 'value b', 'key c' => 'value c' ],
		];
		yield 'associative array' => [
			[ 'key a' => 'value a', 'key b' => 'value b', 'key c' => 'value c' ],
			[ 'key a' => 'value a', 'key b' => 'value b', 'key c' => 'value c' ],
		];
		yield 'nested ArrayObjects' => [
			new ArrayObject( [
				'key a' => 'value a',
				'key b' => new ArrayObject( [ 'key b1' => 'value b1', 'key b2' => 'value b2' ] ),
				'key c' => new ArrayObject( [ 'value c1', 'value c2' ] ),
				'key d' => new ArrayObject( [
					'key d1' => new ArrayObject( [ 'key d1a' => 'value d1a', 'key d1b' => 'value d1b' ] ),
					'key d2' => new ArrayObject( [ 'value d2a', 'value d2b' ] ),
				] ),
				'key e' => new ArrayObject( [
					new ArrayObject( [ 'key e1a' => 'value e1a', 'key e1b' => 'value e1b' ] ),
					new ArrayObject( [ 'value e2a', 'value e2b' ] ),
				] ),
			] ),
			[
				'key a' => 'value a',
				'key b' => [ 'key b1' => 'value b1', 'key b2' => 'value b2' ],
				'key c' => [ 'value c1', 'value c2' ],
				'key d' => [
					'key d1' => [ 'key d1a' => 'value d1a', 'key d1b' => 'value d1b' ],
					'key d2' => [ 'value d2a', 'value d2b' ],
				],
				'key e' => [
					[ 'key e1a' => 'value e1a', 'key e1b' => 'value e1b' ],
					[ 'value e2a', 'value e2b' ],
				],
			],
		];
	}

}
