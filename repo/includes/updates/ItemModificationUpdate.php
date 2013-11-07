<?php

namespace Wikibase;

/**
 * Represents an update to the structured storage for a single Item.
 * TODO: we could keep track of actual changes in a lot of cases, and so be able to do less (expensive) queries to update.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemModificationUpdate extends EntityModificationUpdate {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param ItemContent $newContent
	 * @param null|ItemContent $oldContent
	 */
	public function __construct( ItemContent $newContent, ItemContent $oldContent = null ) {
		parent::__construct( $newContent, $oldContent );
	}

	/**
	 * @see EntityDeletionUpdate::doTypeSpecificStuff
	 *
	 * @since 0.1
	 *
	 * @param Store $store
	 * @param Entity $entity
	 */
	protected function doTypeSpecificStuff( Store $store, Entity $entity ) {
		$store->newSiteLinkCache()->saveLinksOfItem( $entity );
	}

}
