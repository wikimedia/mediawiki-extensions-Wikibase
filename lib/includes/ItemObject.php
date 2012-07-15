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
	 * @see Item::getAliases()
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 *
	 * @return array
	 */
	public function getAliases( $languageCode ) {
		return array_key_exists( $languageCode, $this->data['aliases'] ) ?
			$this->data['aliases'][$languageCode] : array();
	}

	/**
	 * @see Item::getAllAliases()
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getAllAliases() {
		return $this->data['aliases'];
	}

	/**
	 * @see Item::setAliases()
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function setAliases( $languageCode, array $aliases ) {
		$this->data['aliases'][$languageCode] = $aliases;
	}

	/**
	 * @see Item::addAliases()
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function addAliases( $languageCode, array $aliases ) {
		$this->setAliases(
			$languageCode,
			array_unique( array_merge(
				$this->getAliases( $languageCode ),
				$aliases
			) )
		);
	}

	/**
	 * @see Item::removeAliases()
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function removeAliases( $languageCode, array $aliases ) {
		$this->setAliases(
			$languageCode,
			array_diff(
				$this->getAliases( $languageCode ),
				$aliases
			)
		);
	}

	/**
	 * @see Item::getDescriptions()
	 *
	 * @since 0.1
	 *
	 * @param array|null $languages note that an empty array gives descriptions for no languages whil a null pointer gives all
	 *
	 * @return array found descriptions in given languages
	 */
	public function getDescriptions( array $languages = null ) {
		return $this->getMultilangTexts( 'description', $languages );
	}

	/**
	 * @see Item::getLabels()
	 *
	 * @since 0.1
	 *
	 * @param array|null $languages note that an empty array gives labels for no languages while a null pointer gives all
	 *
	 * @return array found labels in given languages
	 */
	public function getLabels( array $languages = null ) {
		return $this->getMultilangTexts( 'label', $languages );
	}

	/**
	 * Get texts from an item with a field specifier.
	 *
	 * @since 0.1
	 *
	 * @param string $fieldKey
	 * @param array|null $languages
	 *
	 * @return array
	 */
	protected function getMultilangTexts( $fieldKey, array $languages = null ) {
		$textList = $this->data[$fieldKey];

		if ( !is_null( $languages ) ) {
			$textList = array_intersect_key( $textList, array_flip( $languages ) );
		}

		return $textList;
	}

	/**
	 * @see Item::addSiteLink()
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 * @param string $updateType
	 *
	 * @return array|false Returns array on success, or false on failure
	 */
	public function addSiteLink( $siteId, $pageName, $updateType = 'add' ) {
		$success =
			( $updateType === 'add' && !array_key_exists( $siteId, $this->data['links'] ) )
				|| ( $updateType === 'update' && array_key_exists( $siteId, $this->data['links'] ) )
				|| ( $updateType === 'set' );

		if ( $success ) {
			$this->data['links'][$siteId] = $pageName;
		}

		// TODO: we should not return this array here like this. Probably create new object to represent link.
		return $success ? array( 'site' => $siteId, 'title' => $this->data['links'][$siteId] ) : false;
	}

	/**
	 * @see Item::removeSiteLink()
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return boolean Success indicator
	 */
	public function removeSiteLink( $siteId, $pageName = false ) {
		if ( $pageName !== false) {
			$success = array_key_exists( $siteId, $this->data['links'] ) && $this->data['links'][$siteId] === $pageName;
		}
		else {
			$success = true;
		}

		if ( $success ) {
			unset( $this->data['links'][$siteId] );
		}

		return $success;
	}

	/**
	 * @see Item::getDescription()
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 *
	 * @return string|false
	 */
	public function getDescription( $langCode ) {
		return array_key_exists( $langCode, $this->data['description'] )
			? $this->data['description'][$langCode] : false;
	}

	/**
	 * @see Item::getLabel()
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 *
	 * @return string|false
	 */
	public function getLabel( $langCode ) {
		return array_key_exists( $langCode, $this->data['label'] )
			? $this->data['label'][$langCode] : false;
	}

	/**
	 * @see Item::getSiteLinks()
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getSiteLinks() {
		return $this->data['links'];
	}

	/**
	 * @see Item::isEmpty()
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		$fields = array( 'links', 'label', 'description', 'aliases' );

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
	 * TODO: this should not be public
	 *
	 * @since 0.1
	 */
	public function cleanStructure() {
		foreach ( array( 'links', 'label', 'description', 'aliases' ) as $field ) {
			if ( !array_key_exists( $field, $this->data ) ) {
				$this->data[$field] = array();
			}
		}
	}

	/**
	 * @see Item::copy()
	 *
	 * @since 0.1
	 *
	 * @return Item
	 */
	public function copy() {
		$array = array();

		foreach ( $this->toArray() as $key => $value ) {
			$array[$key] = is_object( $value ) ? clone $value : $value;
		}

		return new static( $array );
	}

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Item
	 */
	public static function newFromArray( array $data ) {
		$item = new static( $data, true );
		$item->cleanStructure();
		return $item;
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
	 * Whatever would be more appropriate during a normalization of titles during lookup.
	 * FIXME: this is extremely badly named and is probably incorrectly placed
	 *
	 * @since 0.1
	 *
	 * @param string $str
	 * @return string
	 */
	public static function normalize( $str ) {

		// ugly but works, should probably do more normalization
		// should (?) use $wgLegalTitleChars and $wgDisableTitleConversion somehow
		$str = preg_replace( '/^[\s_]+/', '', $str );
		$str = preg_replace( '/[\s_]+$/', '', $str );
		$str = preg_replace( '/[\s_]+/', ' ', $str );

		return $str;
	}

}
