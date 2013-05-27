<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Wikibase\QueryEngine\SQLStore\DataValueHandlers;

/**
 * @covers Wikibase\QueryEngine\SQLStore\DataValueHandlers
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
class DataValueHandlersTest extends \PHPUnit_Framework_TestCase {

	public function testGetHandlersReturnType() {
		$defaultHandlers = new DataValueHandlers();
		$defaultHandlers = $defaultHandlers->getHandlers();

		$this->assertInternalType( 'array', $defaultHandlers );
		$this->assertContainsOnlyInstancesOf( 'Wikibase\QueryEngine\SQLStore\DataValueHandler', $defaultHandlers );
	}

}
