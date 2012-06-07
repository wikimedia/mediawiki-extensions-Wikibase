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
class ItemDiff extends EntityDiff {

	/**
	 * @var MapDiff
	 */
	protected $siteLinkDiff;

	/**
	 * @var MapDiff
	 */
	protected $aliasDiff;

	/**
	 * @var array|false
	 */
	protected $changes = false;

	/**
	 * @var Item
	 */
	protected $oldItem;

	/**
	 * @var Item
	 */
	protected $newItem;

	/**
	 * Constructs a new
	 *
	 * @since 0.1
	 *
	 * @param Item $oldItem
	 * @param Item $newItem
	 *
	 * @return ItemDiff
	 */
	public function __construct( Item $oldItem, Item $newItem ) {
		$this->oldItem = $oldItem;
		$this->newItem = $newItem;
	}

	/**
	 * Create the sitelinks diff if not already done so.
	 */
	protected function diffSiteLinks() {
		if ( !isset( $this->siteLinkDiff ) ) {
			$this->siteLinkDiff = MapDiff::newFromArrays(
				$this->oldItem->getSiteLinks(),
				$this->newItem->getSiteLinks()
			);
		}
	}

	/**
	 * Create the aliases diff if not already done so.
	 */
	protected function diffAliases() {
		if ( !isset( $this->aliasDiff ) ) {
			$this->aliasDiff = MapDiff::newFromArrays(
				$this->oldItem->getAllAliases(),
				$this->newItem->getAllAliases()
			);
		}
	}

	/**
	 * Returns if there are any changes to the sitelinks.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function hasSiteLinkChanges() {
		$this->diffSiteLinks();
		return !$this->siteLinkDiff->isEmpty();
	}

	/**
	 * Returns if there are any changes to the aliases.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function hasAliasChanges() {
		$this->diffAliases();
		return !$this->aliasDiff->isEmpty();
	}

	/**
	 * Returns a MapDiff object with the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getSiteLinkDiff() {
		$this->diffSiteLinks();
		return $this->siteLinkDiff;
	}

	/**
	 * Returns a MapDiff object with the aliases differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getAliasesDiff() {
		$this->diffAliases();
		return $this->aliasDiff;
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