<?php

namespace Wikibase\DataModel\Fixtures;

use Wikibase\DataModel\HashArray;

/**
 * @licence GNU GPL v2+
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
