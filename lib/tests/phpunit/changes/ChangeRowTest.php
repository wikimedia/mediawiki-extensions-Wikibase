<?php

namespace Wikibase\Test;
use \Wikibase\ChangesTable;

/**
 * Tests for the Wikibase\ChangeRow class.
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
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 * @group WikibaseChangeRowTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangeRowTest extends \ORMRowTest {

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.2
	 * @return string
	 */
	protected function getRowClass() {
		return '\Wikibase\ChangeRow';
	}

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.2
	 * @return string
	 */
	protected function getTableInstance() {
		return ChangesTable::singleton();
	}

	public function constructorTestProvider() {
		return array(
			array( TestChanges::getChange(), true ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetUser( $changeRow ) {
		$this->assertInstanceOf( '\User', $changeRow->getUser() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetAge( $changeRow ) {
		$this->assertEquals(
			time() - (int)wfTimestamp( TS_UNIX, '20120515104713' ),
			$changeRow->getAge()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetTime( $changeRow ) {
		$this->assertEquals(
			'20120515104713',
			$changeRow->getTime()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetObjectId( $changeRow ) {
		$this->assertEquals(
			'q182',
			$changeRow->getObjectId()
		);
	}

	/**
	 * @dataProvider constructorTestProvider
	 */
	public function testSaveAndRemove( array $data, $loadDefaults ) {
		if ( !defined( 'WBC_VERSION' ) ) {
			parent::testSaveAndRemove( $data, $loadDefaults );
		} else {
			$this->markTestSkipped( "Skipping because you're running it on a WikibaseClient instance." );
		}
	}

}
