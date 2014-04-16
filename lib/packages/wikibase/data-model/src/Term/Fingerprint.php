<?php

namespace Wikibase\DataModel\Term;

/**
 * @since 0.7.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Fingerprint {

	/**
	 * @return Fingerprint
	 */
	public static function newEmpty() {
		return new self(
			new TermList( array() ),
			new TermList( array() ),
			new AliasGroupList( array() )
		);
	}

	private $labels;
	private $descriptions;
	private $aliasGroups;

	public function __construct( TermList $labels, TermList $descriptions, AliasGroupList $aliasGroups ) {
		$this->labels = $labels;
		$this->descriptions = $descriptions;
		$this->aliasGroups = $aliasGroups;
	}

	/**
	 * @return TermList
	 */
	public function getLabels() {
		return $this->labels;
	}

	/**
	 * @param string $languageCode
	 * @return Term
	 */
	public function getLabel( $languageCode ) {
		return $this->labels->getByLanguage( $languageCode );
	}

	/**
	 * @param Term $label
	 */
	public function setLabel( Term $label ) {
		$this->descriptions->setTerm( $label );
	}

	/**
	 * @param string|Term $languageCode
	 */
	public function removeLabel( $languageCode ) {
		if ( $languageCode instanceof Term ) {
			/** @var Term $term */
			$term = $languageCode;
			$languageCode = $term->getLanguageCode();
		}

		$this->labels->removeByLanguage( $languageCode );
	}

	/**
	 * @return TermList
	 */
	public function getDescriptions() {
		return $this->descriptions;
	}

	/**
	 * @param string $languageCode
	 * @return Term
	 */
	public function getDescription( $languageCode ) {
		return $this->descriptions->getByLanguage( $languageCode );
	}

	/**
	 * @param Term $description
	 */
	public function setDescription( Term $description ) {
		$this->descriptions->setTerm( $description );
	}

	/**
	 * @param string|Term $languageCode
	 */
	public function removeDescription( $languageCode ) {
		if ( $languageCode instanceof Term ) {
			/** @var Term $term */
			$term = $languageCode;
			$languageCode = $term->getLanguageCode();
		}

		$this->descriptions->removeByLanguage( $languageCode );
	}

	/**
	 * @return AliasGroupList
	 */
	public function getAliasGroups() {
		return $this->aliasGroups;
	}

	/**
	 * @param string $languageCode
	 * @return AliasGroup
	 */
	public function getAliasGroup( $languageCode ) {
		return $this->aliasGroups->getByLanguage( $languageCode );
	}

	/**
	 * @param AliasGroup $aliasGroup
	 */
	public function setAliasGroup( AliasGroup $aliasGroup ) {
		$this->aliasGroups->setGroup( $aliasGroup );
	}

	/**
	 * @param string|AliasGroup $languageCode
	 */
	public function removeAliasGroup( $languageCode ) {
		if ( $languageCode instanceof AliasGroup ) {
			/** @var AliasGroup $aliasGroup */
			$aliasGroup = $languageCode;
			$languageCode = $aliasGroup->getLanguageCode();
		}

		$this->aliasGroups->removeByLanguage( $languageCode );
	}

}
