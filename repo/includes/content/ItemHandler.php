<?php

namespace Wikibase;

use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemHandler extends EntityHandler {

	/**
	 * @see EntityHandler::getContentClass
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return '\Wikibase\ItemContent';
	}

	public function __construct() {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
			'history' => '\Wikibase\HistoryItemAction',
			'view' => '\Wikibase\ViewItemAction',
			'edit' => '\Wikibase\EditItemAction',
			'submit' => '\Wikibase\SubmitItemAction',
		);
	}

	/**
	 * @param string $blob
	 * @param null|string $format
	 *
	 * @return ItemContent
	 */
	public function unserializeContent( $blob, $format = null ) {
		$entity = EntityFactory::singleton()->newFromBlob( Item::ENTITY_TYPE, $blob, $format );
		return ItemContent::newFromItem( $entity );
	}

	/**
	 * @see ContentHandler::getDiffEngineClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getDiffEngineClass() {
		return '\Wikibase\ItemContentDiffView';
	}

	/**
	 * Get the item corresponding to the provided site and title pair,
	 * or null if there is no such item.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return ItemContent|null
	 */
	public function getContentFromSiteLink( $siteId, $pageName ) {
		$id = $this->getIdForSiteLink( $siteId, $pageName );

		if ( $id === false ) {
			return null;
		}

		$entityId = ItemId::newFromNumber( $id );
		return WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getFromId( $entityId );
	}

	/**
	 * Get the item id for a site and page pair.
	 * Returns false when there is no such pair.
	 *
	 * @since 0.1
	 *
	 * @param integer $siteId
	 * @param string $pageName
	 *
	 * @return false|integer
	 */
	public function getIdForSiteLink( $siteId, $pageName ) {
		return StoreFactory::getStore()->newSiteLinkCache()->getItemIdForLink( $siteId, $pageName );
	}

	/**
	 * Get the title of the item corresponding to the provided site and title pair,
	 * or null if there is no such item.
	 *
	 * @since 0.3
	 *
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return Title|null
	 */
	public function getTitleFromSiteLink( $siteId, $pageName ) {
		$id = $this->getIdForSiteLink( $siteId, $pageName );

		if ( $id === false ) {
			return null;
		}

		$entityId = ItemId::newFromNumber( $id );
		return WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getTitleForId( $entityId );
	}

	/**
	 * @see EntityHandler::getSpecialPageForCreation
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getSpecialPageForCreation() {
		return 'NewItem';
	}

	/**
	 * Returns Item::ENTITY_TYPE
	 *
	 * @return string
	 */
	public function getEntityType() {
		return Item::ENTITY_TYPE;
	}
}
