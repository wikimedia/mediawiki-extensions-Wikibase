<?php

namespace Wikibase\DataModel\Term;

class DescriptionList extends TermList {

	/**
	 * @param Description[] $descriptions
	 */
	public function __construct( array $descriptions ) {
		// TODO: validation
		parent::__construct( $descriptions );
	}

}