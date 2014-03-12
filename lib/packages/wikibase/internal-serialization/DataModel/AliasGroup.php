<?php

namespace Wikibase\DataModel\Term;

class AliasGroup {

	private $languageCode;
	private $aliases;

	/**
	 * @param string $languageCode
	 * @param string[] $aliases
	 */
	public function __construct( $languageCode, array $aliases ) {
		$this->languageCode = $languageCode;
		$this->aliases = $aliases;
	}

	/**
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * @return string[]
	 */
	public function getAliases() {
		return $this->aliases;
	}

}