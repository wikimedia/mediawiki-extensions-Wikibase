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
		return array(
			'alphabetic' => array( 'ar', 'de', 'en', 'fr', 'ks', 'rn', 'ky', 'hu', 'ja', 'pt' ),
			'alphabetic_revised' => array( 'ar', 'de', 'en', 'fr', 'ks', 'ky', 'rn', 'hu', 'ja', 'pt' ),
			'alphabetic_sr' => array( 'ar', 'de', 'en', 'fr', 'ky', 'rn', 'ks', 'ja', 'hu', 'pt' ),
			'mycustomorder' => array( 'de', 'ja', 'pt', 'hu', 'en' ),
		);
	}

	public function constructorProvider() {
		$sortOrders = $this->sortOrdersProvider();
		return array(
			array( 'code', $sortOrders, [] ),
			array( 'code', $sortOrders, array( 'en' ) )
		);
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
		$links = array( 'fr', 'ky', 'hu', 'ar', 'ks', 'ja', 'de', 'en', 'pt', 'rn' );

		return array(
			array(
				$links, 'code', $sortOrders, [],
				array( 'ar', 'de', 'en', 'fr', 'hu', 'ja', 'ks', 'ky', 'pt', 'rn' )
			),
			array(
				$links, 'code', $sortOrders, array( 'en' ),
				array( 'en', 'ar', 'de', 'fr', 'hu', 'ja', 'ks', 'ky', 'pt', 'rn' )
			),
			array(
				$links, 'alphabetic', $sortOrders, [],
				$sortOrders['alphabetic']
			),
			array(
				$links, 'alphabetic', $sortOrders, array( 'en', 'ja' ),
				array( 'en', 'ja', 'ar', 'de','fr', 'ks', 'rn', 'ky', 'hu', 'pt' )
			),
			array(
				$links, 'alphabetic_revised', $sortOrders, [],
				$sortOrders['alphabetic_revised']
			),
			array(
				$links, 'alphabetic_revised', $sortOrders, array( 'hu' ),
				array( 'hu', 'ar', 'de', 'en', 'fr', 'ks', 'ky', 'rn', 'ja', 'pt' )
			),
			array(
				array( 'ja', 'de', 'pt', 'en', 'hu' ), 'mycustomorder', $sortOrders, [],
				$sortOrders['mycustomorder']
			),
			array(
				array( 'x2', 'x1', 'x3' ),
				'alphabetic',
				array( 'alphabetic' => [] ),
				[],
				array( 'x1', 'x2', 'x3' )
			),
			array(
				array( 'x2', 'x1', 'en', 'de', 'a2', 'a1' ),
				'alphabetic',
				$sortOrders,
				[],
				array( 'de', 'en', 'a1', 'a2', 'x1', 'x2' )
			),
			array(
				array( 'f', 'd', 'b', 'a', 'c', 'e' ),
				'alphabetic',
				array( 'alphabetic' => array( 'c', 'a' ) ),
				array( 'e' ),
				array( 'e', 'c', 'a', 'b', 'd', 'f' )
			),
			'Strict code order' => array(
				array( 'f', 'd', 'b', 'a', 'c', 'e' ),
				'code',
				array( 'alphabetic' => array( 'c', 'a' ) ), // this should be ignored
				array( 'e' ), // prepend
				array( 'e', 'a', 'b', 'c', 'd', 'f' )
			),
			'Code w/o alphabetic' => array(
				array( 'c', 'b', 'a' ),
				'code',
				[],
				[],
				array( 'a', 'b', 'c' )
			),
			array(
				array( 'a', 'b', 'k', 'x' ),
				'alphabetic',
				array( 'alphabetic' => array( 'x', 'k', 'a' ) ),
				[],
				array( 'x', 'k', 'a', 'b' )
			),
			'Fall back to code order' => array(
				array( 'b', 'a' ),
				'invalid',
				[],
				[],
				array( 'a', 'b' )
			)
		);
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
