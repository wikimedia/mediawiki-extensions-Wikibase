<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Query\QueryStore;
use Wikibase\Repo\Database\TableDefinition;

class Store implements QueryStore {

	/**
	 * @since 0.4
	 *
	 * @return TableDefinition
	 */
	public function getTables() {
		return array();
	}

	/**
	 * @see QueryStore::getName
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getName() {
		return 'Wikibase SQL store';
	}

	// TODO

}