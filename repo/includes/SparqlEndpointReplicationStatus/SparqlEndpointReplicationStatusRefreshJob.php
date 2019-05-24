<?php

namespace Wikibase\Repo\SparqlEndpointReplicationStatus;
use Job;

/**
 * Job that refreshes the SPARQL endpoint replication lag.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SparqlEndpointReplicationStatusRefreshJob extends Job {

	const command = 'SparqlEndpointReplicationStatusRefreshJob';

	/**
	 * @var SparqlEndpointReplicationStatusStateHandler
	 */
	private $stateHandler;

	/**
	 * @var SparqlEndpointReplicationStatus
	 */
	private $replicationStatus;

	public function __construct(
		SparqlEndpointReplicationStatusStateHandler $stateHandler,
		SparqlEndpointReplicationStatus $replicationStatus,
		array $params = []
	) {
		parent::__construct( 'SparqlEndpointReplicationStatusRefresh', $params );
		$this->stateHandler = $stateHandler;
		$this->replicationStatus = $replicationStatus;
		$this->removeDuplicates = true;
	}

	/**
	 * @return bool
	 */
	public function run() {
		$this->stateHandler->setLag(
			$this->replicationStatus->getLag(),
			time()
		);

		return true;
	}

}