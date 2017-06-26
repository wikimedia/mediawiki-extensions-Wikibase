<?php

namespace Wikibase\Repo\Modules;

use ResourceLoaderContext;
use Wikibase\Lib\DataTypeDefinitions;

class PropertyValueExpertsModule extends \ResourceLoaderModule {

	/**
	 * @var DataTypeDefinitions
	 */
	private $dataTypeDefinitions;

	public function __construct( DataTypeDefinitions $dataTypeDefinitions ) {

		$this->dataTypeDefinitions = $dataTypeDefinitions;
	}

	public function getScript( ResourceLoaderContext $context ) {

		$expertModuleMap = \XML::encodeJsVar( $this->dataTypeDefinitions->getExpertModules() );

		//TODO Throw exception if no expert module defined. Probably not here, but somewhere.

		$js = <<<JS
module.exports = ( function () {
	'use strict';
	return $expertModuleMap;
}() );
JS;

		return $js;
	}

	public function getDependencies( ResourceLoaderContext $context = null ) {
		return array_values( $this->dataTypeDefinitions->getExpertModules() );
	}

}

