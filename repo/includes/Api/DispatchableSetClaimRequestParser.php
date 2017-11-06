<?php

namespace Wikibase\Repo\Api;

/**
 * TEST
 */
interface DispatchableSetClaimRequestParser extends SetClaimRequestParser {

	/**
	 * @param array $params
	 * @return bool
	 */
	public function canParse( array $params );

}
