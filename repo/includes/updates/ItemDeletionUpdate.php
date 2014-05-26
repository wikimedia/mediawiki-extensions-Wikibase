<?php

namespace Wikibase;

use Title;

/**
 * Deletion update to handle deletion of Wikibase items.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDeletionUpdate extends EntityDeletionUpdate {

	/**
	 * @since 0.1
	 *
	 * @param ItemContent $newContent
	 * @param Title $title
	 */
	public function __construct( ItemContent $newContent, Title $title ) {
		parent::__construct( $newContent, $title );
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
		$store->newSiteLinkCache()->deleteLinksOfItem( $entity->getId() );
	}

}
