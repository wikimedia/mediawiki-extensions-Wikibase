<?php

namespace Wikibase\Lib\Store;

use MWException;
use Wikibase\TermIndexEntry;

/**
 * Object representing a term index search mask.
 *
 * @license GPL-2.0+
 */
class TermIndexMask {

	private $fieldNames = array(
		'entityType',
		'termType',
		'termLanguage',
		'termText',
	);

	private $termType = null;

	private $termLanguage = null;

	private $termText = null;

	private $entityType = null;

	/**
	 * @since 0.2
	 *
	 * @param array $fields
	 *
	 * @throws MWException
	 */
	public function __construct( array $fields = [] ) {
		$unexpectedFields = array_diff_key( $fields, array_flip( $this->fieldNames ) );
		if ( $unexpectedFields ) {
			throw new MWException( 'Invalid term index mask field provided' );
		}

		if ( array_key_exists( 'termType', $fields ) ) {
			$this->setType( $fields['termType'] );
		}
		if ( array_key_exists( 'termLanguage', $fields ) ) {
			$this->setLanguage( $fields['termLanguage'] );
		}
		if ( array_key_exists( 'entityType', $fields ) ) {
			$this->setEntityType( $fields['entityType'] );
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
	private function setType( $termType ) {
		if ( !in_array( $termType, array( TermIndexEntry::TYPE_ALIAS, TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ), true ) ) {
			throw new MWException( 'Invalid term type provided' );
		}

		$this->termType = $termType;
	}

	/**
	 * @return string|null
	 */
	public function getType() {
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

	/**
	 * @param string $entityType
	 *
	 * @throws MWException
	 */
	private function setEntityType( $entityType ) {
		if ( !is_string( $entityType ) ) {
			throw new MWException( 'Entity type code can only be a string' );
		}

		$this->entityType = $entityType;
	}

	/**
	 * @return string|null
	 */
	public function getEntityType() {
		return $this->entityType;
	}

}