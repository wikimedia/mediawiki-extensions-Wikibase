<?php

namespace Wikibase;

/**
 * Represents a diff between two WikibaseItem instances.
 * Acts as a container for diffs between the various fields
 * of the items. Also contains methods to obtain these
 * diffs as Wikibase\Change objects.
 *
 * Immutable.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDiff extends MapDiff {

	public static function newFromItems( Item $oldItem, Item $newItem ) {
		return new static( array(
			'sites' => MapDiff::newFromArrays(
				$oldItem->getSiteLinks(),
				$newItem->getSiteLinks()
			),
			'aliases' => MapDiff::newFromArrays(
				$oldItem->getAllAliases(),
				$newItem->getAllAliases()
			)
		) );
	}

	/**
	 * Returns a MapDiff object with the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getSiteLinkDiff() {
		return $this->operations['sites'];
	}

	/**
	 * Returns a MapDiff object with the aliases differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getAliasesDiff() {
		return $this->operations['aliases'];
	}



	/**
	 * Returns if there are any changes (equivalent to: any differences between the items).
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->getSiteLinkDiff()->isEmpty()
			&& $this->getAliasesDiff()->isEmpty();
	}

}