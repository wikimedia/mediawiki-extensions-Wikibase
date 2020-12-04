<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput\PlaceholderExpander;

use IContextSource;
use Wikibase\Lib\LanguageFallbackChainFactory;

/**
 * Determines whether the entity page was requested with non-default settings,
 * e.g. custom language preferences.
 *
 * @license GPL-2.0-or-later
 */
class TermboxRequestInspector {

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;

	public function __construct( LanguageFallbackChainFactory $languageFallbackChainFactory ) {
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
	}

	public function isDefaultRequest( IContextSource $context ): bool {
		return $this->languageFallbackChainFactory->newFromContext( $context )->getFallbackChain()
			=== $this->languageFallbackChainFactory->newFromLanguage( $context->getLanguage() )->getFallbackChain();
	}

}
