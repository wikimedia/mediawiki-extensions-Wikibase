<?php

namespace Wikibase;
use User, Title, WikiPage, Content, RequestContext;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemHandler extends EntityHandler {

	/**
	 * Returns an instance of the ItemHandler.
	 *
	 * @since 0.1
	 *
	 * @return ItemHandler
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * @see ContentHandler::makeEmptyContent
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public function makeEmptyContent() {
		return ItemContent::newEmpty();
	}

	public function __construct() {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
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
		return ItemContent::newFromArray( $this->unserializedData( $blob, $format ) );
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
	 * Get the item corresponding to the provided site and title pair, or null if there is no such item.
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
		return $id === false ? null : self::getFromId( $id );
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
	 * @see EntityHandler::getEntityPrefix
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getEntityPrefix() {
		return Settings::get( 'itemPrefix' );
	}

	/**
	 * @see EntityHandler::getEntityNamespace
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getEntityNamespace() {
		return WB_NS_DATA;
	}

	/**
	 * Get the item corresponding to the provided site and title pair, or null if there is no such item.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return ItemContent|null
	 */
	public function getFromSiteLink( $siteId, $pageName ) {
		$id = $this->getIdForSiteLink( $siteId, $pageName );
		return $id === false ? null : $this->getFromId( $id );
	}

}

