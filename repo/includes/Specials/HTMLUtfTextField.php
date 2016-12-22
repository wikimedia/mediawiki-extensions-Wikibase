<?php

namespace Wikibase\Repo\Specials;

use HTMLTextField;
use Wikibase\StringNormalizer;

class HTMLUtfTextField extends HTMLTextField {

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	public function __construct( array $params ) {
		parent::__construct( $params );

		$this->stringNormalizer = new StringNormalizer();
	}

	public function filter( $value, $alldata ) {
		$filteredValue = $this->stringNormalizer->trimToNFC( $value );

		return parent::filter( $filteredValue, $alldata );
	}

}
