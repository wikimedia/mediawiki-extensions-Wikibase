<?php

namespace Wikibase\DataModel\Term;

class LabelList extends TermList {

	/**
	 * @param Label[] $labels
	 */
	public function __construct( array $labels ) {
		// TODO: validation
		parent::__construct( $labels );
	}

}