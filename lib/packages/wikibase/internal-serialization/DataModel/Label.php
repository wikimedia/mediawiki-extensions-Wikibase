<?php

namespace Wikibase\DataModel\Term;

class Label extends AbstractTerm {

	public function __construct( $languageCode, $text ) {
		// validity checks
		parent::__construct( $languageCode, $text );
	}

}