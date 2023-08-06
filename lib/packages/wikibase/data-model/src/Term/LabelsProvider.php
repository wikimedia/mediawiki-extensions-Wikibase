<?php

namespace Wikibase\DataModel\Term;

/**
 * Common interface for classes (typically Entities) that contain a TermList, representing labels.
 * Implementations must guarantee this returns the original, mutable object.
 *
 * @since 4.1
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface LabelsProvider {

	/**
	 * This is guaranteed to return the original, mutable object.
	 *
	 * @return TermList
	 */
	public function getLabels();

}
