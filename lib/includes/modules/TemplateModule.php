<?php

namespace Wikibase;
use ResourceLoaderFileModule, ResourceLoaderContext;

/**
 * Injects templates into JavaScript.
 *
 * Note: when moving the file, the path to templates.php might need updating.
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
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
		$templateStore = new TemplateRegistry();
		$templateStore->addTemplates( include( __DIR__ . "/../../resources/templates.php" ) );

		$templatesJson = \FormatJson::encode( $templateStore->getTemplates() );

		// template store JavaScript initialisation
		$script = <<<EOT
( function( mw ) {
	'use strict';

	mw.templates = mw.templates || {};
	mw.templates.store = new mw.Map();
	mw.templates.store.set( $templatesJson );

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

}
