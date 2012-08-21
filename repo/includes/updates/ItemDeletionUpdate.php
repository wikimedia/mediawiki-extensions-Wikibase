<?php

namespace Wikibase;

/**
 * Deletion update to handle deletion of Wikibase items.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDeletionUpdate extends \DataUpdate {

	/**
	 * @var ItemContent
	 */
	protected $itemContent;

	/**
	 * @param ItemContent $itemContent
	 */
	public function __construct( ItemContent $itemContent ) {
		$this->itemContent = $itemContent;
	}

	/**
	 * Returns the ItemContent that's being deleted.
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public function getItemContent() {
		return $this->itemContent;
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 */
	public function doUpdate() {
		StoreFactory::getStore()->newEntityUpdateHandler()->handleUpdate( $this->itemContent->getItem() );

		/**
		 * Gets called after the deletion of an item has been comitted,
		 * allowing for extensions to do additional cleanup.
		 *
		 * @since 0.1
		 *
		 * @param ItemStructuredSave $this
		 */
		wfRunHooks( 'WikibaseItemDeletionUpdate', array( $this ) );
	}

}