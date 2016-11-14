<?php

namespace Wikibase\Client\Tests;

use Wikibase\InterwikiSorter;

/**
 * @covers Wikibase\InterwikiSorter
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InterwikiSorterTest extends \PHPUnit_Framework_TestCase {

	public function sortOrdersProvider() {
		return [
			'alphabetic' => [ 'ar', 'de', 'en', 'fr', 'ks', 'rn', 'ky', 'hu', 'ja', 'pt' ],
			'alphabetic_revised' => [ 'ar', 'de', 'en', 'fr', 'ks', 'ky', 'rn', 'hu', 'ja', 'pt' ],
			'alphabetic_sr' => [ 'ar', 'de', 'en', 'fr', 'ky', 'rn', 'ks', 'ja', 'hu', 'pt' ],
			'mycustomorder' => [ 'de', 'ja', 'pt', 'hu', 'en' ],
		];
	}

	public function constructorProvider() {
		$sortOrders = $this->sortOrdersProvider();
		return [
			[ 'code', $sortOrders, [] ],
			[ 'code', $sortOrders, [ 'en' ] ]
		];
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $sort, $sortOrders, $sortPrepend ) {
		$interwikiSorter = new InterwikiSorter( $sort, $sortOrders, $sortPrepend );
		$this->assertInstanceOf( InterwikiSorter::class, $interwikiSorter );
	}

	public function sortLinksProvider() {
		$sortOrders = $this->sortOrdersProvider();
		$links = [ 'fr', 'ky', 'hu', 'ar', 'ks', 'ja', 'de', 'en', 'pt', 'rn' ];

		return [
			[
				$links, 'code', $sortOrders, [],
				[ 'ar', 'de', 'en', 'fr', 'hu', 'ja', 'ks', 'ky', 'pt', 'rn' ]
			],
			[
				$links, 'code', $sortOrders, [ 'en' ],
				[ 'en', 'ar', 'de', 'fr', 'hu', 'ja', 'ks', 'ky', 'pt', 'rn' ]
			],
			[
				$links, 'alphabetic', $sortOrders, [],
				$sortOrders['alphabetic']
			],
			[
				$links, 'alphabetic', $sortOrders, [ 'en', 'ja' ],
				[ 'en', 'ja', 'ar', 'de','fr', 'ks', 'rn', 'ky', 'hu', 'pt' ]
			],
			[
				$links, 'alphabetic_revised', $sortOrders, [],
				$sortOrders['alphabetic_revised']
			],
			[
				$links, 'alphabetic_revised', $sortOrders, [ 'hu' ],
				[ 'hu', 'ar', 'de', 'en', 'fr', 'ks', 'ky', 'rn', 'ja', 'pt' ]
			],
			[
				[ 'ja', 'de', 'pt', 'en', 'hu' ], 'mycustomorder', $sortOrders, [],
				$sortOrders['mycustomorder']
			],
			[
				[ 'x2', 'x1', 'x3' ],
				'alphabetic',
				[ 'alphabetic' => [] ],
				[],
				[ 'x1', 'x2', 'x3' ]
			],
			[
				[ 'x2', 'x1', 'en', 'de', 'a2', 'a1' ],
				'alphabetic',
				$sortOrders,
				[],
				[ 'de', 'en', 'a1', 'a2', 'x1', 'x2' ]
			],
			[
				[ 'f', 'd', 'b', 'a', 'c', 'e' ],
				'alphabetic',
				[ 'alphabetic' => [ 'c', 'a' ] ],
				[ 'e' ],
				[ 'e', 'c', 'a', 'b', 'd', 'f' ]
			],
			'Strict code order' => [
				[ 'f', 'd', 'b', 'a', 'c', 'e' ],
				'code',
				[ 'alphabetic' => [ 'c', 'a' ] ], // this should be ignored
				[ 'e' ], // prepend
				[ 'e', 'a', 'b', 'c', 'd', 'f' ]
			],
			'Code w/o alphabetic' => [
				[ 'c', 'b', 'a' ],
				'code',
				[],
				[],
				[ 'a', 'b', 'c' ]
			],
			[
				[ 'a', 'b', 'k', 'x' ],
				'alphabetic',
				[ 'alphabetic' => [ 'x', 'k', 'a' ] ],
				[],
				[ 'x', 'k', 'a', 'b' ]
			],
			'Fall back to code order' => [
				[ 'b', 'a' ],
				'invalid',
				[],
				[],
				[ 'a', 'b' ]
			]
		];
	}

	/**
	 * @dataProvider sortLinksProvider
	 */
	public function testSortLinks( array $links, $sort, array $sortOrders, $sortPrepend, $expected ) {
		$interwikiSorter = new InterwikiSorter( $sort, $sortOrders, $sortPrepend );
		$sortedLinks = $interwikiSorter->sortLinks( $links );
		$this->assertEquals( $expected, $sortedLinks );
	}

}
