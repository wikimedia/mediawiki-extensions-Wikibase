<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\TableDefinition;

/**
 * StoreConfiguration for the SQL Store.
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
 * @since wd.qe
 *
 * @file
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreConfig {

	/**
	 * @since wd.qe
	 *
	 * @var string
	 */
	private $name;

	/**
	 * @since wd.qe
	 *
	 * @var string
	 */
	private $tablePrefix;

	/**
	 * The DataValueHandlers for the DataValue types supported by this configuration.
	 * Array keys are DataValue type identifiers (string) pointing to the corresponding DataValueHandler.
	 *
	 * @since wd.qe
	 *
	 * @var DataValueHandler[]
	 */
	private $dvHandlers = array();


	const SNAK_MAIN = 0;
	const SNAK_QUALIFIER = 1;

	/**
	 * Constructor.
	 *
	 * @since wd.qe
	 *
	 * @param string $storeName
	 * @param string $tablePrefix
	 * @param DataValueHandler[] $dataValueHandlers
	 */
	public function __construct( $storeName, $tablePrefix, array $dataValueHandlers ) {
		$this->name = $storeName;
		$this->tablePrefix = $tablePrefix;
		$this->dvHandlers = $dataValueHandlers;
	}

	/**
	 * @since wd.qe
	 *
	 * @return string
	 */
	public function getStoreName() {
		return $this->name;
	}

	/**
	 * @since wd.qe
	 *
	 * @return string
	 */
	public function getTablePrefix() {
		return $this->tablePrefix;
	}

	/**
	 * @since wd.qe
	 *
	 * @return DataValueHandler[]
	 */
	public function getDataValueHandlers() {
		return $this->dvHandlers;
	}

	// TODO

}


class Schema {

	/**
	 * @since wd.qe
	 *
	 * @var StoreConfig
	 */
	private $config;

	/**
	 * The DataValueHandlers for the DataValue types supported by this configuration.
	 * Array keys are snak types pointing to arrays where array keys are DataValue type
	 * identifiers (string) pointing to the corresponding DataValueHandler.
	 *
	 * @since wd.qe
	 *
	 * @var DataValueHandler[]
	 */
	private $dvHandlers = array();

	/**
	 * @since wd.qe
	 *
	 * @param StoreConfig $config
	 */
	public function __construct( StoreConfig $config ) {
		$this->config = $config;
		$this->expandDataValueHandlers( $config->getDataValueHandlers() );
	}

	/**
	 * @since wd.qe
	 *
	 * @param DataValueHandler[] $dataValueHandlers
	 */
	private function expandDataValueHandlers( array $dataValueHandlers ) {
		foreach ( $this->snakTypes as $snakType => $snakPrefix ) {
			$handlers = array();

			foreach ( $dataValueHandlers as $dataValueHandler ) {
				$table = $dataValueHandler->getTableDefinition();
				$table = $table->mutateName( $snakPrefix . $table->getName() );


			}

			$this->dvHandlers[$snakType] = $handlers;
		}
	}

	public function getHandlerForType( $dataType, $snakType ) {


		// TODO: checks




	}

	/**
	 * @since wd.qe
	 *
	 * @return DataValueHandler[]
	 */
	public function getDataValueHandlers() {
		return $this->dvHandlers;
	}

}