<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Wikibase\QueryEngine\SQLStore\DataValueHandlers;
use Wikibase\QueryEngine\SQLStore\DataValueTable;

/**
 * Unit tests for the Wikibase\QueryEngine\SQLStore\DataValueHandler implementing classes.
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
 * @since 0.1
 *
 * @ingroup WikibaseQueryEngineTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DataValueTableTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @since 0.1
	 *
	 * @return DataValueTable[][]
	 */
	public function instanceProvider() {
		$defaultHandlers = new DataValueHandlers();

		$argLists = array();

		foreach ( $defaultHandlers->getHandlers() as $handler ) {
			$argLists[] = array( $handler->getDataValueTable() );
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param DataValueTable $dvTable
	 */
	public function testGetValueFieldNameReturnValue( DataValueTable $dvTable ) {
		$valueFieldName = $dvTable->getValueFieldName();

		$this->assertInternalType( 'string', $valueFieldName );

		$this->assertTrue(
			$dvTable->getTableDefinition()->hasFieldWithName( $valueFieldName ),
			'The value field is present in the table'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param DataValueTable $dvTable
	 */
	public function testGetSortFieldNameReturnValue( DataValueTable $dvTable ) {
		$sortFieldName = $dvTable->getSortFieldName();

		$this->assertInternalType( 'string', $sortFieldName );

		$this->assertTrue(
			$dvTable->getTableDefinition()->hasFieldWithName( $sortFieldName ),
			'The sort field is present in the table'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param DataValueTable $dvTable
	 */
	public function testGetLabelFieldNameReturnValue( DataValueTable $dvTable ) {
		$labelFieldName = $dvTable->getLabelFieldName();

		$this->assertTrue(
			$labelFieldName === null || is_string( $labelFieldName ),
			'The label field name needs to be either string or null'
		);

		if ( is_string( $labelFieldName ) ) {
			$this->assertTrue(
				$dvTable->getTableDefinition()->hasFieldWithName( $labelFieldName ),
				'The label field is present in the table'
			);
		}
	}

}
