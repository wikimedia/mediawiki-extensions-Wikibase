<?php

namespace Wikibase\Repo\ParserOutput;

use ExtensionRegistry;
use MobileContext;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class TermboxFlag {

	private $settings;

	private $extensionRegistry;

	public const TERMBOX_FLAG = 'termboxEnabled';
	public const TERMBOX_DESKTOP_FLAG = 'termboxDesktopEnabled';

	public function __construct(
		SettingsArray $settings,
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
		return (
			$this->isMobile() && $this->shouldRenderTermboxMobile() ||
			!$this->isMobile() && $this->shouldRenderTermboxDesktop()
		);
	}

	private function shouldRenderTermboxMobile(): bool {
		return $this->settings->getSetting( self::TERMBOX_FLAG );
	}

	private function isMobile(): bool {
		return $this->extensionRegistry->isLoaded( 'MobileFrontend' )
			&& MobileContext::singleton()->shouldDisplayMobileView();
	}

	private function shouldRenderTermboxDesktop(): bool {
		return $this->settings->getSetting( self::TERMBOX_DESKTOP_FLAG );
	}

}
