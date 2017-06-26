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

		$json = \XML::encodeJsVar( $this->dataTypeDefinitions->getExpertModules() );

		//TODO Throw exception if no expert module defined. Probably not here, but somewhere.
		//TODO Change all experts to use `module.exports =` construct

		$js = <<<JS
module.exports = ( function () {
	'use strict';
	return $json;
}() );
JS;

		return $js;
	}

	public function getDependencies( ResourceLoaderContext $context = null ) {
		return array_values( $this->dataTypeDefinitions->getExpertModules() );
	}

}

