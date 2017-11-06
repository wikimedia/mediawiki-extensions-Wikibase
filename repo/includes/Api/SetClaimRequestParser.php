<?php

namespace Wikibase\Repo\Api;

/**
 * Service turning an array of API parameters (as received e.g. from the framework) into
 * SetClaimRequest object.
 */
interface SetClaimRequestParser {

	/**
	 * @param array $params
	 * @return SetClaimRequest
	 */
	public function parse( array $params );

}
