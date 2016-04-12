<?php

namespace Wikibase\DataModel\Fixtures;

use Wikibase\DataModel\HashArray;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class HashArrayWithDuplicates extends HashArray {

	public function getObjectType() {
		return '\Hashable';
	}

	public function __construct( $input = null ) {
		$this->acceptDuplicates = true;
		parent::__construct( $input );
	}

}
