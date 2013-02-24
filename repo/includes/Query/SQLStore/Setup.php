<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\TableBuilder;
use MessageReporter;

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
	 * @since 0.4
	 *
	 * @var MessageReporter|null
	 */
	private $messageReporter;

	/**
	 * @since 0.4
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
	 * @since 0.4
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
	 * @since 0.4
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
	 * @since 0.4
	 */
	private function setupTables() {
		foreach ( $this->store->getTables() as $table ) {
			$this->tableBuilder->createTable( $table );
		}
	}

	// TODO

}