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
interface EntityDiff extends \Diff\IDiff {

	/**
	 * Returns a view object for the diff.
	 *
	 * @since 0.1
	 *
	 * @return EntityDiffView
	 */
	public function getView();

	/**
	 * Applies this diff as a patch to the given entity.
	 *
	 * @param Entity $entity the entity to modify
	 *
	 * @return void
	 */
	public function apply( Entity $entity );

}
