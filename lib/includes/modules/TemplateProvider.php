<?php

namespace Wikibase;
use ResourceLoaderModule, ResourceLoaderContext;

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

class TemplateProvider extends ResourceLoaderModule {

	public function getScript( ResourceLoaderContext $context ) {
		return 'mediaWiki.config.set( "wbTemplates", ' . \FormatJson::encode(
			HtmlTemplateStore::singleton()->getTemplates()
		) . ' )';
	}

}
