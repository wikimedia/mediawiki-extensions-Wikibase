<?php

namespace Wikibase\DataModel\Fixtures;

use Hashable;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class HashableObject implements Hashable {

	protected $var;

	public function __construct( $var ) {
		$this->var = $var;
	}

	public function getHash() {
		return sha1( $this->var );
	}

}
