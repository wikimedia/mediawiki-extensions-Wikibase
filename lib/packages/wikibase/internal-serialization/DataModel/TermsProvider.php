<?php

namespace Wikibase\DataModel\Term;

interface TermsProvider {

	/**
	 * @return Terms
	 */
	public function getTerms();

}