<?php

namespace Wikibase;
use ResourceLoaderFileModule, ResourceLoaderContext;

/**
 * Injects templates into JavaScript.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

class TemplateModule extends ResourceLoaderFileModule {

	/**
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param \ResourceLoaderContext $context
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$templatesJson = \FormatJson::encode( TemplateStore::singleton()->getTemplates() );

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
