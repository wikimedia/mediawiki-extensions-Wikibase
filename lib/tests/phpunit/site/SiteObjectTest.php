<?php

/**
 * Tests for the SiteObject class.
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
 * @since 1.20
 *
 * @ingroup Site
 * @ingroup Test
 *
 * @group Site
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteRowTest extends ORMRowTest {

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 1.20
	 * @return string
	 */
	protected function getRowClass() {
		return 'SiteObject';
	}

	/**
	 * @see ORMRowTest::getTableInstance
	 * @since 1.20
	 * @return IORMTable
	 */
	protected function getTableInstance() {
		return SitesTable::singleton();
	}

	/**
	 * @see ORMRowTest::constructorTestProvider
	 * @since 1.20
	 * @return array
	 */
	public function constructorTestProvider() {
		$rows = array(
			array( 'enwiki' ),
		);

		foreach ( $rows as &$args ) {
			$fields = array(
				'global_key' => $args[0],
			);

			$args = array( $fields, true );
		}

		return $rows;
	}

	/**
	 * @dataProvider constructorTestProvider
	 */
	public function testConstructorEvenMore( array $fields ) {
		$site = \SitesTable::singleton()->newRow( $fields, true );

		$functionMap = array(
			'getGlobalId',
		);

		reset( $fields );

		foreach ( $functionMap as $functionName ) {
			$this->assertEquals( current( $fields ), call_user_func( array( $site, $functionName ) ) );
			next( $fields );
		}
	}

}