<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput;

use ExtensionRegistry;
use MobileContext;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class TermboxFlag {

	/** @var SettingsArray */
	private $settings;

	/** @var ExtensionRegistry */
	private $extensionRegistry;

	const TERMBOX_FLAG = 'termboxEnabled';

	public function __construct(
		SettingsArray $settings,
		ExtensionRegistry $extensionRegistry
	) {
		$this->settings = $settings;
		$this->extensionRegistry = $extensionRegistry;
	}

	public static function getInstance(): self {
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
	public function shouldRenderTermbox(): bool {
		return $this->settings->getSetting( self::TERMBOX_FLAG )
			&& $this->extensionRegistry->isLoaded( 'MobileFrontend' )
			&& MobileContext::singleton()->shouldDisplayMobileView();
	}

}
