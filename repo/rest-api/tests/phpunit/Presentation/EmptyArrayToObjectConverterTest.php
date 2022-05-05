<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation;

use Generator;
use PHPUnit\Framework\TestCase;
use stdClass;
use Wikibase\Repo\RestApi\Presentation\EmptyArrayToObjectConverter;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\EmptyArrayToObjectConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EmptyArrayToObjectConverterTest extends TestCase {

	/**
	 * @dataProvider dataProvider
	 *
	 * @param stdClass|array $expectedResult
	 */
	public function testConvert( array $paths, array $input, $expectedResult ): void {
		$converter = new EmptyArrayToObjectConverter( $paths );

		$this->assertEquals( $expectedResult, $converter->convert( $input ) );
	}

	public function dataProvider(): Generator {
		yield 'empty top-level, path matches' => [
			[ '/' ],
			[],
			new stdClass(),
		];

		yield 'empty top-level, path does not match' => [
			[],
			[],
			[],
		];

		yield 'top-level field, path matches' => [
			[ '/labels' ],
			[ 'labels' => [] ],
			[ 'labels' => new stdClass() ],
		];

		yield 'top-level field, path does not match' => [
			[],
			[ 'labels' => [] ],
			[ 'labels' => [] ],
		];

		yield 'multiple fields' => [
			[ '/labels', '/descriptions' ],
			[ 'labels' => [], 'descriptions' => [] ],
			[ 'labels' => new stdClass(), 'descriptions' => new stdClass() ],
		];

		yield 'nested field' => [
			[ '/foo/bar' ],
			[ 'foo' => [ 'bar' => [] ] ],
			[ 'foo' => [ 'bar' => new stdClass() ] ],
		];

		yield 'wildcard' => [
			[ '/statements/*/*/qualifiers' ],
			[ 'statements' => [ 'P123' => [
				[ 'qualifiers' => [] ],
				[ 'qualifiers' => [] ],
			] ] ],
			[ 'statements' => [ 'P123' => [
				[ 'qualifiers' => new stdClass() ],
				[ 'qualifiers' => new stdClass() ],
			] ] ]
		];

		yield 'wildcard root' => [
			[ '/*/*/qualifiers' ],
			[ 'P123' => [
				[ 'qualifiers' => [] ],
				[ 'qualifiers' => [] ],
			] ],
			[ 'P123' => [
				[ 'qualifiers' => new stdClass() ],
				[ 'qualifiers' => new stdClass() ],
			] ]
		];
	}

}
