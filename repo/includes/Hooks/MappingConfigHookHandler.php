<?php

namespace Wikibase\Repo\Hooks;

use CirrusSearch\Maintenance\MappingConfigBuilder;
use Wikibase\Repo\Search\Fields\WikibaseFieldsDefinition;

class MappingConfigHookHandler {

	/**
	 * @var WikibaseFieldsDefinition
	 */
	private $fieldsDefinition;

	/**
	 * @param array &$config
	 * @param MappingConfigBuilder $mappingConfigBuilder
	 *
	 * @return bool
	 */
	public static function onCirrusSearchMappingConfig(
		array &$config,
		MappingConfigBuilder $mappingConfigBuilder
	) {
		$handler = self::newFromGlobalState();
		$handler->addExtraFields( $config );

		return true;
	}

	private static function newFromGlobalState() {
		return new self(
			new WikibaseFieldsDefinition()
		);
	}

	/**
	 * @param WikibaseFieldsDefinition $fieldsDefinition
	 */
	public function __construct( WikibaseFieldsDefinition $fieldsDefinition ) {
		$this->fieldsDefinition = $fieldsDefinition;
	}

	/**
	 * @param array &$config
	 */
	public function addExtraFields( array &$config ) {
		$fields = $this->fieldsDefinition->getFields();

		foreach ( $fields as $fieldName => $field ) {
			$config['page']['properties'][$fieldName] = $field->getMapping();
		}
	}

}
