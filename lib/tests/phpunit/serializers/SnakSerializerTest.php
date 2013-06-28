<?php

namespace Wikibase\Test;

/**
 * Tests for the Wikibase\SnakSerializer class.
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
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\SnakSerializer';
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$id = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 );

		$validArgs[] = array(
			new \Wikibase\PropertyNoValueSnak( $id ),
			array(
				'snaktype' => 'novalue',
				'property' => 'p42',
			)
		);

		$validArgs[] = array(
			new \Wikibase\PropertySomeValueSnak( $id ),
			array(
				'snaktype' => 'somevalue',
				'property' => 'p42',
			)
		);

		$dataValue = new \DataValues\StringValue( 'ohi' );

		$validArgs[] = array(
			new \Wikibase\PropertyValueSnak( $id, $dataValue ),
			array(
				'snaktype' => 'value',
				'property' => 'p42',
				'datavalue' => $dataValue->toArray(),
			)
		);

		return $validArgs;
	}

}
