<?php

namespace Wikibase\Lib\Modules;

use MediaWiki\Html\Html;
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

	/**
	 * @var DataTypeDefinitions
	 */
	private $dataTypeDefinitions;

	public function __construct( DataTypeDefinitions $dataTypeDefinitions ) {
		$this->dataTypeDefinitions = $dataTypeDefinitions;
	}

	/**
	 * @param RL\Context $context
	 * @return string
	 */
	public function getScript( RL\Context $context ) {
		$expertModuleMap = Html::encodeJsVar( $this->dataTypeDefinitions->getExpertModules() );

		$js = <<<JS
module.exports = ( function () {
	'use strict';
	return $expertModuleMap;
}() );
JS;

		return $js;
	}

	/**
	 * @param RL\Context|null $context
	 * @return array|string[]
	 */
	public function getDependencies( ?RL\Context $context = null ) {
		return array_values( $this->dataTypeDefinitions->getExpertModules() );
	}

	/** @inheritDoc */
	public function enableModuleContentVersion() {
		// Let RL\Module::getVersionHash() invoke getScript() and hash that.
		return true;
	}

}
