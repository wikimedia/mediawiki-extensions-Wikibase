<?php

namespace Wikibase\Repo\ParserOutput\PlaceholderExpander;

use IContextSource;
use Wikibase\LanguageFallbackChainFactory;

/**
 * Determines whether the entity page was requested with non-default settings,
 * e.g. custom language preferences.
 *
 * @license GPL-2.0-or-later
 */
class TermboxRequestInspector {

	private $languageFallbackChainFactory;
	private $useUserSpecificSSR;

	public function __construct( LanguageFallbackChainFactory $languageFallbackChainFactory, $useUserSpecificSSR ) {
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->useUserSpecificSSR = $useUserSpecificSSR;
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return bool
	 */
	public function isDefaultRequest( IContextSource $context ) {
		if ( $this->useUserSpecificSSR === false ) {
			return true;
		}

		return $this->languageFallbackChainFactory->newFromContext( $context )->getFallbackChain()
			=== $this->languageFallbackChainFactory->newFromLanguage( $context->getLanguage() )->getFallbackChain();
	}

}
