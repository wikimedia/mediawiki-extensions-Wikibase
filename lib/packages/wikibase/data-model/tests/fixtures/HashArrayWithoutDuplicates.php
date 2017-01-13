<?php

namespace Wikibase\DataModel\Fixtures;

use Wikibase\DataModel\HashArray;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class HashArrayWithoutDuplicates extends HashArray {

	public function getObjectType() {
		return '\Hashable';
	}

}
