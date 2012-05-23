<?php

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
	 * @var WikibaseMapDiff
	 */
	protected $siteLinkDiff;

	/**
	 * @var WikibaseListDiff
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
		$this->siteLinkDiff = WikibaseMapDiff::newFromArrays(
			$oldItem->getSiteLinks(),
			$newItem->getSiteLinks()
		);

		$this->aliasDiff = WikibaseListDiff::newFromArrays(
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
	 * @return WikibaseSitelinkChange
	 */
	public function getSiteLinkChange() {
		return WikibaseSitelinkChange::newFromDiff( $this->siteLinkDiff );
	}

	/**
	 * @return WikibaseAliasChange
	 */
	public function getAliasesChange() {
		return WikibaseAliasChange::newFromDiff( $this->aliasDiff );
	}

}