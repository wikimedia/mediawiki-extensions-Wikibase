<?php

namespace DataValue {

interface Hashable {

	public function getHash();

}

interface Comparable {

	public function equals( $dataValue );

}

/**
 * Interface for objects that represent a single data value.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface DataValue extends Hashable, Comparable, \Serializable {

	public function getType();

	public function getSortKey();

}

}
namespace Wikibase {
	$wbTypes = array(
		'geo' => array(
			'datavalue' => 'geo-dv',
			'parser' => 'geo-parser',
			'formatter' => 'geo-formatter',
		),
		'positive-number' => array(
			'datavalue' => 'numeric-dv',
			'parser' => 'numeric-parser',
			'formatter' => 'numeric-formatter',
			'validators' => array( $rangeValidator ),
		),
	);

	class TypeFactory {

		/**
		 * Maps type id to Type.
		 * @var array of Type
		 */
		protected $types;

		public function singleton() {
			if ( true ) {
				$instance = 0;

				$instance->initizlise();
			}


			return $instance;
		}

		protected function initizlise()  {

		}

		/**
		 * @return array of $typeId
		 */
		public function getTypesIds() {
			return array_keys( $this->types );
		}

		/**
		 * @param $typeId
		 * @return Type
		 */
		public function getType( $typeId ) {
			return $this->types[$typeId];
		}

		/**
		 * @return array of Type
		 */
		public function getTypes() {
			return $this->types;
		}

	}

	interface Type {

		public function getType();

		public function getDataValueType();

		public function getParser();

		/**
		 * @abstract
		 * @return ValueFormatter
		 */
		public function getFormatter();

		//public function getValidators();

		public function getLabel( $langCode );

	}

	class TypeObject implements Type {

		public function __construct() {

		}

		public function getType() {

		}

		public function getDataValueType() {

		}

		public function getParser() {

		}

		public function getFormatter() {

		}

		public function getLabel( $langCode ) {

		}

	}

	class WikibaseValue {

		public static function newFromParse( Type $type, $rawValue ) {
			return new static( $type, $type->getParser()->parse( $rawValue ) );
		}

		/**
		 * @var Type $type
		 */
		protected $type;

		public function __construct( Type $type, DataValue $value ) {

		}

		public function format() {
			$this->type->getFormatter()->format( $this->value );
		}

		public function getType() {
			return $this->type;
		}

	}

}

