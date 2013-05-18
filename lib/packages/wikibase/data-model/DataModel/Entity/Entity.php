<?php

namespace Wikibase;

use Diff\Comparer\CallbackComparer;
use Diff\Differ;
use Diff\MapPatcher;
use Diff\Patcher;
use MWException;
use Wikibase\Lib\GuidGenerator;

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
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class Entity implements \Comparable, ClaimAggregate, \Serializable {

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
	 * @var EntityId|bool|null
	 */
	protected $id = false;

	/**
	 * @since 0.3
	 *
	 * @var Claims|null
	 */
	protected $claims;

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
	 * Returns a type identifier for the entity.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public abstract function getType();

	/**
	 * Get an array representing the Entity.
	 * A new Entity can be constructed by passing this array to @see Entity::newFromArray
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function toArray() {
		$this->stub();
		return $this->data;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function serialize() {
		$data = $this->toArray();

		// Add an identifier for the serialization version so we can switch behavior in
		// the unserializer to avoid breaking compatibility after certain changes.
		$data['v'] = 1;

		return \FormatJson::encode( $data );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.3
	 *
	 * @param string $value
	 *
	 * @return Entity
	 * @throws MWException
	 */
	public function unserialize( $value ) {
		$unserialized = \FormatJson::decode( $value, true );

		if ( is_array( $unserialized ) && array_key_exists( 'v', $unserialized ) ) {
			unset( $unserialized['v'] );

			return $this->__construct( $unserialized );
		}

		throw new MWException( 'Invalid serialization passed to Entity unserializer' );
	}

	/**
	 * @since 0.3
	 *
	 * @deprecated Do not rely on this method being present, it will be removed soonish.
	 */
	public function __wakeup() {
		// Compatibility with 0.1 and 0.2 serializations.
		if ( is_int( $this->id ) ) {
			$this->id = new EntityId( $this->getType(), $this->id );
		}

		// Compatibility with 0.2 and 0.3 ItemObjects.
		// (statements key got renamed to claims)
		if ( array_key_exists( 'statements', $this->data ) ) {
			$this->data['claims'] = $this->data['statements'];
			unset( $this->data['statements'] );
		}
	}

	/**
	 * Returns the id of the entity or null if it is not in the datastore yet.
	 *
	 * @since 0.1 return type changed in 0.3
	 *
	 * @return EntityId|null
	 */
	public function getId() {
		if ( $this->id === false ) {
			if ( array_key_exists( 'entity', $this->data ) ) {
				$this->id = EntityId::newFromPrefixedId( $this->data['entity'] );
			}
			else {
				$this->id = null;
			}
		}

		return $this->id;
	}

	/**
	 * Returns a prefixed version of the entity's id or null if it is not in the datastore yet.
	 *
	 * @since 0.2
	 * @deprecated since 0.4
	 *
	 * @return string|null
	 */
	public function getPrefixedId() {
		return $this->getId() === null ? null : $this->getId()->getPrefixedId();
	}

	/**
	 * Sets the ID.
	 * Should only be set to something determined by the store and not by the user (to avoid duplicate IDs).
	 *
	 * @since 0.1
	 *
	 * @param EntityId|integer $id Can be EntityId since 0.3
	 *
	 * @throws MWException
	 */
	public function setId( $id ) {
		if ( $id instanceof EntityId ) {
			if ( $id->getEntityType() !== $this->getType() ) {
				throw new MWException( 'Attempt to set an EntityId with mismatching entity type' );
			}

			$this->id = $id;
		}
		else if ( is_integer( $id ) ) {
			$this->id = new EntityId( $this->getType(), $id );
		}
		else {
			throw new MWException( __METHOD__ . ' only accepts EntityId and integer' );
		}
	}

	/**
	 * Sets the value for the label in a certain value.
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
	 * Sets the value for the description in a certain value.
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
	 * Removes the labels in the specified languages.
	 *
	 * @since 0.1
	 *
	 * @param string|array $languages note that an empty array removes labels for no languages while a null pointer removes all
	 */
	public function removeLabel( $languages = array() ) {
		$this->removeMultilangTexts( 'label', (array)$languages );
	}

	/**
	 * Removes the descriptions in the specified languages.
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
	 * Returns the aliases for the item in the language with the specified code.
	 * TODO: decide on how to deal with duplicates, it is assumed all duplicates should be removed
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 *
	 * @return array
	 */
	public function getAliases( $languageCode ) {
		return array_key_exists( $languageCode, $this->data['aliases'] ) ?
			array_unique( $this->data['aliases'][$languageCode] ) : array();
	}

	/**
	 * Returns all the aliases for the item.
	 * The result is an array with language codes pointing to an array of aliases in the language they specify.
	 * TODO: decide on how to deal with duplicates, it is assumed all duplicates should be removed
	 *
	 * @since 0.1
	 *
	 * @param array|null $languages
	 *
	 * @return array
	 */
	public function getAllAliases( array $languages = null ) {
		$textList = $this->data['aliases'];

		if ( !is_null( $languages ) ) {
			$textList = array_intersect_key( $textList, array_flip( $languages ) );
		}

		$textList = array_map(
			'array_unique',
			$textList
		);

		return $textList;
	}

	/**
	 * Sets the aliases for the item in the language with the specified code.
	 * TODO: decide on how to deal with duplicates, it is assumed all duplicates should be removed
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function setAliases( $languageCode, array $aliases ) {
		$this->data['aliases'][$languageCode] = array_unique( $aliases );
	}

	/**
	 * Add the provided aliases to the aliases list of the item in the language with the specified code.
	 * TODO: decide on how to deal with duplicates, it is assumed all duplicates should be removed
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
	 * Removed the provided aliases from the aliases list of the item in the language with the specified code.
	 * TODO: decide on how to deal with duplicates, it is assumed all duplicates should be removed
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
	 * Returns the descriptions of the entity in the provided languages.
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
	 * Returns the labels of the entity in the provided languages.
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
	 * Returns the description of the entity in the language with the provided code,
	 * or false in cases there is none in this language.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 *
	 * @return string|bool
	 */
	public function getDescription( $langCode ) {
		return array_key_exists( $langCode, $this->data['description'] )
			? $this->data['description'][$langCode] : false;
	}

	/**
	 * Returns the label of the entity in the language with the provided code,
	 * or false in cases there is none in this language.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 *
	 * @return string|bool
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
	 * @param bool|bool $wipeExisting Unconditionally wipe out all data
	 *
	 * @since 0.1
	 */
	protected function cleanStructure( $wipeExisting = false ) {
		foreach ( array( 'label', 'description', 'aliases', 'claims' ) as $field ) {
			if ( $wipeExisting || !array_key_exists( $field, $this->data ) ) {
				$this->data[$field] = array();
			}
		}
	}

	/**
	 * Replaces the currently set labels with the provided ones.
	 * The labels are provided as an associative array where the keys are
	 * language codes pointing to the label in that language.
	 *
	 * @since 0.4
	 *
	 * @param string[] $labels
	 */
	public function setLabels( array $labels ) {
		$this->data['label'] = $labels;
	}

	/**
	 * Replaces the currently set descriptions with the provided ones.
	 * The descriptions are provided as an associative array where the keys are
	 * language codes pointing to the description in that language.
	 *
	 * @since 0.4
	 *
	 * @param string[] $descriptions
	 */
	public function setDescriptions( array $descriptions ) {
		$this->data['description'] = $descriptions;
	}

	/**
	 * Replaces the currently set aliases with the provided ones.
	 * The aliases are provided as an associative array where the keys are
	 * language codes pointing to an array value that holds the aliases
	 * in that language.
	 *
	 * @since 0.4
	 *
	 * @param array[] $aliasLists
	 */
	public function setAllAliases( array $aliasLists ) {
		$this->data['aliases'] = $aliasLists;
	}

	/**
	 * TODO: change to take Claim[]
	 *
	 * @since 0.4
	 *
	 * @param Claims $claims
	 */
	public function setClaims( Claims $claims ) {
		$this->claims = iterator_to_array( $claims );
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
	 * Returns if the entity is empty.
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

		if ( $this->hasClaims() ) {
			return false;
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * @see Comparable::equals
	 *
	 * Two entities are considered equal if they are of the same
	 * type and have the same value. The value does not include
	 * the id, so entities with the same value but different id
	 * are considered equal.
	 *
	 * @since 0.1
	 *
	 * @param mixed $that
	 *
	 * @return boolean
	 */
	public function equals( $that ) {
		if ( $that === $this ) {
			return true;
		}

		if ( !is_object( $that ) || ( get_class( $this ) !== get_class( $that ) ) ) {
			return false;
		}

		wfProfileIn( __METHOD__ );

		//@todo: ignore the order of aliases
		$thisData = $this->toArray();
		$thatData = $that->toArray();

		$comparer = new ObjectComparer();
		$equals = $comparer->dataEquals( $thisData, $thatData, array( 'entity' ) );

		wfProfileOut( __METHOD__ );
		return $equals;
	}

	/**
	 * Returns a deep copy of the entity.
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

	/**
	 * Stubs the entity as far as possible.
	 * This is useful when one wants to conserve memory.
	 *
	 * @since 0.2
	 */
	public function stub() {
		if ( is_null( $this->getId() ) ) {
			if ( array_key_exists( 'entity', $this->data ) ) {
				unset( $this->data['entity'] );
			}
		}
		else {
			$this->data['entity'] = $this->getPrefixedId();
		}

		$this->data['claims'] = $this->getStubbedClaims( empty( $this->data['claims'] ) ? array() : $this->data['claims'] );
	}

	/**
	 * Returns all the labels, descriptions and aliases as Term objects.
	 *
	 * @since 0.2
	 *
	 * @return Term[]
	 */
	public function getTerms() {
		$terms = array();

		foreach ( $this->getDescriptions() as $languageCode => $description ) {
			$term = new Term();

			$term->setLanguage( $languageCode );
			$term->setType( Term::TYPE_DESCRIPTION );
			$term->setText( $description );

			$terms[] = $term;
		}

		foreach ( $this->getLabels() as $languageCode => $label ) {
			$term = new Term();

			$term->setLanguage( $languageCode );
			$term->setType( Term::TYPE_LABEL );
			$term->setText( $label );

			$terms[] = $term;
		}

		foreach ( $this->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$term = new Term();

				$term->setLanguage( $languageCode );
				$term->setType( Term::TYPE_ALIAS );
				$term->setText( $alias );

				$terms[] = $term;
			}
		}

		return $terms;
	}

	/**
	 * @see ClaimListAccess::addClaim
	 *
	 * @since 0.3
	 *
	 * @param Claim $claim
	 */
	public function addClaim( Claim $claim ) {
		$this->unstubClaims();
		$this->claims[] = $claim;
		// TODO: ensure guid is valid for entity
	}

	/**
	 * @see ClaimAggregate::getClaims
	 *
	 * @since 0.3
	 *
	 * @return Claim[]
	 */
	public function getClaims() {
		$this->unstubClaims();
		return $this->claims;
	}

	/**
	 * Unsturbs the statements from the JSON into the $statements field
	 * if this field is not already set.
	 *
	 * @since 0.3
	 *
	 * @return Claims
	 */
	protected function unstubClaims() {
		if ( $this->claims === null ) {
			$this->claims = array();

			foreach ( $this->data['claims'] as $claimSerialization ) {
				$this->claims[] = Claim::newFromArray( $claimSerialization );
			}
		}
	}

	/**
	 * Takes the claims element of the $data array of an item and writes the claims to it as stubs.
	 *
	 * @since 0.3
	 *
	 * @param Claim[] $claims
	 *
	 * @return array
	 */
	protected function getStubbedClaims( array $claims ) {
		if ( $this->claims !== null ) {
			$claims = array();

			/**
			 * @var Claim $claim
			 */
			foreach ( $this->claims as $claim ) {
				$claims[] = $claim->toArray();
			}
		}

		return $claims;
	}

	/**
	 * Convenience function to check if the entity contains any claims.
	 *
	 * On top of being a convenience function, this implementation allows for doing
	 * the check without forcing an unstub in contrast to count( $this->getClaims() ).
	 *
	 * @since 0.2
	 *
	 * @return boolean
	 */
	public function hasClaims() {
		if ( $this->claims === null ) {
			return $this->data['claims'] !== array();
		}
		else {
			return count( $this->claims ) > 0;
		}
	}

	/**
	 * Returns a new Claim with the provided Snak as main snak.
	 *
	 * @since 0.3
	 *
	 * @param Snak $mainSnak
	 * @param GuidGenerator|null $guidGenerator
	 *
	 * @return Claim
	 */
	public final function newClaim( Snak $mainSnak, GuidGenerator $guidGenerator = null ) {
		$claim = $this->newClaimBase( $mainSnak );

		if ( $guidGenerator === null ) {
			$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $this->getId() );
		}

		$claim->setGuid( $guidGenerator->newGuid() );

		return $claim;
	}

	/**
	 * @since 0.3
	 *
	 * @param Snak $mainSnak
	 *
	 * @return Claim
	 */
	protected function newClaimBase( Snak $mainSnak ) {
		return new Claim( $mainSnak );
	}

	/**
	 * Returns an EntityDiff between $this and the provided Entity.
	 *
	 * @since 0.1
	 *
	 * @param Entity $target
	 * @param Differ|null $differ Since 0.4
	 *
	 * @return EntityDiff
	 * @throws MWException
	 */
	public final function getDiff( Entity $target, Differ $differ = null ) {
		if ( $this->getType() !== $target->getType() ) {
			throw new MWException( 'Can only diff between entities of the same type' );
		}

		if ( $differ === null ) {
			$differ = new \Diff\MapDiffer( true );
		}

		$oldEntity = $this->entityToDiffArray( $this );
		$newEntity = $this->entityToDiffArray( $target );

		$diffOps = $differ->doDiff( $oldEntity, $newEntity );

		$claims = new Claims( $this->getClaims() );
		$diffOps['claim'] = $claims->getDiff( new Claims( $target->getClaims() ) );

		return EntityDiff::newForType( $this->getType(), $diffOps );
	}

	/**
	 * Create and returns an array based serialization suitable for EntityDiff.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function entityToDiffArray( Entity $entity ) {
		$array = array();

		$array['aliases'] = $entity->getAllAliases();
		$array['label'] = $entity->getLabels();
		$array['description'] = $entity->getDescriptions();

		return $array;
	}

	/**
	 * Apply an EntityDiff to the entity.
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $patch
	 */
	public final function patch( EntityDiff $patch ) {
		$patcher = new MapPatcher();

		$this->setLabels( $patcher->patch( $this->getLabels(), $patch->getLabelsDiff() ) );
		$this->setDescriptions( $patcher->patch( $this->getDescriptions(), $patch->getDescriptionsDiff() ) );
		$this->setAllAliases( $patcher->patch( $this->getAllAliases(), $patch->getAliasesDiff() ) );

		$this->patchSpecificFields( $patch, $patcher );

		$patcher->setValueComparer( new CallbackComparer(
			function( Claim $firstClaim, Claim $secondClaim ) {
				return $firstClaim->getHash() === $secondClaim->getHash();
			}
		)  );

		$claims = array();

		foreach ( $this->getClaims() as $claim ) {
			$claims[$claim->getGuid()] = $claim;
		}

		$claims = $patcher->patch( $claims, $patch->getClaimsDiff() );

		$this->setClaims( new Claims( $claims ) );
	}

	/**
	 * Patch fields specific to the type of entity.
	 * @see patch
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $patch
	 * @param Patcher $patcher
	 */
	protected function patchSpecificFields( EntityDiff $patch, Patcher $patcher ) {
		// No-op, meant to be overridden in deriving classes to add specific behavior
	}

	/**
	 * Parses the claim GUID and returns the prefixed entity ID it contains.
	 *
	 * @since 0.3
	 * @deprecated since 0.4
	 *
	 * @param string $claimKey
	 *
	 * @return string
	 * @throws MWException
	 */
	public static function getIdFromClaimGuid( $claimKey ) {
		$keyParts = explode( '$', $claimKey );

		if ( count( $keyParts ) !== 2 ) {
			throw new MWException( 'A claim key should have a single $ in it' );
		}

		return $keyParts[0];
	}

}
