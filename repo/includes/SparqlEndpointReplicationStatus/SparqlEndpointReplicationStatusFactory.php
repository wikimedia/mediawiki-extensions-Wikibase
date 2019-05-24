<?php

namespace Wikibase\Repo\SparqlEndpointReplicationStatus;


/**
 * TODO
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SparqlEndpointReplicationStatusFactory {

	public function newSparqlEndpointReplicationStatus( array $params = [] ): SparqlEndpointReplicationStatus {
		return new WikimediaPrometheusSparqlEndpointReplicationStatus();
	}

}