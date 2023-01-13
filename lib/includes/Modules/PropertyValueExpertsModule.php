<?php

namespace Wikibase\Lib\Modules;

// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use Wikibase\Lib\DataTypeDefinitions;

/**
 * Module exporting map from property type to expert module name handling this type
 *
 * @note Tested via wikibase.experts.modules.tests.js
 *
 * @license GPL-2.0-or-later
 */
class PropertyValueExpertsModule extends RL\Module {
	/** @var string[] Limited to desktop because of T326405 */
	protected $targets = [ 'desktop' ];

	/**
	 * @var DataTypeDefinitions
	 */
	private $dataTypeDefinitions;

	public function __construct( DataTypeDefinitions $dataTypeDefinitions ) {
		$this->dataTypeDefinitions = $dataTypeDefinitions;
	}

	public function getScript( RL\Context $context ) {
		$expertModuleMap = \Xml::encodeJsVar( $this->dataTypeDefinitions->getExpertModules() );

		$js = <<<JS
module.exports = ( function () {
	'use strict';
	return $expertModuleMap;
}() );
JS;

		return $js;
	}

	public function getDependencies( RL\Context $context = null ) {
		return array_values( $this->dataTypeDefinitions->getExpertModules() );
	}

}
