<?php

namespace Wikibase\Lib\Store;

use MWException;
use Wikibase\TermIndexEntry;

/**
 * Object representing a term index search mask.
 * Instances might be incomplete.
 *
 * @license GPL-2.0+
 */
class TermIndexMask {

	private $fieldNames = [
		'termType',
		'termLanguage',
		'termText',
	];

	private $termType = null;

	private $termLanguage = null;

	private $termText = null;

	/**
	 * @param array $fields, containing fields:
	 *        'termType' => string|null, one of self::TYPE_* constants,
	 *        'termLanguage' => string|null
	 *        'termText' => string|null
	 *
	 * @throws MWException
	 */
	public function __construct( array $fields = [] ) {
		$unexpectedFields = array_diff_key( $fields, array_flip( $this->fieldNames ) );
		if ( $unexpectedFields ) {
			throw new MWException( 'Invalid term index mask field provided' );
		}

		if ( array_key_exists( 'termType', $fields ) ) {
			$this->setTermType( $fields['termType'] );
		}
		if ( array_key_exists( 'termLanguage', $fields ) ) {
			$this->setLanguage( $fields['termLanguage'] );
		}
		if ( array_key_exists( 'termText', $fields ) ) {
			$this->setText( $fields['termText'] );
		}
	}

	/**
	 * @param string $termType
	 *
	 * @throws MWException
	 */
	private function setTermType( $termType ) {
		if ( !in_array( $termType, array( TermIndexEntry::TYPE_ALIAS, TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ), true ) ) {
			throw new MWException( 'Invalid term type provided' );
		}

		$this->termType = $termType;
	}

	/**
	 * @return string|null
	 */
	public function getTermType() {
		return $this->termType;
	}

	/**
	 * @param string $languageCode
	 *
	 * @throws MWException
	 */
	private function setLanguage( $languageCode ) {
		if ( !is_string( $languageCode ) ) {
			throw new MWException( 'Language code can only be a string' );
		}

		$this->termLanguage = $languageCode;
	}

	/**
	 * @return string|null
	 */
	public function getLanguage() {
		return $this->termLanguage;
	}

	/**
	 * @param string $text
	 *
	 * @throws MWException
	 */
	private function setText( $text ) {
		if ( !is_string( $text ) ) {
			throw new MWException( 'Term text can only be a string' );
		}

		$this->termText = $text;
	}

	/**
	 * @return string|null
	 */
	public function getText() {
		return $this->termText;
	}

}
