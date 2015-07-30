<?php

namespace Wikibase\DataModel\Term;

/**
 * @since 4.1
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface DescriptionsProvider {

	/**
	 * @return TermList
	 */
	public function getDescriptions();

}
