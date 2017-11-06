<?php

namespace Wikibase\Repo\Api;

/**
 * Service that turns arrays of parameters passed to wbsetclaim API into request objects,
 * and also determines whether parameters contain a request the particular service
 * can/should handle (i.e. if it is only meant to handle the particular class of requests).
 */
interface DispatchableSetClaimRequestParser extends SetClaimRequestParser {

	/**
	 * @param array $params
	 * @return bool
	 */
	public function canParse( array $params );

}
