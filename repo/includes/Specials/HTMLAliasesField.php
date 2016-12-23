<?php

namespace Wikibase\Repo\Specials;

use HTMLTextField;
use Wikibase\StringNormalizer;

class HTMLAliasesField extends HTMLTextField {
	const DELIMITER = '|';

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	private $defaultParameters = [
		'placeholder-message' => 'wikibase-aliases-edit-placeholder',
		'label-message' => 'wikibase-newentity-aliases',
	];

	public function __construct( array $params ) {
		if (isset($params['filter-callback'])) {
			throw new \InvalidArgumentException(
				"Can not use `filter-callback` for aliases field. It already has it's own filtering"
			);
		}

		parent::__construct( array_merge( $this->defaultParameters, $params ) );

		$this->stringNormalizer = new StringNormalizer();
	}

	public function filter( $value, $alldata ) {
		$aliases = explode( self::DELIMITER, $value );
		$aliases = array_map( [ $this->stringNormalizer, 'trimToNFC' ], $aliases );

		return array_values( array_filter( $aliases ) );
	}

	public function getOOUI( $value ) {
		$value = $this->arrayToString( $value );

		return parent::getOOUI( $value );
	}

	public function getTableRow( $value ) {
		$value = $this->arrayToString( $value );

		return parent::getTableRow( $value );
	}

	public function getDiv( $value ) {
		$value = $this->arrayToString( $value );

		return parent::getDiv( $value );
	}

	public function getRaw( $value ) {
		$value = $this->arrayToString( $value );

		return parent::getRaw( $value );
	}

	public function getVForm( $value ) {
		$value = $this->arrayToString( $value );

		return parent::getVForm( $value );
	}

	public function getInline( $value ) {
		$value = $this->arrayToString( $value );

		return parent::getInline( $value );
	}

	private function arrayToString( $value ) {
		return implode( self::DELIMITER, $value );
	}

}
