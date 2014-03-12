<?php

namespace Wikibase\DataModel\Term;

abstract class AbstractTerm implements Term {

	private $languageCode;
	private $text;

	/**
	 * @param string $languageCode
	 * @param string $text
	 */
	public function __construct( $languageCode, $text ) {
		$this->languageCode = $languageCode;
		$this->text = $text;
	}

	/**
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}

}