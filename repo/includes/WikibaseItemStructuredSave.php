<?php

/**
 * Represents an update to the structured storage for a single WikibaseItem.
 *
 * @since 0.1
 *
 * @file WikibaseItemStructuredSave.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseItemStructuredSave extends SecondaryDataUpdate {

	/**
	 * The item to update.
	 *
	 * @since 0.1
	 * @var WikibaseItem
	 */
	protected $item;

	/**
	 * The title of the page representing the item.
	 *
	 * @since 0.1
	 * @var Title
	 */
	protected $title;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param WikibaseItem $item
	 * @param Title $title
	 */
	public function __construct( WikibaseItem $item, Title $title ) {
		$this->item = $item;
		$this->title = $title;
	}

	/**
	 * Perform the actual update.
	 *
	 * @since 0.1
	 */
	public function doUpdate() {
		$this->item->structuredSave( $this->title->getArticleID() );
	}

}