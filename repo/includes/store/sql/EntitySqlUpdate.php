<?php

namespace Wikibase;

/**
 * Handler of entity updates using SQL to do additional indexing.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntitySqlUpdate implements EntityUpdateHandler {

	/**
	 * @since 0.1
	 *
	 * @var Entity
	 */
	protected $entity;

	/**
	 * @see EntityUpdateHandler::handleUpdate
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function handleUpdate( Entity $entity ) {
		$this->entity = $entity;

		$dbw = wfGetDB( DB_MASTER );

		$dbw->begin( __METHOD__ );

		if ( $entity->getType() == Item::ENTITY_TYPE ) {
			$success = $this->saveSiteLinks( $this->entity );
		}

		$success = $this->saveTerms() && $success;
		$dbw->commit( __METHOD__ );

		return $success;
	}

	/**
	 * Saves the links to other sites (for example which article on which Wikipedia corresponds to this item).
	 * This info is saved in wb_items_per_site.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	protected function saveSiteLinks( Item $item ) {
		$updater = new SiteLinkTable( 'wb_items_per_site' );
		return $updater->saveLinksOfItem( $item );
	}

	/**
	 *
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	protected function saveTerms() {
		$dbw = wfGetDB( DB_MASTER );

		$entityIdentifiers = array(
			'term_entity_id' => $this->entity->getId(),
			'term_entity_type' => $this->entity->getType()
		);

		$success = $dbw->delete(
			'wb_terms',
			$entityIdentifiers,
			__METHOD__
		);

		foreach ( $this->getEntityTerms() as $term ) {
			$success = $dbw->insert(
				'wb_terms',
				array_merge(
					$term,
					$entityIdentifiers
				),
				__METHOD__
			) && $success;
		}

		return $success;
	}

	/**
	 * Returns a list with all the terms for the entity.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	protected function getEntityTerms() {
		$terms = array();

		foreach ( $this->entity->getDescriptions() as $languageCode => $description ) {
			$terms[] = array(
				'term_language' => $languageCode,
				'term_type' => 'description',
				'term_text' => $description,
			);
		}

		foreach ( $this->entity->getLabels() as $languageCode => $label ) {
			$terms[] = array(
				'term_language' => $languageCode,
				'term_type' => 'label',
				'term_text' => $label,
			);
		}

		foreach ( $this->entity->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$terms[] = array(
					'term_language' => $languageCode,
					'term_type' => 'alias',
					'term_text' => $alias,
				);
			}
		}

		return $terms;
	}

}
