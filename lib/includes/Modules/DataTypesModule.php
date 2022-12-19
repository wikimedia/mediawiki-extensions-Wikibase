<?php

namespace Wikibase\Lib\Modules;

use Exception;
// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;
use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;

/**
 * Resource loader module for defining resources that will create a MW config var in JavaScript
 * holding information about all data types known to a given DataTypeFactory.
 *
 * The resource definition requires the following additional keys:
 * - (string) datatypesconfigvarname: Name of the "mw.config.get( '...' )" config variable.
 * - (Function|DataTypeFactory) datatypefactory: Provider for the data types. Can be a callback
 *   returning a DataTypeFactory instance.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
class DataTypesModule extends RL\Module {

	/**
	 * @var string[]
	 */
	protected $targets = [ 'desktop', 'mobile' ];

	/**
	 * @var DataType[]
	 */
	protected $dataTypes;

	/**
	 * @var string
	 */
	protected $dataTypesConfigVarName;

	/**
	 * @var DataTypeFactory
	 */
	protected $dataTypeFactory;

	/**
	 * @since 0.1
	 *
	 * @param array $resourceDefinition
	 */
	public function __construct( array $resourceDefinition ) {
		$this->dataTypesConfigVarName =
			static::extractDataTypesConfigVarNameFromResourceDefinition( $resourceDefinition );

		$this->dataTypeFactory =
			static::extractDataTypeFactoryFromResourceDefinition( $resourceDefinition );

		$dataTypeFactory = $this->getDataTypeFactory();
		$this->dataTypes = $dataTypeFactory->getTypes();
	}

	/**
	 * @since 0.1
	 *
	 * @param array $resourceDefinition
	 *
	 * @return string
	 * @throws Exception If the given resource definition is not sufficient
	 */
	public static function extractDataTypesConfigVarNameFromResourceDefinition(
		array $resourceDefinition
	) {
		$dataTypesConfigVarName = $resourceDefinition['datatypesconfigvarname'] ?? null;

		if ( !is_string( $dataTypesConfigVarName ) || $dataTypesConfigVarName === '' ) {
			throw new Exception(
				'The "datatypesconfigvarname" value of the resource definition' .
				' has to be a non-empty string value'
			);
		}

		return $dataTypesConfigVarName;
	}

	/**
	 * @since 0.1
	 *
	 * @param array $resourceDefinition
	 *
	 * @return DataTypeFactory
	 * @throws Exception If the given resource definition is not sufficient
	 */
	public static function extractDataTypeFactoryFromResourceDefinition(
		array $resourceDefinition
	) {
		$dataTypeFactory = $resourceDefinition['datatypefactory'] ?? null;

		if ( is_callable( $dataTypeFactory ) ) {
			$dataTypeFactory = call_user_func( $dataTypeFactory );
		}

		if ( !( $dataTypeFactory instanceof DataTypeFactory ) ) {
			throw new Exception(
				'The "datatypefactory" value of the resource definition has' .
				' to be an instance of DataTypeFactory or a callback returning one'
			);
		}

		return $dataTypeFactory;
	}

	/**
	 * Returns the name of the config var key under which the data type definition will be available
	 * in JavaScript using "mw.config.get( '...' )"
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getConfigVarName() {
		return $this->dataTypesConfigVarName;
	}

	/**
	 * Returns the data types factory providing the data type information.
	 *
	 * @since 0.1
	 *
	 * @return DataTypeFactory
	 */
	public function getDataTypeFactory() {
		return $this->dataTypeFactory;
	}

	/**
	 * Used to propagate available data type ids to JavaScript.
	 * Data type ids will be available in 'wbDataTypeIds' config var.
	 * @see RL\Module::getScript
	 *
	 * @since 0.1
	 *
	 * @param RL\Context $context
	 *
	 * @return string
	 */
	public function getScript( RL\Context $context ) {
		$configVarName = $this->getConfigVarName();
		$typesJson = [];

		foreach ( $this->dataTypes as $dataType ) {
			$typesJson[ $dataType->getId() ] = $dataType->toArray();
		}

		return ResourceLoader::makeConfigSetScript( [ $configVarName => $typesJson ] );
	}

	/**
	 * @see RL\Module::getDefinitionSummary
	 *
	 * @param RL\Context $context
	 *
	 * @return array
	 */
	public function getDefinitionSummary( RL\Context $context ) {
		$summary = parent::getDefinitionSummary( $context );

		$summary[] = [
			'dataHash' => sha1( json_encode( array_keys( $this->dataTypes ) ) ),
		];

		return $summary;
	}

}
