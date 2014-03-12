<?php

namespace Wikibase\DataModel\Term;

interface Term {

	/**
	 * @return string
	 */
	public function getLanguageCode();

	/**
	 * @return string
	 */
	public function getText();

}