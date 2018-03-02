<?php

namespace Wikibase\View\Module;

use FormatJson;
use ResourceLoaderContext;
use ResourceLoaderFileModule;
use Wikibase\View\Template\TemplateFactory;

/**
 * Injects templates into JavaScript.
 *
 * @license GPL-2.0-or-later
 * @author H. Snater <mediawiki@snater.com>
 */
class TemplateModule extends ResourceLoaderFileModule {

	/**
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		// register HTML templates
		$templateFactory = TemplateFactory::getDefaultInstance();
		$templatesJson = FormatJson::encode( $templateFactory->getTemplates() );

		// template store JavaScript initialisation
		$script = <<<EOT
( function( mw ) {
	'use strict';

	mw.wbTemplates = mw.wbTemplates || {};
	mw.wbTemplates.store = new mw.Map();
	mw.wbTemplates.store.set( $templatesJson );

}( mediaWiki ) );
EOT;

		return $script . "\n" . parent::getScript( $context );
	}

	/**
	 * @see ResourceLoaderModule::supportsURLLoading
	 *
	 * @return bool
	 */
	public function supportsURLLoading() {
		return false; // always use getScript() to acquire JavaScript (even in debug mode)
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
		$summary['mtime'] = (string)filemtime( __DIR__ . '/../../resources/templates.php' );

		return $summary;
	}

}
