<?php

namespace Wikibase;
use ResourceLoaderContext;
use ResourceLoaderFileModule;

/**
 * Injects templates into JavaScript.
 *
 * Note: when moving the file, the path to templates.php might need updating.
 *
 * @since 0.2
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
		$templatesJson = \FormatJson::encode( TemplateRegistry::singleton()->getTemplates() );

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

}
