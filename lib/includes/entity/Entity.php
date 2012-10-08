<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase entity.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Values
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Entity extends Comparable, ClaimAggregate {

	/**
	 * Get an array representing the Entity.
	 * A new Entity can be constructed by passing this array to @see Entity::newFromArray
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function toArray();

	/**
	 * Returns the id of the entity or null if it is not in the datastore yet.
	 *
	 * @since 0.1
	 *
	 * @return integer|null
	 */
	public function getId();

	/**
	 * Returns a prefixed version of the entity's id or null if it is not in the datastore yet.
	 *
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getPrefixedId();

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
	 * Removes the labels in the specified languages.
	 *
	 * @since 0.1
	 *
	 * @param string|array $languages note that an empty array removes labels for no languages while a null pointer removes all
	 */
	public function removeLabel( $languages = array() );

	/**
	 * Removes the descriptions in the specified languages.
	 *
	 * @since 0.1
	 *
	 * @param string|array $languages note that an empty array removes descriptions for no languages while a null pointer removes all
	 */
	public function removeDescription( $languages = array() );

	/**
	 * Returns the descriptions of the entity in the provided languages.
	 *
	 * @since 0.1
	 *
	 * @param array|null $languages note that an empty array gives descriptions for no languages whil a null pointer gives all
	 *
	 * @return array found descriptions in given languages
	 */
	public function getDescriptions( array $languages = null );

	/**
	 * Returns the labels of the entity in the provided languages.
	 *
	 * @since 0.1
	 *
	 * @param array|null $languages note that an empty array gives labels for no languages while a null pointer gives all
	 *
	 * @return array found labels in given languages
	 */
	public function getLabels( array $languages = null );

	/**
	 * Returns the description of the entity in the language with the provided code,
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
	 * Returns the label of the entity in the language with the provided code,
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
	 * Returns if the entity is empty.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty();

	/**
	 * Returns if the entity is equal to $that.
	 *
	 * Two entities are considered equal if
	 * they have the same type, and the same content.
	 * If both entities have an ID set, then the IDs must be equal
	 * for the entities to be considered equal.
	 *
	 * @since 0.1
	 *
	 * @return boolean true of $that this equals to $this.
	 */
	//public function equals( Entity $that );

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
	 * Returns a type identifier for the entity.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Returns the name of the entity's type in a localized form.
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getLocalizedType();

	/**
	 * Creates a diff between the entity and provided target.
	 *
	 * @since 0.1
	 *
	 * @param Entity $target
	 *
	 * @return EntityDiff
	 */
	public function getDiff( Entity $target );

	/**
	 * Returns a diff representing an undo action for the changes made between
	 * the two provided entities against the entity itself.
	 *
	 * @since 0.1
	 *
	 * @param Entity $newerEntity
	 * @param Entity $olderEntity
	 *
	 * @return EntityDiff
	 */
	public function getUndoDiff( Entity $newerEntity, Entity $olderEntity );

	/**
	 * Returns a deep copy of the entity.
	 *
	 * @since 0.1
	 *
	 * @return Entity
	 */
	public function copy();

	/**
	 * Clears the structure.
	 *
	 * @since 0.1
	 */
	public function clear();

	/**
	 * Stubs the entity as far as possible.
	 * This is useful when one wants to conserve memory.
	 *
	 * @since 0.2
	 */
	public function stub();

}
