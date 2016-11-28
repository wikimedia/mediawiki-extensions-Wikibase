<?php

namespace Wikibase\Lib\Store;

use Wikibase\TermIndexEntry;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Object representing search criteria while performing a search in the term index.
 * Instances might be incomplete (have null values for some of the fields).
 *
 * @license GPL-2.0+
 */
class TermIndexSearchCriteria {

	private $fieldNames = [
		'termType',
		'termLanguage',
		'termText',
	];

	/**
	 * @var string|null
	 */
	private $termType = null;

	/**
	 * @var string|null
	 */
	private $termLanguage = null;

	/**
	 * @var string|null
	 */
	private $termText = null;

	/**
	 * @param array $fields, containing fields:
	 *        'termType' => string|null, one of TermIndexEntry::TYPE_… constants,
	 *        'termLanguage' => string|null
	 *        'termText' => string|null
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $fields = [] ) {
		Assert::parameter(
			empty( array_diff( array_keys( $fields ), $this->fieldNames ) ),
			'$fields',
			'must only contain the following keys: termType, termLanguage, termText'
		);
		Assert::parameter(
			!isset( $fields['termType'] ) || (
				is_string( $fields['termType'] ) && in_array(
					$fields['termType'],
					[ TermIndexEntry::TYPE_ALIAS, TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ]
				)
			),
			'$fields["termType"]',
			'must be TermIndexEntry::TYPE_ALIAS, TermIndexEntry::TYPE_LABEL, or TermIndexEntry::TYPE_DESCRIPTION'
		);
		Assert::parameter(
			!isset( $fields['termLanguage'] ) || (
				is_string( $fields['termLanguage'] ) && $fields['termLanguage'] !== ''
			),
			'$fields["termLanguage"]',
			'must be a non-empty string when provided'
		);
		Assert::parameter(
			!isset( $fields['termText'] ) || (
				is_string( $fields['termText'] ) && $fields['termText'] !== ''
			),
			'$fields["termText"]',
			'must be a non-empty string when provided'
		);

		if ( isset( $fields['termType'] ) ) {
			$this->termType = $fields['termType'];
		}
		if ( isset( $fields['termLanguage'] ) ) {
			$this->termLanguage = $fields['termLanguage'];
		}
		if ( isset( $fields['termText'] ) ) {
			$this->termText = $fields['termText'];
		}
	}

	/**
	 * @return string|null
	 */
	public function getTermType() {
		return $this->termType;
	}

	/**
	 * @return string|null
	 */
	public function getLanguage() {
		return $this->termLanguage;
	}

	/**
	 * @return string|null
	 */
	public function getText() {
		return $this->termText;
	}

}
