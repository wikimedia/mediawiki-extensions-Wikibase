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
 * @group NamespaceCheckerTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class NamespaceCheckerTest extends \MediaWikiTestCase {

	public function constructorProvider() {
		return array(
			array( array(), array(), array(), null ), // #0
			array( array( NS_MAIN ), array(), array( NS_MAIN ), null ), // #1
			array( array(), array( NS_USER_TALK ), array(), null ), // #2
			array( array( NS_MAIN ), array( NS_USER_TALK ), array( NS_MAIN ), null ), // #3
			array( array( NS_MAIN ), array(), array( NS_MAIN ), array( NS_MAIN ) ), // #4
			array( array( NS_MAIN ), array(), array(), array( NS_MAIN ) ), // #5
			array( array( NS_USER, NS_MAIN ), array(), array( NS_MAIN ), array( NS_USER ) ), // #6, order will be reversed
			array( array( NS_MAIN, NS_USER ), array(), array( NS_MAIN ), array( NS_MAIN, NS_USER ) ), // #7
			array( array( NS_MAIN ), array( NS_USER ), array( NS_MAIN ), array( NS_MAIN, NS_USER ) ), // #8
			array( array( NS_MAIN ), array( NS_USER ), array( NS_MAIN ), array( NS_MAIN, NS_TALK, NS_USER, NS_USER_TALK ) ), // #9
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( array $expected, array $excluded, array $enabled, array $default = null ) {
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled, $default );
		$merged = array_filter(
			isset( $default ) ? $default : array(),
			function ( $ns ) {
				return $ns % 2 === 0;
			}
		);
		$merged = array_unique( array_merge( $merged, $enabled ) );
		$this->assertEquals( $merged, $namespaceChecker->getEnabledNamespaces() );
		$this->assertEquals( $excluded, $namespaceChecker->getExcludedNamespaces() );
		$this->assertEquals( $expected, $namespaceChecker->getValidNamespaces() );
	}

	public function enabledProvider() {
		return array(
			array( NS_USER_TALK, array(), array(), false ), // #0 was true, this should be false, not defined
			array( NS_USER_TALK, array(), array( NS_MAIN ), false ), // #1 this should be false, not defined
			array( NS_USER_TALK, array( NS_USER_TALK ), array(), false ), // #2 this should be false, excluded
			array( NS_CATEGORY, array( NS_USER_TALK ), array( NS_MAIN ), true ), // #3 this should be false, not defined unless default
			array( NS_CATEGORY, array( NS_USER_TALK ), array(), true ), // #4 this should be false, not defined
			array( NS_USER_TALK, array( NS_USER_TALK ), array( NS_USER_TALK ), false ), // #5 this should be false, excluded
			array( 72, array(), array(), false ), // #6 this should be false, excluded
			array( 73, array(), array(), false ) // #7 this should be false, excluded
		);
	}

	/**
	 * @dataProvider enabledProvider
	 */
	public function testIsWikibaseEnabled( $ns, $excluded, $enabled, $expected ) {
		$default = array( NS_MAIN, NS_TALK, NS_USER, NS_USER_TALK, NS_CATEGORY, NS_CATEGORY_TALK );
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled, $default );
		$result = $namespaceChecker->isWikibaseEnabled( $ns );
		$this->assertEquals( $expected, $result );
	}

}
