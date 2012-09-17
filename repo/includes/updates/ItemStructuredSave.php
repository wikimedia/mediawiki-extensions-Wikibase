<?php

namespace Wikibase;
use Title;

/**
 * Represents an update to the structured storage for a single WikibaseItem.
 * TODO: we could keep track of actual changes in a lot of cases, and so be able to do less (expensive) queries to update.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemStructuredSave extends \DataUpdate {

	/**
	 * The item to update.
	 *
	 * @since 0.1
	 * @var ItemContent
	 */
	protected $itemContent;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param ItemContent $itemContent
	 */
	public function __construct( ItemContent $itemContent ) {
		$this->itemContent = $itemContent;
	}

	/**
	 * Returns the ItemContent that's being saved.
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public function getItemContent() {
		return $this->itemContent;
	}

	/**
	 * Perform the actual update.
	 *
	 * @since 0.1
	 */
	public function doUpdate() {
		wfProfileIn( __METHOD__ );

		StoreFactory::getStore()->newEntityUpdateHandler()->handleUpdate( $this->itemContent->getItem() );

		/**
		 * Gets called after the structured save of an item has been comitted,
		 * allowing for extensions to do additional storage/indexing.
		 *
		 * @since 0.1
		 *
		 * @param ItemStructuredSave $this
		 */
		wfRunHooks( 'WikibaseItemStructuredSave', array( $this ) );

		wfProfileOut( __METHOD__ );
	}

}
