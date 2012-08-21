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
		$dbw = wfGetDB( DB_MASTER );

		$dbw->begin( __METHOD__ );
		$this->saveSiteLinks();
		$this->saveMultilangFields();
		$this->saveAliases();
		$dbw->commit( __METHOD__ );

		/**
		 * Gets called after the structured save of an item has been comitted,
		 * allowing for extensions to do additional storage/indexing.
		 *
		 * @since 0.1
		 *
		 * @param ItemStructuredSave $this
		 */
		wfRunHooks( 'WikibaseItemStructuredSave', array( $this ) );
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
		return $updater->saveLinksOfItem( $this->itemContent->getItem() );
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

		$idField = array( 'tpl_item_id' => $this->itemContent->getItem()->getId() );

		$success = $dbw->delete(
			'wb_texts_per_lang',
			$idField,
			__METHOD__
		);

		$descriptions = $this->itemContent->getItem()->getDescriptions();
		$labels = $this->itemContent->getItem()->getLabels();

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

		$idField = array( 'alias_item_id' => $this->itemContent->getItem()->getId() );

		$success = $dbw->delete(
			'wb_aliases',
			$idField,
			__METHOD__
		);

		foreach ( $this->itemContent->getItem()->getAllAliases() as $languageCode => $aliases ) {
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
