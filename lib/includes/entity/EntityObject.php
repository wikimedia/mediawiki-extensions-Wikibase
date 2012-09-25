<?php

namespace Wikibase;

/**
 * Represents a single Wikibase entity.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityObject implements Entity {

	/**
	 * Maps entity types to objects representing the corresponding entity.
	 *
	 * @since 0.1
	 *
	 * @var array
	 */
	public static $typeMap = array(
		Item::ENTITY_TYPE => '\Wikibase\ItemObject',
		Property::ENTITY_TYPE => '\Wikibase\PropertyObject',
		Query::ENTITY_TYPE => '\Wikibase\QueryObject'
	);

	/**
	 * @since 0.1
	 * @var array
	 */
	protected $data;

	/**
	 * Id of the item (the 42 in q42 used as page name and in exports).
	 * Integer when set. False when not initialized. Null when the item is new and unsaved.
	 *
	 * @since 0.1
	 * @var integer|false|null
	 */
	protected $id = false;

	/**
	 * Constructor.
	 * Do not use to construct new stuff from outside of this class, use the static newFoobar methods.
	 * In other words: treat as protected (which it was, but now cannot be since we derive from Content).
	 * @protected
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 */
	public function __construct( array $data ) {
		$this->data = $data;
		$this->cleanStructure();
	}

	/**
	 * @see Entity::toArray
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function toArray() {
		$data = $this->data;

		if ( is_null( $this->getId() ) ) {
			if ( array_key_exists( 'entity', $data ) ) {
				unset( $data['entity'] );
			}
		}
		else {
			$data['entity'] = $this->getIdPrefix() . $this->getId();
		}

		return $data;
	}

	/**
	 * Returns a unique id prefix for the type of entity.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getIdPrefix() {
		return '';
	}

	/**
	 * @see Entity::getId()
	 *
	 * @since 0.1
	 *
	 * @return integer|null
	 */
	public function getId() {
		if ( $this->id === false ) {
			if ( array_key_exists( 'entity', $this->data ) ) {
				$this->id = (int)substr( $this->data['entity'], strlen( $this->getIdPrefix() ) );
			}
			else {
				$this->id = null;
			}
		}

		return $this->id;
	}

	/**
	 * @see Entity::setId()
	 *
	 * @since 0.1
	 *
	 * @param integer $id
	 */
	public function setId( $id ) {
		$this->id = $id;
	}

	/**
	 * @see Entity::setLabel()
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 * @param string $value
	 * @return string
	 */
	public function setLabel( $langCode, $value ) {
		// TODO: normalize value
		$this->data['label'][$langCode] = $value;
		return $value;
	}

	/**
	 * @see Entity::setDescription()
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 * @param string $value
	 * @return string
	 */
	public function setDescription( $langCode, $value ) {
		// TODO: normalize value
		$this->data['description'][$langCode] = $value;
		return $value;
	}

	/**
	 * @see Entity::removeLabel()
	 *
	 * @since 0.1
	 *
	 * @param string|array $languages note that an empty array removes labels for no languages while a null pointer removes all
	 */
	public function removeLabel( $languages = array() ) {
		$this->removeMultilangTexts( 'label', (array)$languages );
	}

	/**
	 * @see Entity::removeDescription()
	 *
	 * @since 0.1
	 *
	 * @param string|array $languages note that an empty array removes descriptions for no languages while a null pointer removes all
	 */
	public function removeDescription( $languages = array() ) {
		$this->removeMultilangTexts( 'description', (array)$languages );
	}

	/**
	 * Remove the value with a field specifier
	 *
	 * @since 0.1
	 *
	 * @param string $fieldKey
	 * @param array|null $languages
	 */
	protected function removeMultilangTexts( $fieldKey, array $languages = null ) {
		if ( is_null( $languages ) ) {
			$this->data[$fieldKey] = array();
		}
		else {
			foreach ( $languages as $lang ) {
				unset( $this->data[$fieldKey][$lang] );
			}
		}
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
	 * @param $languages
	 * @return array
	 */
	public function getAllAliases( array $languages = null ) {
		$textList = $this->data['aliases'];

		if ( !is_null( $languages ) ) {
			$textList = array_intersect_key( $textList, array_flip( $languages ) );
		}

		return $textList;
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
	 * Cleans the internal array structure.
	 * This consists of adding elements the code expects to be present later on
	 * and migrating or removing elements after changes to the structure are made.
	 * Should typically be called before using any of the other methods.
	 *
	 * @param bool|false $wipeExisting Unconditionally wipe out all data
	 *
	 * @since 0.1
	 */
	protected function cleanStructure( $wipeExisting = false ) {
		foreach ( array( 'label', 'description', 'aliases' ) as $field ) {
			if ( $wipeExisting || !array_key_exists( $field, $this->data ) ) {
				$this->data[$field] = array();
			}
		}
	}

	/**
	 * Clears the structure.
	 *
	 * @since 0.1
	 */
	public function clear() {
		$this->cleanStructure( true );
	}

	/**
	 * @see Entity::isEmpty()
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		wfProfileIn( __METHOD__ );

		$fields = array( 'label', 'description', 'aliases' );

		foreach ( $fields as $field ) {
			if ( $this->data[$field] !== array() ) {
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * @see Comparable::equals
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
	public function equals( $that ) {
		if ( $that === $this ) {
			return true;
		}

		if ( get_class( $this ) !== get_class( $that ) ) {
			return false;
		}

		wfProfileIn( __METHOD__ );

		/**
		 * @var Entity $that
		 */
		$thisId = $this->getId();
		$thatId = $that->getId();

		if ( $thisId !== null && $thatId !== null ) {
			if ( $thisId !== $thatId ) {
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		//@todo: ignore the order of aliases
		$thisData = $this->toArray();
		$thatData = $that->toArray();

		$comparer = new ObjectComparer();
		$equals = $comparer->dataEquals( $thisData, $thatData, array( 'entity' ) );

		wfProfileOut( __METHOD__ );
		return $equals;
	}

	/**
	 * @see Entity::getUndoDiff
	 *
	 * @since 0.1
	 *
	 * @param Entity $newerEntity
	 * @param Entity $olderEntity
	 *
	 * @return EntityDiff
	 * @throws \MWException
	 */
	public function getUndoDiff( Entity $newerEntity, Entity $olderEntity ) {
		if ( $newerEntity->getType() !== $this->getType() || $olderEntity->getType() !== $this->getType() ) {
			throw new \MWException( 'Entities passed to getUndoDiff must have the same type as the entity object.' );
		}

		wfProfileIn( __METHOD__ );

		// FIXME: awareness of internal entity structure in diff code where it can be avoided (and is already in EntityDiff)
		$diff = $newerEntity->getDiff( $olderEntity )->getApplicableDiff( $this->toArray() );

		wfProfileOut( __METHOD__ );
		return $diff;
	}

	/**
	 * @see Entity::copy()
	 *
	 * @since 0.1
	 *
	 * @return Entity
	 */
	public function copy() {
		wfProfileIn( __METHOD__ );

		$array = array();

		foreach ( $this->toArray() as $key => $value ) {
			$array[$key] = is_object( $value ) ? clone $value : $value;
		}

		$copy = new static( $array );

		wfProfileOut( __METHOD__ );
		return $copy;
	}

}