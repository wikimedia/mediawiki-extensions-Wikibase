<?php

namespace Wikibase\DataModel\Entity;

use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\EntityDiffer;
use Wikibase\DataModel\Entity\Diff\EntityPatcher;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * Represents a single Wikibase entity.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#Values
 *
 * @deprecated since 1.0 - do not type hint against Entity. See
 * https://lists.wikimedia.org/pipermail/wikidata-tech/2014-June/000489.html
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class Entity implements \Comparable, FingerprintProvider, EntityDocument {

	/**
	 * @var EntityId|null
	 */
	protected $id;

	/**
	 * @var Fingerprint
	 */
	protected $fingerprint;

	/**
	 * Returns the id of the entity or null if it does not have one.
	 *
	 * @since 0.1 return type changed in 0.3
	 *
	 * @return EntityId|null
	 */
	public function getId() {
		return $this->id;
	}

	public abstract function setId( $id );

	/**
	 * Sets the value for the label in a certain value.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @return string
	 */
	public function setLabel( $languageCode, $value ) {
		$this->fingerprint->getLabels()->setTerm( new Term( $languageCode, $value ) );
		return $value;
	}

	/**
	 * Sets the value for the description in a certain value.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @return string
	 */
	public function setDescription( $languageCode, $value ) {
		$this->fingerprint->getDescriptions()->setTerm( new Term( $languageCode, $value ) );
		return $value;
	}

	/**
	 * Removes the labels in the specified languages.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 */
	public function removeLabel( $languageCode ) {
		$this->fingerprint->getLabels()->removeByLanguage( $languageCode );
	}

	/**
	 * Removes the descriptions in the specified languages.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 */
	public function removeDescription( $languageCode ) {
		$this->fingerprint->getDescriptions()->removeByLanguage( $languageCode );
	}

	/**
	 * Returns the aliases for the item in the language with the specified code.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 *
	 * @return string[]
	 */
	public function getAliases( $languageCode ) {
		$aliases = $this->fingerprint->getAliasGroups();

		if ( $aliases->hasGroupForLanguage( $languageCode ) ) {
			return $aliases->getByLanguage( $languageCode )->getAliases();
		}

		return array();
	}

	/**
	 * Returns all the aliases for the item.
	 * The result is an array with language codes pointing to an array of aliases in the language they specify.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string[]|null $languageCodes
	 *
	 * @return array[]
	 */
	public function getAllAliases( array $languageCodes = null ) {
		$aliases = $this->fingerprint->getAliasGroups();

		$textLists = array();

		/**
		 * @var AliasGroup $aliasGroup
		 */
		foreach ( $aliases as $languageCode => $aliasGroup ) {
			if ( $languageCodes === null || in_array( $languageCode, $languageCodes ) ) {
				$textLists[$languageCode] = $aliasGroup->getAliases();
			}
		}

		return $textLists;
	}

	/**
	 * Sets the aliases for the item in the language with the specified code.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 * @param string[] $aliases
	 */
	public function setAliases( $languageCode, array $aliases ) {
		$this->fingerprint->getAliasGroups()->setGroup( new AliasGroup( $languageCode, $aliases ) );
	}

	/**
	 * Add the provided aliases to the aliases list of the item in the language with the specified code.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 * @param string[] $aliases
	 */
	public function addAliases( $languageCode, array $aliases ) {
		$this->setAliases(
			$languageCode,
			array_merge(
				$this->getAliases( $languageCode ),
				$aliases
			)
		);
	}

	/**
	 * Removed the provided aliases from the aliases list of the item in the language with the specified code.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 * @param string[] $aliases
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
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string[]|null $languageCodes Note that an empty array gives descriptions for no languages while a null pointer gives all
	 *
	 * @return string[] Found descriptions in given languages
	 */
	public function getDescriptions( array $languageCodes = null ) {
		return $this->getMultilangTexts( 'description', $languageCodes );
	}

	/**
	 * Returns the labels of the entity in the provided languages.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string[]|null $languageCodes Note that an empty array gives labels for no languages while a null pointer gives all
	 *
	 * @return string[] Found labels in given languages
	 */
	public function getLabels( array $languageCodes = null ) {
		return $this->getMultilangTexts( 'label', $languageCodes );
	}

	/**
	 * Returns the description of the entity in the language with the provided code,
	 * or false in cases there is none in this language.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 *
	 * @return string|bool
	 */
	public function getDescription( $languageCode ) {
		if ( !$this->fingerprint->getDescriptions()->hasTermForLanguage( $languageCode ) ) {
			return false;
		}

		return $this->fingerprint->getDescriptions()->getByLanguage( $languageCode )->getText();
	}

	/**
	 * Returns the label of the entity in the language with the provided code,
	 * or false in cases there is none in this language.
	 *
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string $languageCode
	 *
	 * @return string|bool
	 */
	public function getLabel( $languageCode ) {
		if ( !$this->fingerprint->getLabels()->hasTermForLanguage( $languageCode ) ) {
			return false;
		}

		return $this->fingerprint->getLabels()->getByLanguage( $languageCode )->getText();
	}

	/**
	 * Get texts from an item with a field specifier.
	 *
	 * @since 0.1
	 * @deprecated
	 *
	 * @param string $fieldKey
	 * @param string[]|null $languageCodes
	 *
	 * @return string[]
	 */
	private function getMultilangTexts( $fieldKey, array $languageCodes = null ) {
		if ( $fieldKey === 'label' ) {
			$textList = $this->fingerprint->getLabels()->toTextArray();
		}
		else {
			$textList = $this->fingerprint->getDescriptions()->toTextArray();
		}

		if ( !is_null( $languageCodes ) ) {
			$textList = array_intersect_key( $textList, array_flip( $languageCodes ) );
		}

		return $textList;
	}

	/**
	 * Replaces the currently set labels with the provided ones.
	 * The labels are provided as an associative array where the keys are
	 * language codes pointing to the label in that language.
	 *
	 * @since 0.4
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string[] $labels
	 */
	public function setLabels( array $labels ) {
		$this->fingerprint->setLabels( new TermList() );

		foreach ( $labels as $languageCode => $labelText ) {
			$this->setLabel( $languageCode, $labelText );
		}
	}

	/**
	 * Replaces the currently set descriptions with the provided ones.
	 * The descriptions are provided as an associative array where the keys are
	 * language codes pointing to the description in that language.
	 *
	 * @since 0.4
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param string[] $descriptions
	 */
	public function setDescriptions( array $descriptions ) {
		$this->fingerprint->setDescriptions( new TermList() );

		foreach ( $descriptions as $languageCode => $descriptionText ) {
			$this->setDescription( $languageCode, $descriptionText );
		}
	}

	/**
	 * Replaces the currently set aliases with the provided ones.
	 * The aliases are provided as an associative array where the keys are
	 * language codes pointing to an array value that holds the aliases
	 * in that language.
	 *
	 * @since 0.4
	 * @deprecated since 0.7.3 - use getFingerprint and setFingerprint
	 *
	 * @param array[] $aliasLists
	 */
	public function setAllAliases( array $aliasLists ) {
		$this->fingerprint->setAliasGroups( new AliasGroupList() );

		foreach( $aliasLists as $languageCode => $aliasList ) {
			$this->setAliases( $languageCode, $aliasList );
		}
	}

	/**
	 * Returns a deep copy of the entity.
	 *
	 * @since 0.1
	 *
	 * @return static
	 */
	public function copy() {
		return unserialize( serialize( $this ) );
	}

	/**
	 * @see ClaimListAccess::addClaim
	 *
	 * @since 0.3
	 * @deprecated since 1.0
	 *
	 * @param Claim $claim
	 *
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function addClaim( Claim $claim ) {
		throw new RuntimeException( 'Claims on entities are not supported any more.' );
	}

	/**
	 * @since 0.3
	 * @deprecated since 1.0
	 *
	 * @return Claim[]
	 */
	public function getClaims() {
		return array();
	}

	/**
	 * Convenience function to check if the entity contains any claims.
	 *
	 * On top of being a convenience function, this implementation allows for doing
	 * the check without forcing an unstub in contrast to count( $this->getClaims() ).
	 *
	 * @since 0.2
	 * @deprecated since 1.0
	 *
	 * @return bool
	 */
	public function hasClaims() {
		return false;
	}

	/**
	 * @since 0.3
	 * @deprecated since 1.0
	 *
	 * @param Snak $mainSnak
	 *
	 * @return Claim
	 */
	public function newClaim( Snak $mainSnak ) {
		return new Claim( $mainSnak );
	}

	/**
	 * Returns an EntityDiff between $this and the provided Entity.
	 *
	 * @since 0.1
	 * @deprecated since 1.0 - use EntityDiffer or a more specific differ
	 *
	 * @param Entity $target
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public final function getDiff( Entity $target ) {
		$differ = new EntityDiffer();
		return $differ->diffEntities( $this, $target );
	}

	/**
	 * Apply an EntityDiff to the entity.
	 *
	 * @since 0.4
	 * @deprecated since 1.1 - use EntityPatcher or a more specific patcher
	 *
	 * @param EntityDiff $patch
	 */
	public final function patch( EntityDiff $patch ) {
		$patcher = new EntityPatcher();
		$patcher->patchEntity( $this, $patch );
	}

	/**
	 * Returns a list of all Snaks on this Entity. This includes at least the main snaks of
	 * Claims, the snaks from Claim qualifiers, and the snaks from Statement References.
	 *
	 * This is a convenience method for use in code that needs to operate on all snaks, e.g.
	 * to find all referenced Entities.
	 *
	 * @since 0.5
	 * @deprecated since 1.0 - use StatementList::getAllSnaks instead
	 *
	 * @return Snak[]
	 */
	public function getAllSnaks() {
		$snaks = array();

		foreach ( $this->getClaims() as $claim ) {
			foreach( $claim->getAllSnaks() as $snak ) {
				$snaks[] = $snak;
			}
		}

		return $snaks;
	}

	/**
	 * @since 0.7.3
	 *
	 * @return Fingerprint
	 */
	public function getFingerprint() {
		return $this->fingerprint;
	}

	/**
	 * @since 0.7.3
	 *
	 * @param Fingerprint $fingerprint
	 */
	public function setFingerprint( Fingerprint $fingerprint ) {
		$this->fingerprint = $fingerprint;
	}

	/**
	 * Returns if the Entity has no content.
	 * Having an id set does not count as having content.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public abstract function isEmpty();

	/**
	 * Removes all content from the Entity.
	 * The id is not part of the content.
	 *
	 * @since 0.1
	 */
	public abstract function clear();

}
