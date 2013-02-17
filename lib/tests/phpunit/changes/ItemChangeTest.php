<?php

namespace Wikibase\Test;
use \Wikibase\ItemChange;

/**
 * Tests for the Wikibase\ItemChange class.
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group ItemChange
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ItemChangeTest extends \MediaWikiTestCase {

	public function changesProvider() {
		$testChanges = new TestChanges();
		$siteLinkChanges = $testChanges->getSiteLinkChanges();

		return array(
			array( $siteLinkChanges[0] ),
			array( $siteLinkChanges[1] )
		);
	}

	public function getSiteLinkDiffProvider() {
		$testChanges = new TestChanges();
		$siteLinkChanges = $testChanges->getSiteLinkChanges();

		return array(
			array(
				array(
					'type' => 'diff',
					'isassoc' => true,
					'operations' => array(
						'afwiki' => array(
							'type' => 'add',
							'newvalue' => 'Venezuela',
						),
					),
				),
				$siteLinkChanges[0],
			),
			array(
				array(
					'type' => 'diff',
					'isassoc' => true,
					'operations' => array(
						'cawiki' => array(
							'type' => 'remove',
							'oldvalue' => 'Veneçuela',
						),
					),
				),
				$siteLinkChanges[1]
			)
		);
	}

	/**
	 * @dataProvider changesProvider
	 */
	public function testConstructor( $change ) {
		$this->assertInstanceOf( '\Wikibase\ItemChange', $change );
	}

	/**
	 * @dataProvider getSiteLinkDiffProvider
	 */
	public function testGetSiteLinkDiff( $expected, ItemChange $change ) {
		// @todo $diff as array

		// $diff as ItemDiff
		$diff = $change->getSiteLinkDiff();
		$this->assertInstanceOf( '\Wikibase\ItemDiff', $change->getDiff() );
		$this->assertEquals( $expected, $diff->toArray() );
	}
}
