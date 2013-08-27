<?php

namespace Wikibase;

use RequestContext;
use Content;
use MWException;
use Title;
use User;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

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
		$data = $this->unserializedData( $blob, $format );

		if ( !empty( $data['redirect'] ) ) {
			$target = new EntityId( $data['redirect']['type'], $data['redirect']['id'] );
			return ItemContent::newFromRedirect( $target );
		}

		$entity = EntityFactory::singleton()->newFromArray( $this->getEntityType(), $data );
		return ItemContent::newFromItem( $entity );
	}

	/**
	 * @param \Content $content
	 * @param null     $format
	 *
	 * @return string
	 */
	public function serializeContent( \Content $content, $format = null ) {
		/* @var ItemContent $content */
		if ( $content->isRedirect() ) {
			$target = $content->getRedirectTargetId();
			$data = array(
				'entity' => $content->getEntity()->getId(),
				'redirect' => array(
					'type' => $target->getEntityType(),
					'id' => $target->getNumericId(),
				)
			);

			return $this->serializeData( $data, $format );
		}

		return parent::serializeContent( $content, $format );
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

		$entityId = new EntityId( Item::ENTITY_TYPE, $id );
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

		$entityId = new EntityId( Item::ENTITY_TYPE, $id );
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

	/**
	 * Creates a new ItemContent object that acts as a redirect to the given page.
	 *
	 * @see ContentHandler::makeRedirectContent
	 *
	 * @param Title $destination the page to redirect to. Must be an existing wikibase Item.
	 *
	 * @throws \MWException
	 * @return ItemContent
	 */
	public function makeRedirectContent( Title $destination, $text = '' ) {
		$ns = $destination->getNamespace();

		if ( $ns !== $this->getEntityNamespace() ) {
			throw new MWException( "Items can only redirect to other items." );
		}

		if ( !$destination->exists() ) {
			throw new MWException( "Items can only redirect to existing items." );
		}

		//TODO: mapping from titles to ids should be encapsulated elsewhere, e.g. in EntityTitleLookup
		$id = EntityId::newFromPrefixedId( $destination->getText() );

		if ( !$id ) {
			throw new MWException( "Page title is not a valid item ID: " . $destination->getText() );
		}

		if ( $id->getEntityType() !== Item::ENTITY_TYPE  ) {
			throw new MWException( "Page title is not a valid item ID: " . $destination->getText() );
		}

		return ItemContent::newFromRedirect( $id );
	}
}

