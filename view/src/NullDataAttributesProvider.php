<?php

namespace Wikibase\View;

use stdClass;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class NullDataAttributesProvider extends DataAttributesProvider {

	/**
	 * @param object $object
	 *
	 * @return array Always empty.
	 */
	public function getDataAttributes( $object ) {
		return [];
	}

}
