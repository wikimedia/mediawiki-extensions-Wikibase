<?php

namespace Wikibase\Repo\Query\SQLStore;

use MessageReporter;
use Wikibase\Database\QueryInterface;
use Wikibase\Database\TableBuilder;
use Wikibase\Repo\Query\QueryStore;

/**
 * Simple query store for relational SQL databases.
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
class Store implements QueryStore {

	/**
	 * @since wd.qe
	 *
	 * @var StoreConfig
	 */
	private $config;

	/**
	 * @since wd.qe
	 *
	 * @var QueryInterface
	 */
	private $queryInterface;

	/**
	 * @since wd.qe
	 *
	 * @var TableBuilder|null
	 */
	private $tableBuilder;

	/**
	 * Constructor.
	 *
	 * @since wd.qe
	 *
	 * @param StoreConfig $config
	 * @param QueryInterface $queryInterface
	 */
	public function __construct( StoreConfig $config, QueryInterface $queryInterface ) {
		$this->config = $config;
		$this->queryInterface = $queryInterface;
	}

	/**
	 * Sets the table builder to use for creating tables.
	 *
	 * @since wd.qe
	 *
	 * @param TableBuilder $tableBuilder
	 */
	public function setTableBuilder( TableBuilder $tableBuilder ) {
		$this->tableBuilder = $tableBuilder;
	}

	/**
	 * @see QueryStore::getName
	 *
	 * @since wd.qe
	 *
	 * @return string
	 */
	public function getName() {
		return $this->config->getStoreName();
	}

	/**
	 * @see QueryStore::getQueryEngine
	 *
	 * @since wd.qe
	 *
	 * @return \Wikibase\Repo\Query\QueryEngine
	 */
	public function getQueryEngine() {
		return new Engine( $this->config, $this->queryInterface );
	}

	/**
	 * @see QueryStore::getUpdater
	 *
	 * @since wd.qe
	 *
	 * @return \Wikibase\Repo\Query\QueryStoreUpdater
	 */
	public function getUpdater() {
		return new Updater( $this->config, $this->queryInterface );
	}

	/**
	 * @see QueryStore::setup
	 *
	 * @since wd.qe
	 *
	 * @param MessageReporter $messageReporter
	 *
	 * @return boolean Success indicator
	 */
	public function setup( MessageReporter $messageReporter ) {
		$setup = new Setup( $this->config, $this->queryInterface, $this->tableBuilder, $messageReporter );

		return $setup->install();
	}

	/**
	 * @see QueryStore::drop
	 *
	 * @since wd.qe
	 *
	 * @param MessageReporter $messageReporter
	 *
	 * @return boolean Success indicator
	 */
	public function drop( MessageReporter $messageReporter ) {
		$setup = new Setup( $this->config, $this->queryInterface, $this->tableBuilder, $messageReporter );

		return $setup->uninstall();
	}

}
