<?php

namespace Wikibase\Repo\Specials\HTMLForm;

use HTMLTextField;
use Wikibase\Lib\StringNormalizer;

/**
 * A variant of an HTMLTextField that forcefully applies trimming.
 *
 * @license GPL-2.0-or-later
 */
class HTMLTrimmedTextField extends HTMLTextField {

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	public function __construct( array $params ) {
		parent::__construct( $params );

		$this->stringNormalizer = new StringNormalizer();
	}

	public function filter( $value, $alldata ) {
		if ( is_string( $value ) ) {
			$value = $this->stringNormalizer->trimToNFC( $value );
		}

		return parent::filter( $value, $alldata );
	}

}
