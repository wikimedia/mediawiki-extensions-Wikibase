<?php

namespace Wikibase\Repo\Api;

/**
 * TEST
 */
interface SetClaimRequestParser {

	/**
	 * @param array $params
	 * @return SetClaimRequest
	 */
	public function parse( array $params );

}
