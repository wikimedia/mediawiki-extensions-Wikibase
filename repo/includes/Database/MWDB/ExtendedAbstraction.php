<?php

namespace Wikibase\Repo\Database\MWDB;

use Wikibase\Repo\Database\TableDefinition;
use Wikibase\Repo\DBConnectionProvider;
use InvalidArgumentException;
use DatabaseBase;

/**
 * Base database abstraction class to put stuff into that is not present
 * in the MW core db abstraction layer.
 *
 * Like to core class DatabaseBase, each deriving class provides support
 * for a specific type of database.
 *
 * Everything implemented in these classes could go into DatabaseBase and
 * deriving classes, though this might take quite some time, hence implementation
 * is first done here. If you feel like taking core CR crap and waiting a few
 * months, by all means try to get the functionality into core.
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
abstract class ExtendedAbstraction {

	/**
	 * @since wd.db
	 *
	 * @var DBConnectionProvider
	 */
	private $connectionProvider;

	/**
	 * @since wd.db
	 *
	 * @param DBConnectionProvider $connectionProvider
	 */
	public function __construct( DBConnectionProvider $connectionProvider ) {
		$this->connectionProvider = $connectionProvider;
	}

	/**
	 * @since wd.db
	 *
	 * @return DatabaseBase
	 * @throws InvalidArgumentException
	 */
	protected function getDB() {
		$db = $this->connectionProvider->getConnection();

		if ( $db->getType() !== $this->getType() ) {
			throw new InvalidArgumentException();
		}
	}

	/**
	 * Create the provided table.
	 *
	 * @since wd.db
	 *
	 * @param TableDefinition $table
	 *
	 * @return boolean Success indicator
	 */
	public abstract function createTable( TableDefinition $table );

	/**
	 * Returns the type of the supported MW DB abstraction class.
	 *
	 * @since wd.db
	 *
	 * @return string
	 */
	protected abstract function getType();

}