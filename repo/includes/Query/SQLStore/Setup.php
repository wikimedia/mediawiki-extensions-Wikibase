<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\TableBuilder;
use MessageReporter;

/**
 * Setup for the SQLStore.
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
class Setup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var TableBuilder
	 */
	private $tableBuilder;

	/**
	 * @since wd.qe
	 *
	 * @var MessageReporter|null
	 */
	private $messageReporter;

	/**
	 * @since wd.qe
	 *
	 * @param Store $sqlStore
	 * @param TableBuilder $tableBuilder
	 * @param MessageReporter|null $messageReporter
	 */
	public function __construct( Store $sqlStore, TableBuilder $tableBuilder, MessageReporter $messageReporter = null ) {
		$this->store = $sqlStore;
		$this->tableBuilder = $tableBuilder;
	}

	/**
	 * @since wd.qe
	 *
	 * @param string $message
	 */
	private function report( $message ) {
		if ( $this->messageReporter !== null ) {
			$this->messageReporter->reportMessage( $message );
		}
	}

	/**
	 * Run the setup.
	 *
	 * @since wd.qe
	 */
	public function run() {
		$this->report( 'Starting setup of ' . $this->store->getName() );

		$this->setupTables();

		// TODO

		$this->report( 'Finished setup of ' . $this->store->getName() );
	}

	/**
	 * Sets up the tables of the store.
	 *
	 * @since wd.qe
	 */
	private function setupTables() {
		foreach ( $this->store->getTables() as $table ) {
			$this->tableBuilder->createTable( $table );
		}
	}

	// TODO

}