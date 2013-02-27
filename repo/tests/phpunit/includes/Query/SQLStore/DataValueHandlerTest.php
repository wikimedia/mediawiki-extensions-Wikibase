<?php

namespace Wikibase\Repo\Test\Query\SQLStore;

use Wikibase\Repo\Query\SQLStore\DataValueHandler;

/**
 * Unit tests for the Wikibase\Repo\Query\SQLStore\DataValueHandler implementing classes.
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class DataValueHandlerTest extends \MediaWikiTestCase {

	/**
	 * @since wd.qe
	 *
	 * @return DataValueHandler[]
	 */
	protected abstract function getInstances();

	/**
	 * @since wd.qe
	 *
	 * @return DataValueHandler[][]
	 */
	public function instanceProvider() {
		return $this->arrayWrap( $this->getInstances() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param DataValueHandler $dvHandler
	 */
	public function testGetTableDefinitionReturnType( DataValueHandler $dvHandler ) {
		// TODO
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param DataValueHandler $dvHandler
	 */
	public function testGetValueFieldReturnValue( DataValueHandler $dvHandler ) {
		// TODO
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param DataValueHandler $dvHandler
	 */
	public function testGetSortFieldReturnValue( DataValueHandler $dvHandler ) {
		// TODO
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param DataValueHandler $dvHandler
	 */
	public function testNewDataValueFromDbValue( DataValueHandler $dvHandler ) {
		// TODO
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param DataValueHandler $dvHandler
	 */
	public function testGetLabelFieldReturnValue( DataValueHandler $dvHandler ) {
		// TODO
	}

}
