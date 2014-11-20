<?php

namespace Wikibase\DataModel\Fixtures;

use Hashable;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class HashArrayElement implements Hashable {

	public $text = '';

	public function __construct( $text ) {
		$this->text = $text;
	}

	public function getHash() {
		return sha1( $this->text );
	}

	public static function getInstances() {
		$stuff = array(
			'foo',
			'bar',
			'baz',
			'bah',
			'~=[,,_,,]:3',
		);

		$instances = array();

		foreach ( $stuff as $thinghy ) {
			$instances[] = new static( $thinghy );
		}

		return $instances;
	}

}
