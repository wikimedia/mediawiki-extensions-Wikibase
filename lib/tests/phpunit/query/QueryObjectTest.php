<?php

namespace Wikibase\Test;
use \Wikibase\QueryObject as QueryObject;
use \Wikibase\Query as Query;

/**
 * Tests for the Wikibase\QueryObject class.
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseQuery
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class QueryObjectTest extends EntityObjectTest {

	/**
	 * @see EntityObjectTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\Query
	 */
	protected function getNewEmpty() {
		return QueryObject::newEmpty();
	}

	/**
	 * @see   EntityObjectTest::getNewFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return \Wikibase\Entity
	 */
	protected function getNewFromArray( array $data ) {
		return QueryObject::newFromArray( $data );
	}
}