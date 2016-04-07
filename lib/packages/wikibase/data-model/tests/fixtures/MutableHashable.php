<?php

namespace Wikibase\DataModel\Fixtures;

use Hashable;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MutableHashable implements Hashable {

	public $text = '';

	public function getHash() {
		return sha1( __CLASS__ . $this->text );
	}

}
