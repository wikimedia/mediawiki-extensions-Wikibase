<?php
namespace Wikibase\Test;
use Wikibase\InterwikiSorter;

/**
 * Tests for the InterwikiSorter class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InterwikiSorterTest extends \MediaWikiTestCase {

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
			array( 'code', $sortOrders, array() ),
			array( 'code', $sortOrders, array( 'en' ) )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $sort, $sortOrders, $sortPrepend ) {
		$interwikiSorter = new InterwikiSorter( $sort, $sortOrders, $sortPrepend );
		$this->assertInstanceOf( '\Wikibase\InterwikiSorter', $interwikiSorter );
	}

	public function sortLinksProvider() {
		$sortOrders = $this->sortOrdersProvider();
		$links = array( 'fr', 'ky', 'hu', 'ar', 'ks', 'ja', 'de', 'en', 'pt', 'rn' );

		return array(
			array(
				$links, 'code', $sortOrders, array(),
				array( 'ar', 'de', 'en', 'fr', 'hu', 'ja', 'ks', 'ky', 'pt', 'rn' )
			),
			array(
				$links, 'code', $sortOrders, array( 'en' ),
				array( 'en', 'ar', 'de', 'fr', 'hu', 'ja', 'ks', 'ky', 'pt', 'rn' )
			),
			array(
				$links, 'alphabetic', $sortOrders, array(),
				$sortOrders['alphabetic']
			),
			array(
				$links, 'alphabetic', $sortOrders, array( 'en', 'ja' ),
				array( 'en', 'ja', 'ar', 'de','fr', 'ks', 'rn', 'ky', 'hu', 'pt' )
			),
			array(
				$links, 'alphabetic_revised', $sortOrders, array(),
				$sortOrders['alphabetic_revised']
			),
			array(
				$links, 'alphabetic_revised', $sortOrders, array( 'hu' ),
				array( 'hu', 'ar', 'de', 'en', 'fr', 'ks', 'ky', 'rn', 'ja', 'pt' )
			),
			array(
				array( 'ja', 'de', 'pt', 'en', 'hu' ), 'mycustomorder', $sortOrders, array(),
				$sortOrders['mycustomorder']
			),
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
