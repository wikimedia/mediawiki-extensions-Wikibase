<?php

namespace Wikibase;

interface Item extends Entity {

	/**
	 * Get an array representing the WikibaseItem as they are
	 * stored in the article table and can be passed to newFromArray.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function toArray();

	/**
	 * Returns the id of the item or null if it is not in the datastore yet.
	 *
	 * @since 0.1
	 *
	 * @return integer|null
	 */
	public function getId();

	/**
	 * Sets the ID.
	 * Should only be set to something determined by the store and not by the user (to avoid duplicate IDs).
	 *
	 * @since 0.1
	 *
	 * @param integer $id
	 */
	public function setId( $id );

	/**
	 * Sets the value for the label in a certain value.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 * @param string $value
	 * @return string
	 */
	public function setLabel( $langCode, $value );

	/**
	 * Sets the value for the description in a certain value.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 * @param string $value
	 * @return string
	 */
	public function setDescription( $langCode, $value );

	/**
	 * Remove label for a specific language in an item.
	 *
	 * @since 0.1
	 *
	 * @param string|array $languages note that an empty array removes labels for no languages while a null pointer removes all
	 */
	public function removeLabel( $languages = array() );

	/**
	 * Remove descriptions for a specific language in an item
	 *
	 * @since 0.1
	 *
	 * @param string|array $languages note that an empty array removes descriptions for no languages while a null pointer removes all
	 */
	public function removeDescription( $languages = array() );

	/**
	 * Returns the aliases for the item in the language with the specified code.
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 *
	 * @return array
	 */
	public function getAliases( $languageCode );

	/**
	 * Returns all the aliases for the item.
	 * The result is an array with language codes pointing to an array of aliases in the language they specify.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getAllAliases();

	/**
	 * Sets the aliases for the item in the language with the specified code.
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function setAliases( $languageCode, array $aliases );

	/**
	 * Add the provided aliases to the aliases list of the item in the language with the specified code.
	 * TODO: decide on how to deal with duplicates
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function addAliases( $languageCode, array $aliases );

	/**
	 * Removed the provided aliases from the aliases list of the item in the language with the specified code.
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function removeAliases( $languageCode, array $aliases );

	/**
	 * Get descriptions for an item
	 *
	 * @since 0.1
	 *
	 * @param array|null $languages note that an empty array gives descriptions for no languages whil a null pointer gives all
	 *
	 * @return array found descriptions in given languages
	 */
	public function getDescriptions( array $languages = null );

	/**
	 *  Get labels for an item
	 *
	 * @since 0.1
	 *
	 * @param array|null $languages note that an empty array gives labels for no languages while a null pointer gives all
	 *
	 * @return array found labels in given languages
	 */
	public function getLabels( array $languages = null );

	/**
	 * Adds a site link.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 * @param string $updateType
	 *
	 * @return array|false Returns array on success, or false on failure
	 */
	public function addSiteLink( $siteId, $pageName, $updateType = 'add' );

	/**
	 * Removes a site link.
	 *
	 * @since 0.1
	 *
	 * @param integer $siteId
	 * @param string $pageName
	 *
	 * @return boolean Success indicator
	 */
	public function removeSiteLink( $siteId, $pageName );

	/**
	 * Returns the description of the item in the language with the provided code,
	 * or false in cases there is none in this language.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 *
	 * @return string|false
	 */
	public function getDescription( $langCode );

	/**
	 * Returns the label of the item in the language with the provided code,
	 * or false in cases there is none in this language.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 *
	 * @return string|false
	 */
	public function getLabel( $langCode );

	/**
	 * Returns the site links in an associative array with the following format:
	 * site id (str) => page title (str)
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getSiteLinks();

	/**
	 * Returns if the item is empty.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty();

	/**
	 * Returns a deep copy of the item.
	 *
	 * @since 0.1
	 *
	 * @return Item
	 */
	public function copy();

}