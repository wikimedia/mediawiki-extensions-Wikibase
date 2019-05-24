<?php

namespace Wikibase\Repo\SparqlEndpointReplicationStatus;


/**
 * Service interface looking up the lag of a certain sparql endpoint.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
interface SparqlEndpointReplicationStatus {

	/**
	 * How stale is the data on the endpoint?
	 *
	 * Depending on the implementation this data might be cached,
	 * and/ or actually represent an aggregated value from several endpoints.
	 *
	 * Note: This might assume that the last change to the repo happened now.
	 *
	 * @return int|null Lag in seconds or null if the lag couldn't be determined.
	 */
	public function getLag();

}