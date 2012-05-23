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
		$this->siteLinkDiff = MapDiff::newFromArrays(
			$oldItem->getSiteLinks(),
			$newItem->getSiteLinks()
		);

		$this->aliasDiff = MapDiff::newFromArrays(
			$oldItem->getAllAliases(),
			$newItem->getAllAliases()
		);
	}

	/**
	 * Returns if there are any changes to the sitelinks.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function hasSiteLinkChanges() {
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
		return !$this->aliasDiff->isEmpty();
	}

	/**
	 * Returns a SitelinkChange object constructed from the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return SitelinkChange
	 */
	public function getSiteLinkChange() {
		return SitelinkChange::newFromDiff( $this->siteLinkDiff );
	}

	/**
	 * Returns a AliasChange object constructed from the aliases differences.
	 *
	 * @since 0.1
	 *
	 * @return AliasChange
	 */
	public function getAliasesChange() {
		return AliasChange::newFromDiff( $this->aliasDiff );
	}

	/**
	 * Returns a list of Change objects representing all changes
	 * made to the item. No change objects are constructed for
	 * fields where no changes where made (ie those where the diff is empty).
	 *
	 * @since 0.1
	 *
	 * @return array of Wikibase\Change
	 */
	public function getChanges() {
		if ( $this->changes === false ) {
			$this->changes = array();

			if ( $this->hasAliasChanges() ) {
				$this->changes[] = $this->getAliasesChange();
			}

			if ( $this->hasSiteLinkChanges() ) {
				$this->changes[] = $this->getSiteLinkChange();
			}
		}

		return $this->changes;
	}

	/**
	 * Returns if there are any changes (equivalent to: any differences between the items).
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function hasChanges() {
		return $this->getChanges() !== array();
	}

}