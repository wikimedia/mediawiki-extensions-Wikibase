<?php

namespace Wikibase\QueryEngine\SQLStore;

use MessageReporter;
use Wikibase\Database\QueryInterface;
use Wikibase\QueryEngine\QueryStore;
use Wikibase\QueryEngine\SQLStore\Engine\DescriptionMatchFinder;
use Wikibase\QueryEngine\SQLStore\Engine\Engine;

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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Store implements QueryStore {

	/**
	 * @since 0.1
	 *
	 * @var StoreConfig
	 */
	private $config;

	/**
	 * @since 0.1
	 *
	 * @var QueryInterface
	 */
	private $queryInterface;

	/**
	 * @since 0.1
	 *
	 * @var Factory
	 */
	private $factory;

	/**
	 * @since 0.1
	 *
	 * @param StoreConfig $config
	 * @param QueryInterface $queryInterface
	 */
	public function __construct( StoreConfig $config, QueryInterface $queryInterface ) {
		$this->config = $config;
		$this->queryInterface = $queryInterface;
		$this->factory = new Factory( $config, $queryInterface );
	}

	/**
	 * @see QueryStore::getName
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName() {
		return $this->config->getStoreName();
	}

	/**
	 * @see QueryStore::getQueryEngine
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\QueryEngine\QueryEngine
	 */
	public function getQueryEngine() {
		return new Engine(
			$this->config,
			$this->queryInterface
		);
	}

	/**
	 * @see QueryStore::getUpdater
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\QueryEngine\QueryStoreWriter
	 */
	public function getUpdater() {
		return $this->factory->newWriter();
	}

	/**
	 * @see QueryStore::getSetup
	 *
	 * @since 0.1
	 *
	 * @param MessageReporter $messageReporter
	 *
	 * @return Setup
	 */
	public function getSetup( MessageReporter $messageReporter ) {
		return new Setup(
			$this->config,
			$this->factory->getSchema(),
			$this->queryInterface,
			$this->tableBuilder,
			$messageReporter
		);
	}

	/**
	 * TODO: figure out how to merge this into the QueryEngine interface
	 *
	 * @return DescriptionMatchFinder
	 */
	public function getDescriptionMatchFinder() {
		return $this->factory->newDescriptionMatchFinder();
	}

}
