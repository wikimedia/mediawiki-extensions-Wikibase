<?php

namespace Wikibase;

/**
 * Handler of entity deletions using SQL to do additional indexing.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntitySqlDeletion implements EntityDeletionHandler {

	/**
	 * @see EntityDeletionHandler::handleDeletion
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function handleDeletion( Entity $entity ) {
		// TODO: split entity/item
		$dbw = wfGetDB( DB_MASTER );

		$dbw->begin( __METHOD__ );

		$updater = new SiteLinkTable( 'wb_items_per_site' );
		$updater->deleteLinksOfItem( $entity );

		$dbw->delete(
			'wb_terms',
			array(
				'term_entity_id' => $entity->getId(),
				'term_entity_type' => $entity->getType()
			),
			__METHOD__
		);

		$dbw->commit( __METHOD__ );

		// TODO: we need to handle failures in this thing.
		// If the update breaks for some reason, and stuff remains for a deleted item, how do we get rid of it?
		// Sitelinks will cause problems since they will needlessly prohibit other items from being linked to their targets.

		return true; // TODO
	}

}