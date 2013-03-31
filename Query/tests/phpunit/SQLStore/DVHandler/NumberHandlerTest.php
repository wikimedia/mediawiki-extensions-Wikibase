<?php

namespace Wikibase\Test\Query\SQLStore\DVHandler;

use DataValues\NumberValue;
use Wikibase\Query\SQLStore\DataValueHandler;
use Wikibase\Query\SQLStore\DataValueHandlers;
use Wikibase\Test\Query\SQLStore\DataValueHandlerTest;

/**
 * Unit tests for the Wikibase\Query\SQLStore\DVHandler\NumberHandler class.
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
 * @ingroup WikibaseQueryTest
 *
 * @group Wikibase
 * @group WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NumberHandlerTest extends DataValueHandlerTest {

	/**
	 * @see DataValueHandlerTest::getInstances
	 *
	 * @since 0.1
	 *
	 * @return DataValueHandler[]
	 */
	protected function getInstances() {
		$instances = array();

		$defaultHandlers = new DataValueHandlers();
		$instances[] = $defaultHandlers->getHandler( 'number' );

		return $instances;
	}

	/**
	 * @see DataValueHandlerTest::getValues
	 *
	 * @since 0.1
	 *
	 * @return NumberValue[]
	 */
	protected function getValues() {
		$values = array();

		$values[] = new NumberValue( 0 );
		$values[] = new NumberValue( 1 );
		$values[] = new NumberValue( 7101010 );
		$values[] = new NumberValue( 9000.1 );
		$values[] = new NumberValue( 0.000042 );
		$values[] = new NumberValue( -0.000042 );
		$values[] = new NumberValue( -123456 );
		$values[] = new NumberValue( 71010.101010 );

		return $values;
	}

}
