<?php

namespace Wikibase\Test;
use \Wikibase\ChangesTable;

/**
 * Tests for the Wikibase\DiffChange class.
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
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityChangeTest extends \ORMRowTest {

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.3
	 * @return string
	 */
	protected function getRowClass() {
		return '\Wikibase\EntityChange';
	}

	protected function getClass() {
		return 'Wikibase\EntityChange';
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
	public function testGetMetadata( $entityChange ) {
		$this->assertEquals(
			array(
				'rc_user' => 0,
				'rc_user_text' => '208.80.152.201'
			),
			$entityChange->getMetadata()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testMetadata( $entityChange ) {
		$entityChange->setMetadata(
			array(
				'rc_user' => 0,
				'rc_user_text' => '171.80.182.208'
			),
			true
		);
		$this->assertEquals(
			array(
				'rc_user' => 0,
				'rc_user_text' => '171.80.182.208'
			),
			$entityChange->getMetadata()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetInvalidMetadata( $entityChange ) {
		$entityChange->setMetadata( array(
			'rc_kittens' => 3,
			'rc_user' => 0,
			'rc_user_text' => '171.80.182.208'
		) );
		$this->assertEquals(
			array(
				'rc_user' => 0,
				'rc_user_text' => '171.80.182.208'
			),
			$entityChange->getMetadata()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetEmptyMetadata( $entityChange ) {
		$entityChange->setField( 'info', array() );
		$this->assertEquals(
			false,
			$entityChange->getMetadata()
		);
	}
}
