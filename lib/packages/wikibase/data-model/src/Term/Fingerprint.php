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
	 * @return TermList
	 */
	public function getDescriptions() {
		return $this->descriptions;
	}

	/**
	 * @return AliasGroupList
	 */
	public function getAliasGroups() {
		return $this->aliasGroups;
	}

}
