<?php

namespace Wikibase\Repo\Modules;

use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;
use Exception;
use FormatJson;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * Resource loader module for defining resources that will create a MW config var in JavaScript
 * holding information about all data types known to a given DataTypeFactory.
 *
 * The resource definition requires the following additional keys:
 * - (string) datatypesconfigvarname: Name of the "mw.config.get( '...' )" config variable.
 * - (Function|DataTypeFactory) datatypefactory: Provider for the data types. Can be a callback
 *   returning a DataTypeFactory instance.
 *
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
class DataTypesModule extends ResourceLoaderModule {

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
		$dataTypesConfigVarName = array_key_exists( 'datatypesconfigvarname', $resourceDefinition )
			? $resourceDefinition['datatypesconfigvarname']
			: null;

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
		$dataTypeFactory = array_key_exists( 'datatypefactory', $resourceDefinition )
			? $resourceDefinition['datatypefactory']
			: null;

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
	 * @see ResourceLoaderModule::getScript
	 *
	 * @since 0.1
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$configVarName = $this->getConfigVarName();
		$typesJson = [];

		foreach ( $this->dataTypes as $dataType ) {
			$typesJson[ $dataType->getId() ] = $dataType->toArray();
		}
		$typesJson = FormatJson::encode( $typesJson );

		return "mediaWiki.config.set( '$configVarName', $typesJson );";
	}

	/**
	 * Returns the message keys of the registered data types.
	 * @see ResourceLoaderModule::getMessages
	 * @since 0.1
	 *
	 * @return string[]
	 */
	public function getMessages() {
		$messageKeys = [];

		foreach ( $this->dataTypes as $dataType ) {
			// TODO: currently we assume that the type is using a message while it does not have to.
			//  Either change the system to ensure that a message is used or put the type labels
			//  directly into the JSON. Either way, the information should be in DataType::toArray.
			$messageKeys[] = 'datatypes-type-' . $dataType->getId();
		}

		return $messageKeys;
	}

	/**
	 * @see ResourceLoaderModule::getDefinitionSummary
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return array
	 */
	public function getDefinitionSummary( ResourceLoaderContext $context ) {
		$summary = parent::getDefinitionSummary( $context );

		$summary[] = [
			'dataHash' => sha1( json_encode( array_keys( $this->dataTypes ) ) )
		];

		return $summary;
	}

}
