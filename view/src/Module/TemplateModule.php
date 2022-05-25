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
class TemplateModule extends RL\FileModule {

	/**
	 * @see RL\Module::getScript
	 *
	 * @param RL\Context $context
	 *
	 * @return string
	 */
	public function getScript( RL\Context $context ) {
		// register HTML templates
		$templateFactory = TemplateFactory::getDefaultInstance();
		$templatesJson = FormatJson::encode( $templateFactory->getTemplates() );

		// template store JavaScript initialisation
		$script = <<<EOT
( function () {
	'use strict';

	mw.wbTemplates = mw.wbTemplates || {};
	mw.wbTemplates.store = new mw.Map();
	mw.wbTemplates.store.set( $templatesJson );

}() );
EOT;

		return $script . "\n" . parent::getScript( $context );
	}

	/**
	 * @see RL\Module::supportsURLLoading
	 *
	 * @return bool
	 */
	public function supportsURLLoading() {
		return false; // always use getScript() to acquire JavaScript (even in debug mode)
	}

	/**
	 * @see RL\Module::getDefinitionSummary
	 *
	 * @param RL\Context $context
	 *
	 * @return array
	 */
	public function getDefinitionSummary( RL\Context $context ) {
		$summary = parent::getDefinitionSummary( $context );
		$summary['mtime'] = (string)filemtime( __DIR__ . '/../../resources/templates.php' );

		return $summary;
	}

}
