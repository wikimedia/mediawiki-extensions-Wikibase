<?php

namespace Wikibase\DataModel\Term;

/**
 * Terms are immutable value objects.
 *
 * @since 0.7.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
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