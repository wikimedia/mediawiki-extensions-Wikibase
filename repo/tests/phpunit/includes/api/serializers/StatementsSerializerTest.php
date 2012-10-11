<?php

namespace Wikibase\Test;
use Wikibase\ApiSerializerObject;

/**
 * Tests for the Wikibase\StatementsSerializer class.
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
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseApiSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementsSerializerTest extends ApiSerializerBaseTest {

	/**
	 * @see ApiSerializerBaseTest::getClass
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\StatementsSerializer';
	}

	/**
	 * @see ApiSerializerBaseTest::validProvider
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$statement0 = new \Wikibase\StatementObject( new \Wikibase\ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) ) );
		$statement1 = new \Wikibase\StatementObject( new \Wikibase\ClaimObject( new \Wikibase\PropertySomeValueSnak( 42 ) ) );
		$statement2 = new \Wikibase\StatementObject( new \Wikibase\ClaimObject( new \Wikibase\PropertyNoValueSnak( 1 ) ) );
		$statementList = new \Wikibase\StatementList( array( $statement0, $statement1, $statement2 ) );

		$validArgs = $this->arrayWrap( $validArgs );

		$validArgs[] = array(
			new \Wikibase\StatementList(),
			array(),
		);

		$validArgs[] = array(
			$statementList,
			array(
				'p42' => array(
					0 => array(
						'mainsnak' => array(
							'snaktype' => 'novalue',
							'property' => 'p42',
						),
						'qualifiers' => array(),
						// TODO
					),
					1 => array(
						'mainsnak' => array(
							'snaktype' => 'somevalue',
							'property' => 'p42',
						),
						'qualifiers' => array(),
						// TODO
					),
				),
				'p1' => array(
					0 => array(
						'mainsnak' => array(
							'snaktype' => 'novalue',
							'property' => 'p1',
						),
						'qualifiers' => array(),
						// TODO
					),
				),
			),
		);

		return $validArgs;
	}

}
