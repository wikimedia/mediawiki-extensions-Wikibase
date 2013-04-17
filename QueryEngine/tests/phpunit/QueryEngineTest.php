<?php

namespace Wikibase\QueryEngine\Tests;

use Wikibase\QueryEngine\QueryEngine;

/**
 * Base test class for Wikibase\QueryEngine\QueryEngine implementing classes.
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
abstract class QueryEngineTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @since 0.1
	 *
	 * @return QueryEngine[]
	 */
	protected abstract function getInstances();

	/**
	 * @since 0.1
	 *
	 * @return QueryEngine[][]
	 */
	public function instanceProvider() {
		$argLists = array();

		foreach ( $this->getInstances() as $instance ) {
			$argLists[] = array( $instance );
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param QueryEngine $queryEngine
	 */
	public function testGetNameReturnType( QueryEngine $queryEngine ) {
		// TODO

//		$query = new \Ask\Language\Query(  );
//		$this->assertInstanceOf( 'Wikibase\QueryEngine\QueryEngineResult', $queryEngine->runQuery() );

		$this->assertTrue( true );
	}

}
