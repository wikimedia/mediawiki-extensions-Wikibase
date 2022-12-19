<?php

namespace Wikibase\Repo\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\ArrayValueCollector;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 * @covers \Wikibase\Repo\ArrayValueCollector
 */
class ArrayValueCollectorTest extends TestCase {

	public function provideCollectValues() {
		return [
			'simple array, nothing ignored' => [
				[
					'key1' => 'val1',
					'key2' => 'val2',
				],
				[],
				[
					'val1',
					'val2',
				],
			],
			'simple array, 1 key ignored' => [
				[
					'key1' => 'val1',
					'key2' => 'val2',
					'key3' => 'val3',
				],
				[ 'key2' ],
				[
					'val1',
					'val3',
				],
			],
			'multi dimension array, nothing ignored' => [
				[
					'key1' => 'val1',
					'key2' => [
						'val2',
						'key3' => 'val3',
						'key4' => [ 'val4' ],
					],
				],
				[],
				[
					'val1',
					'val2',
					'val3',
					'val4',
				],
			],
			'multi dimension array, 2 ignored ignored' => [
				[
					'key1' => 'val1',
					'key2' => [
						'val2',
						'key3' => 'val3',
						'key4' => [ 'val4' ],
					],
				],
				[ 'key1', 'key4' ],
				[
					'val2',
					'val3',
				],
			],
		];
	}

	/**
	 * @dataProvider provideCollectValues
	 */
	public function testCollectValues( $data, $ignore, $expected ) {
		$result = ArrayValueCollector::collectValues( $data, $ignore );
		$this->assertEquals( $expected, $result );
	}

}
