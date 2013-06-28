<?php

namespace Wikibase\Lib\Test\Serializers;
use Wikibase\Lib\Serializers\ByPropertyListUnserializer;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * Tests for the Wikibase\ByPropertyListUnserializer class.
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
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyListUnserializerTest extends UnserializerBaseTest {

	/**
	 * @see UnserializerBaseTest::getClass
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ByPropertyListUnserializer';
	}

	/**
	 * @see UnserializerBaseTest::getInstance
	 *
	 * @since 0.4
	 *
	 * @return ByPropertyListUnserializer
	 */
	protected function getInstance() {
		$snakSetailizer = new SnakSerializer();
		return new ByPropertyListUnserializer( $snakSetailizer );
	}

	/**
	 * @see UnserializerBaseTest::validProvider
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$dataValue0 = new \DataValues\StringValue( 'ohi' );

		$id42 = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 );
		$id2 = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 2 );

		$snak0 = new \Wikibase\PropertyNoValueSnak( $id42 );
		$snak1 = new \Wikibase\PropertySomeValueSnak( $id2 );
		$snak2 = new \Wikibase\PropertyValueSnak( $id2, $dataValue0 );

		$validArgs[] = new \Wikibase\SnakList( array( $snak0, $snak1, $snak2 ) );

		$validArgs = $this->arrayWrap( $validArgs );

		$validArgs[] = array(
			array(),
			new \Wikibase\SnakList(),
		);

		$validArgs[] = array(
			array(
				'p42' => array(
					0 => array(
						'snaktype' => 'novalue',
						'property' => 'p42',
					),
				),
				'p2' => array(
					0 => array(
						'snaktype' => 'somevalue',
						'property' => 'p2',
					),
					1 => array(
						'snaktype' => 'value',
						'property' => 'p2',
						'datavalue' => $dataValue0->toArray(),
					),
				),
			),
			new \Wikibase\SnakList( array( $snak0, $snak1, $snak2 ) ),
		);

		return $validArgs;
	}

}
