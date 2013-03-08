<?php

namespace Wikibase\Repo\Database;

use Wikibase\Repo\DBConnectionProvider;
use Wikibase\Repo\Database\TableDefinition;
use Wikibase\Repo\Database\MWDB\ExtendedAbstraction;

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
 * @since wd.db
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
	 * @var ExtendedAbstraction
	 */
	private $extendedAbstraction;

	/**
	 * Constructor.
	 *
	 * @since wd.db
	 *
	 * @param DBConnectionProvider $connectionProvider
	 * @param ExtendedAbstraction $extendedAbstraction
	 */
	public function __construct( DBConnectionProvider $connectionProvider, ExtendedAbstraction $extendedAbstraction ) {
		$this->connectionProvider = $connectionProvider;
		$this->extendedAbstraction = $extendedAbstraction;
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
	 * @since wd.db
	 *
	 * @param string $tableName
	 *
	 * @return boolean
	 */
	public function tableExists( $tableName ) {
		return $this->getDB()->tableExists( $tableName, __METHOD__ );
	}

	/**
	 * @see QueryInterface::createTable
	 *
	 * @since wd.db
	 *
	 * @param TableDefinition $table
	 *
	 * @return boolean Success indicator
	 */
	public function createTable( TableDefinition $table ) {
		return $this->extendedAbstraction->createTable( $table );
	}

	/**
	 * @see QueryInterface::dropTable
	 *
	 * @since wd.db
	 *
	 * @param string $tableName
	 *
	 * @return boolean Success indicator
	 */
	public function dropTable( $tableName ) {
		return $this->getDB()->dropTable( $tableName, __METHOD__ ) !== false;
	}

	/**
	 * @see QueryInterface::insert
	 *
	 * @since wd.db
	 *
	 * @param string $tableName
	 * @param array $values
	 *
	 * @return boolean Success indicator
	 */
	public function insert( $tableName, array $values ) {
		return $this->getDB()->insert(
			$tableName,
			$values,
			__METHOD__
		) !== false;
	}

	/**
	 * @see QueryInterface::update
	 *
	 * @since wd.db
	 *
	 * @param string $tableName
	 * @param array $values
	 * @param array $conditions
	 *
	 * @return boolean Success indicator
	 */
	public function update( $tableName, array $values, array $conditions ) {
		return $this->getDB()->update(
			$tableName,
			$values,
			$conditions,
			__METHOD__
		) !== false;
	}

	/**
	 * @see QueryInterface::delete
	 *
	 * @since wd.db
	 *
	 * @param string $tableName
	 * @param array $conditions
	 *
	 * @return boolean Success indicator
	 */
	public function delete( $tableName, array $conditions ) {
		return $this->getDB()->delete(
			$tableName,
			$conditions,
			__METHOD__
		) !== false;
	}

}
