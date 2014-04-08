<?php

namespace Wikibase\DataModel\Term;

/**
 * @since 0.7.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Label extends Term {

	public function __construct( $languageCode, $text ) {
		parent::__construct( $languageCode, $text );
	}

}