<?php

namespace Wikibase\DataModel\Fixtures;

use Hashable;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MutableHashable implements Hashable {

	public $text = '';

	public function getHash() {
		return sha1( __CLASS__ . $this->text );
	}

}
