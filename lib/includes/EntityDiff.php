<?php

namespace Wikibase;

/**
 * Represents a diff between two Wikibase\Entity instances.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface EntityDiff extends IDiff {

	/**
	 * Returns a view object for the diff.
	 *
	 * @since 0.1
	 *
	 * @return EntityDiffView
	 */
	public function getView();

}
