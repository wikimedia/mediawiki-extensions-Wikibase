<?php

namespace Wikibase\Repo\ParserOutput;

use ExtensionRegistry;
use MobileContext;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class TermboxFlag {

	private $settings;

	private $extensionRegistry;

	const TERMBOX_FLAG = 'termboxEnabled';

	public function __construct(
		SettingsArray  $settings,
		ExtensionRegistry $extensionRegistry
	) {
		$this->settings = $settings;
		$this->extensionRegistry = $extensionRegistry;
	}

	public static function getInstance() {
		return new self(
			WikibaseRepo::getDefaultInstance()->getSettings(),
			ExtensionRegistry::getInstance()
		);
	}

	/**
	 * Determines whether the Termbox should be rendered
	 *
	 * @return bool
	 */
	public function shouldRenderTermbox() {
		return $this->settings->getSetting( self::TERMBOX_FLAG )
			&& $this->extensionRegistry->isLoaded( 'MobileFrontend' )
			&& MobileContext::singleton()->shouldDisplayMobileView();
	}

}
