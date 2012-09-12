<?php

namespace Wikibase;

/**
 * Represents a single Wikibase item.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemObject extends EntityObject implements Item {

	/**
	 * @see EntityObject::getIdPrefix()
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getIdPrefix() {
		return 'q';
	}

	/**
	 * @see Item::addSiteLink()
	 *
	 * @since 0.1
	 *
	 * @param SiteLink $link the link to the target page
	 * @param string $updateType
	 *
	 * @return array|false Returns array on success, or false on failure
	 */
	public function addSiteLink( SiteLink $link, $updateType = 'add' ) {
		$siteId = $link->getSite()->getGlobalId();

		$success =
			( $updateType === 'add' && !array_key_exists( $siteId, $this->data['links'] ) )
				|| ( $updateType === 'update' && array_key_exists( $siteId, $this->data['links'] ) )
				|| ( $updateType === 'set' );

		if ( $success ) {
			$this->data['links'][$siteId] = $link->getPage();
		}

		return $success ? $link : false;
	}

	/**
	 * @see   Item::removeSiteLink()
	 *
	 * @since 0.1
	 *
	 * @param string      $siteId the target site's id
	 * @param bool|string $pageName he target page's name (in normalized form)
	 *
	 * @return bool Success indicator
	 */
	public function removeSiteLink( $siteId, $pageName = false ) {
		if ( $pageName !== false) {
			$success = array_key_exists( $siteId, $this->data['links'] ) && $this->data['links'][$siteId] === $pageName;
		}
		else {
			$success = array_key_exists( $siteId, $this->data['links'] );
		}

		if ( $success ) {
			unset( $this->data['links'][$siteId] );
		}

		return $success;
	}

	/**
	 * @see Item::getSiteLinks()
	 *
	 * @since 0.1
	 *
	 * @return array a list of SiteLink objects
	 */
	public function getSiteLinks() {
		$links = array();

		foreach ( $this->data['links'] as $globalSiteId => $title ) {
			$links[] = SiteLink::newFromText( $globalSiteId, $title );
		}

		return $links;
	}

	/**
	 * @see Item::getSiteLink()
	 *
	 * @since 0.1
	 *
	 * @param String $siteId the id of the site to which to get the lin
	 *
	 * @return SiteLink|null the corresponding SiteLink object, or null
	 */
	public function getSiteLink( $siteId ) {
		if ( array_key_exists( $siteId, $this->data['links'] ) ) {
			return SiteLink::newFromText( $siteId, $this->data['links'][$siteId] );
		} else {
			return null;
		}
	}

	/**
	 * @see Item::isEmpty()
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		if ( !parent::isEmpty() ) {
			return false;
		}

		$fields = array( 'links' );

		foreach ( $fields as $field ) {
			if ( $this->data[$field] !== array() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Cleans the internal array structure.
	 * This consists of adding elements the code expects to be present later on
	 * and migrating or removing elements after changes to the structure are made.
	 * Should typically be called before using any of the other methods.
	 *
	 * @since 0.1
	 */
	protected function cleanStructure( $wipeExisting = false ) {
		parent::cleanStructure( $wipeExisting );

		foreach ( array( 'links' ) as $field ) {
			if (  $wipeExisting || !array_key_exists( $field, $this->data ) ) {
				$this->data[$field] = array();
			}
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Item
	 */
	public static function newFromArray( array $data ) {
		return new static( $data );
	}

	/**
	 * @since 0.1
	 *
	 * @return Item
	 */
	public static function newEmpty() {
		return self::newFromArray( array() );
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return Item::ENTITY_TYPE;
	}

	/**
	 * @see Entity::getDiff
	 *
	 * @since 0.1
	 *
	 * @param Entity $target
	 *
	 * @return ItemDiff
	 */
	public function getDiff( Entity $target ) {
		return ItemDiff::newFromItems( $this, $target );
	}

}
