<?php

namespace Wikibase;
use ResourceLoaderFileModule, ResourceLoaderContext;

/**
 * Injects HTML templates into JavaScript.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

class TemplateProvider extends ResourceLoaderFileModule {

	public function getScript( ResourceLoaderContext $context ) {
		return 'mediaWiki.config.set( "wgTemplateStore", ' . \FormatJson::encode(
			TemplateStore::singleton()->getTemplates()
		) . ' );' . "\n" . parent::getScript( $context );
	}

	public function supportsURLLoading() {
		return false; // always use getScript() to acquire JavaScript (even in debug mode)
	}

}
