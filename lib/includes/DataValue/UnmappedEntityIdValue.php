<?php

namespace Wikibase\Lib\DataValue;

use DataValues\UnknownValue;

/**
 * @license GPL-2.0-or-later
 */
class UnmappedEntityIdValue extends UnknownValue {

	/**
	 * @return string
	 */
	public static function getType() {
		return 'wikibase-unmapped-entityid';
	}

}
