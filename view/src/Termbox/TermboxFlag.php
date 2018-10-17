<?php

namespace Wikibase\View;

use ExtensionRegistry;
use MobileContext;
use Wikibase\Repo\WikibaseRepo;

class TermboxFlag {

	/**
	 * Determines whether the Termbox should be rendered
	 *
	 * @return bool
	 */
	static public function shouldRenderTermbox() {
		return WikibaseRepo::getDefaultInstance()->getSettings()
				->getSetting( 'termboxEnabled' )
			&& ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' )
			&& MobileContext::singleton()->shouldDisplayMobileView();
	}

}
