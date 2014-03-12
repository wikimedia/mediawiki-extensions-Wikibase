<?php

namespace Wikibase\DataModel\Term;

class Description extends AbstractTerm {

	public function __construct( $languageCode, $text ) {
		// validity checks
		parent::__construct( $languageCode, $text );
	}

}