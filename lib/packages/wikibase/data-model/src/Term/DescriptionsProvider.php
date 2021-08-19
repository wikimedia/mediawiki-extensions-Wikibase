<?php

namespace Wikibase\DataModel\Term;

/**
 * Common interface for classes (typically Entities) that contain a TermList, representing
 * descriptions. Implementations must guarantee this returns the original, mutable object by
 * reference.
 *
 * @since 4.1
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface DescriptionsProvider {

	/**
	 * This is guaranteed to return the original, mutable object by reference.
	 *
	 * @return TermList
	 */
	public function getDescriptions();

}
