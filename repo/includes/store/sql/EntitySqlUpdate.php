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
		$success = $this->saveSiteLinks();
		$success = $this->saveMultilangFields() && $success;
		$success = $this->saveAliases() && $success;
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
	protected function saveSiteLinks() {
		$updater = new SiteLinkTable( 'wb_items_per_site' );
		return $updater->saveLinksOfItem( $this->entity );
	}

	/**
	 * Saves the fields that have per-language values, such as the labels and descriptions.
	 * This info is saved in wb_texts_per_lang.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	protected function saveMultilangFields() {
		$dbw = wfGetDB( DB_MASTER );

		$idField = array( 'tpl_item_id' => $this->entity->getId() );

		$success = $dbw->delete(
			'wb_texts_per_lang',
			$idField,
			__METHOD__
		);

		$descriptions = $this->entity->getDescriptions();
		$labels = $this->entity->getLabels();

		foreach ( array_unique( array_merge( array_keys( $descriptions ), array_keys( $labels ) ) ) as $langCode ) {
			$fieldValues = array( 'tpl_language' => $langCode );

			if ( array_key_exists( $langCode, $descriptions ) ) {
				$fieldValues['tpl_description'] = $descriptions[$langCode];
			}

			if ( array_key_exists( $langCode, $labels ) ) {
				$fieldValues['tpl_label'] = $labels[$langCode];
			}

			$success = $dbw->insert(
				'wb_texts_per_lang',
				array_merge(
					$idField,
					$fieldValues
				),
				__METHOD__
			) && $success;
		}

		return $success;
	}

	/**
	 * Saves the aliases.
	 * This info is saved in wb_aliases.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	protected function saveAliases() {
		$dbw = wfGetDB( DB_MASTER );

		$idField = array( 'alias_item_id' => $this->entity->getId() );

		$success = $dbw->delete(
			'wb_aliases',
			$idField,
			__METHOD__
		);

		foreach ( $this->entity->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$success = $dbw->insert(
					'wb_aliases',
					array_merge(
						$idField,
						array(
							'alias_language' => $languageCode,
							'alias_text' => $alias,
						)
					),
					__METHOD__
				) && $success;
			}
		}

		return $success;
	}

}