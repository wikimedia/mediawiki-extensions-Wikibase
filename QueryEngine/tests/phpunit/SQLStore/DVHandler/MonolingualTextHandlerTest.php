<?php

namespace Wikibase\QueryEngine\Tests\SQLStore\DVHandler;

use DataValues\MonolingualTextValue;
use Wikibase\QueryEngine\SQLStore\DataValueHandler;
use Wikibase\QueryEngine\SQLStore\DataValueHandlers;
use Wikibase\QueryEngine\Tests\SQLStore\DataValueHandlerTest;

/**
 * @covers Wikibase\QueryEngine\SQLStore\DVHandler\MonolingualTextHandler
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
 * @group Wikibase
 * @group WikibaseQueryEngine
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MonolingualTextHandlerTest extends DataValueHandlerTest {

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
		$instances[] = $defaultHandlers->getHandler( 'monolingualtext' );

		return $instances;
	}

	/**
	 * @see DataValueHandlerTest::getValues
	 *
	 * @since 0.1
	 *
	 * @return MonolingualTextValue[]
	 */
	protected function getValues() {
		$values = array();

		$values[] = new MonolingualTextValue( 'en', 'foo bar baz' );
		$values[] = new MonolingualTextValue( 'en', ' foo bar baz bah!' );
		$values[] = new MonolingualTextValue( 'nyan', '~=[,,_,,]:3 ~=[,,_,,]:3 ~=[,,_,,]:3 ~=[,,_,,]:3 ~=[,,_,,]:3' );
		$values[] = new MonolingualTextValue( 'fr', '' );
		$values[] = new MonolingualTextValue( 'de', '    ' );

		return $values;
	}

}
