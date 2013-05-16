<?php

namespace Wikibase\QueryEngine\SQLStore\Engine;

use Ask\Language\Description\Description;
use Ask\Language\Option\QueryOptions;
use Ask\Language\Query;
use Wikibase\Database\QueryInterface;
use Wikibase\QueryEngine\QueryEngine;
use Wikibase\QueryEngine\QueryEngineResult;
use Wikibase\QueryEngine\SQLStore\StoreConfig;

/**
 * Simple query engine that works on top of the SQLStore.
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
class Engine implements QueryEngine {

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
	 * @param StoreConfig $storeConfig
	 * @param QueryInterface $queryInterface
	 */
	public function __construct( StoreConfig $storeConfig, QueryInterface $queryInterface ) {
		$this->config = $storeConfig;
		$this->queryInterface = $queryInterface;
	}

	/**
	 * @see QueryEngine::runQuery
	 *
	 * @since 0.1
	 *
	 * @param Query $query
	 *
	 * @return QueryEngineResult
	 */
	public function runQuery( Query $query ) {
		// TODO
		$internalEntityIds = $this->findQueryMatches( $query->getDescription(), $query->getOptions() );

		$result = $this->selectRequestedFields( $internalEntityIds, $query->getSelectionRequests() );

		return $result;
	}

	/**
	 * Selects all the quested data from the matching entities.
	 * This data is put in a QueryEngineResult object which is then returned.
	 *
	 * @since 0.1
	 *
	 * @param array $internalEntityIds
	 * @param array $query
	 *
	 * @return QueryEngineResult
	 */
	private function selectRequestedFields( array $internalEntityIds, array $query ) {
		// TODO
	}

}
