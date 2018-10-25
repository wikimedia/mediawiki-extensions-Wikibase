<?php

namespace Wikibase\Repo\ParserOutput;

use ExtensionRegistry;
use MobileContext;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class TermboxFlag {

	/**
	 * Determines whether the Termbox should be rendered
	 *
	 * @return bool
	 */
	public static function shouldRenderTermbox() {
		return WikibaseRepo::getDefaultInstance()->getSettings()
				->getSetting( 'termboxEnabled' )
			&& ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' )
			&& MobileContext::singleton()->shouldDisplayMobileView();
	}

}
