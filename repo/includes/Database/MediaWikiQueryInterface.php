<?php

namespace Wikibase\Repo\Database;

use Wikibase\Repo\DBConnectionProvider;

/**
 * Implementation of the QueryInterface interface using the MediaWiki
 * database abstraction layer where possible.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MediaWikiQueryInterface implements QueryInterface {

	/**
	 * @var DBConnectionProvider
	 */
	private $connectionProvider;

	/**
	 * @param DBConnectionProvider $connectionProvider
	 */
	public function __construct( DBConnectionProvider $connectionProvider ) {
		$this->connectionProvider = $connectionProvider;
	}

	/**
	 * @return \DatabaseBase
	 */
	private function getDB() {
		return $this->connectionProvider->getConnection();
	}

	/**
	 * @see QueryInterface::tableExists
	 *
	 * @param string $tableName
	 *
	 * @return boolean
	 */
	public function tableExists( $tableName ) {
		return $this->getDB()->tableExists( $tableName, __METHOD__ );
	}

}
