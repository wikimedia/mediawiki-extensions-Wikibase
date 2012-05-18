<?php

use Wikibase\MapDiff as MapDiff;
use Wikibase\MapDiff as ListDiff;
use Wikibase\SitelinkChange as SitelinkChange;
use Wikibase\AliasChange as AliasChange;

/**
 * Represents a diff between two WikibaseItem instances.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseItemDiff extends WikibaseEntityDiff {

	/**
	 * @var MapDiff
	 */
	protected $siteLinkDiff;

	/**
	 * @var MapDiff
	 */
	protected $aliasDiff;

	/**
	 * @since 0.1
	 *
	 * @param WikibaseItem $oldItem
	 * @param WikibaseItem $newItem
	 *
	 * @return WikibaseItemDiff
	 */
	public function __construct( WikibaseItem $oldItem, WikibaseItem $newItem ) {
		$this->siteLinkDiff = MapDiff::newFromArrays(
			$oldItem->getSiteLinks(),
			$newItem->getSiteLinks()
		);

		$this->aliasDiff = ListDiff::newFromArrays(
			$oldItem->getAllAliases(),
			$newItem->getAllAliases()
		);
	}

	/**
	 * @return boolean
	 */
	public function hasSiteLinkChanges() {
		return !$this->siteLinkDiff->isEmpty();
	}

	/**
	 * @return boolean
	 */
	public function hasAliasChanges() {
		return !$this->aliasDiff->isEmpty();
	}

	/**
	 * @return SitelinkChange
	 */
	public function getSiteLinkChange() {
		return SitelinkChange::newFromDiff( $this->siteLinkDiff );
	}

	/**
	 * @return AliasChange
	 */
	public function getAliasesChange() {
		return AliasChange::newFromDiff( $this->aliasDiff );
	}

}