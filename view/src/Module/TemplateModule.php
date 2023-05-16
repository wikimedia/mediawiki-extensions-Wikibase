<?php

namespace Wikibase\View\Module;

use FormatJson;
// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use Wikibase\View\Template\TemplateFactory;

/**
 * Injects templates into JavaScript.
 *
 * @license GPL-2.0-or-later
 * @author H. Snater <mediawiki@snater.com>
 */
class TemplateModule {

	/**
	 * @param RL\Context $context
	 *
	 * @return string
	 */
	public static function getScript( RL\Context $context ) {
		// register HTML templates
		$templateFactory = TemplateFactory::getDefaultInstance();
		$templatesJson = FormatJson::encode( $templateFactory->getTemplates() );

		// template store JavaScript initialisation
		return <<<EOT
( function () {
	'use strict';

	mw.wbTemplates = mw.wbTemplates || {};
	mw.wbTemplates.store = new mw.Map();
	mw.wbTemplates.store.set( $templatesJson );

}() );
EOT;
	}

	/**
	 * @see RL\Module::getDefinitionSummary
	 *
	 * @param RL\Context $context
	 *
	 * @return RL\FilePath
	 */
	public static function getVersion( RL\Context $context ) {
		return new RL\FilePath( 'templates.php' );
	}

}
