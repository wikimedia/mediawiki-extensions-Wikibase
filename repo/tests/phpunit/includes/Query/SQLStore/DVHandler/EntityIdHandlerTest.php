<?php

namespace Wikibase\Repo\Test\Query\SQLStore\DVHandler;

use Wikibase\Repo\Query\SQLStore\DataValueHandler;
use Wikibase\Repo\Query\SQLStore\DVHandler\EntityIdHandler;
use Wikibase\Repo\Test\Query\SQLStore\DataValueHandlerTest;
use Wikibase\EntityId;

/**
 * Unit tests for the Wikibase\Repo\Query\SQLStore\DVHandler\EntityId class.
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
 * @since wd.qe
 *
 * @ingroup WikibaseRepoTest
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdHandlerTest extends DataValueHandlerTest {

	/**
	 * @see DataValueHandlerTest::getInstances
	 *
	 * @since wd.qe
	 *
	 * @return DataValueHandler[]
	 */
	protected function getInstances() {
		$instances = array();

		$instances[] = new EntityIdHandler();

		return $instances;
	}

	/**
	 * @see DataValueHandlerTest::getValues
	 *
	 * @since wd.qe
	 *
	 * @return EntityId[]
	 */
	protected function getValues() {
		$values = array();

		$values[] = new EntityId( 'item', 42 );
		$values[] = new EntityId( 'item', 9001 );
		$values[] = new EntityId( 'property', 23 );
		$values[] = new EntityId( 'query', 7201010 );

		return $values;
	}

}
