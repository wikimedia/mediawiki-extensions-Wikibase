<?php
namespace Wikibase\Test;
use Wikibase\NamespaceChecker;

/**
 * Tests for the NamespaceChecker class.
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
 * @author Daniel Kinzler
 */
class NamespaceCheckerTest extends \MediaWikiTestCase {

	public static function constructorProvider() {
		return array(
			array( array(), array( NS_MAIN ) ),
			array( array( NS_USER_TALK ), array() )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( array $excluded, array $enabled ) {
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled );
		$this->assertEquals( $enabled, $namespaceChecker->getEnabledNamespaces() );
		$this->assertEquals( $excluded, $namespaceChecker->getExcludedNamespaces() );
	}

	public static function enabledProvider() {
		// Edge cases:
		// * empty "exclude" matches nothing
		// * empty "include" matches everything
		// * if neither include nor exclude are matched, the namespace is
		//   accepted if and only if the include array is empty.
		// * if the ns is in both, include and exclude, then it is excluded.

		return array(
			array( NS_USER_TALK, array(), array(), true ), // #0
			array( NS_USER_TALK, array(), array( NS_MAIN ), false ), // #1
			array( NS_USER_TALK, array( NS_USER_TALK ), array(), false ), // #2
			array( NS_USER_TALK, array( NS_CATEGORY ), array( NS_USER_TALK ), true ), // #3
			array( NS_CATEGORY, array( NS_USER_TALK ), array( NS_MAIN ), false ), // #4
			array( NS_CATEGORY, array( NS_USER_TALK ), array(), true ), // #5
			array( NS_USER_TALK, array( NS_USER_TALK ), array( NS_USER_TALK ), false ) // #6
		);
	}

	/**
	 * @dataProvider enabledProvider
	 */
	public function testIsWikibaseEnabled( $ns, $excluded, $enabled, $expected ) {
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled );
		$result = $namespaceChecker->isWikibaseEnabled( $ns );
		$this->assertEquals( $expected, $result );
	}


	public static function wikibaseNamespacesProvider() {
		// Edge cases:
		// * empty "exclude" matches nothing
		// * empty "include" matches everything
		// * if neither include nor exclude are matched, the namespace is
		//   accepted if and only if the include array is empty.
		// * if the ns is in both, include and exclude, then it is excluded.

		$all = \MWNamespace::getValidNamespaces();

		return array(
			array( array(), array(), $all ), // #0
			array( array(), array( NS_MAIN ), array( NS_MAIN ) ), // #1
			array( array( NS_USER_TALK ), array(), array_diff( $all, array( NS_USER_TALK ) ) ), // #2
			array( array( NS_CATEGORY ), array( NS_USER_TALK ), array( NS_USER_TALK ) ), // #3
			array( array( NS_USER_TALK ), array( NS_USER_TALK ), array() ) // #4
		);
	}

	/**
	 * @dataProvider wikibaseNamespacesProvider
	 */
	public function testGetWikibaseNamespaces( $excluded, $enabled, $expected ) {
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled );
		$result = $namespaceChecker->getWikibaseNamespaces();
		$this->assertArrayEquals( $expected, $result );
	}
}
