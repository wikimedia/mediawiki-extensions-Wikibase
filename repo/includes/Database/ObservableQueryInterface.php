<?php

namespace Wikibase\Repo\Database;

use Wikibase\Repo\Database\QueryInterface;

/**
 * Mock implementation of the QueryInterface interface that allows
 * tests to assert that certain methods where called.
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
 * @since wd.db
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ObservableQueryInterface implements QueryInterface {

	/**
	 * @var callable[]
	 */
	private $callbacks = array();

	/**
	 * @param string $method
	 * @param callable $callback
	 */
	public function registerCallback( $method, $callback ) {
		$this->callbacks[$method] = $callback;
	}

	private function runCallbacks( $method, $args ) {
		if ( array_key_exists( $method, $this->callbacks ) ) {
			call_user_func_array( $this->callbacks[$method], $args );
		}
	}

	/**
	 * @see QueryInterface::tableExists
	 *
	 * @param string $tableName
	 *
	 * @return boolean
	 */
	public function tableExists( $tableName ) {
		$this->runCallbacks( __FUNCTION__, func_get_args() );
	}

}
