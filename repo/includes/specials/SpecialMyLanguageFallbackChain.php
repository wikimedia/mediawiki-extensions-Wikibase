<?php

namespace Wikibase\Repo\Specials;

use Html;
use IContextSource;
use Language;
use SpecialPage;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * Page for displaying the current language fallback chain for debugging.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 */
class SpecialMyLanguageFallbackChain extends SpecialPage {

	/**
	 * @var LanguageFallbackChain
	 * @since 0.4
	 */
	private $chain;

	/**
	 * @var LanguageFallbackChainFactory
	 * @since 0.4
	 */
	private $factory;

	/**
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'MyLanguageFallbackChain' );
		$this->factory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @since 0.4
	 * @return String
	 */
	public function getDescription() {
		// Message: special-mylanguagefallbackchain
		return $this->msg( 'special-' . strtolower( $this->getName() ) )->text();
	}

	/**
	 * Set the context.
	 *
	 * @param IContextSource $context
	 */
	public function setContext( $context ) {
		$this->chain = null;
		parent::setContext( $context );
	}

	/**
	 * Get the chain stored for display.
	 *
	 * @return LanguageFallbackChain
	 */
	public function getLanguageFallbackChain() {
		if ( $this->chain === null ) {
			$this->setLanguageFallbackChain( $this->factory->newFromContext( $this->getContext() ) );
		}
		return $this->chain;
	}

	/**
	 * Set a new chain for display and return the original one.
	 *
	 * @param LanguageFallbackChain $chain
	 * @return LanguageFallbackChain
	 */
	public function setLanguageFallbackChain( $chain ) {
		return wfSetVar( $this->chain, $chain );
	}

	/**
	 * Main method
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->outputHeader();

		$this->getOutput()->addWikiMsg( 'wikibase-mylanguagefallbackchain-text' );
		if ( class_exists( 'Babel' ) && !$this->getContext()->getUser()->isAnon() ) {
			$this->getOutput()->addWikiMsg( 'wikibase-mylanguagefallbackchain-babel',
				$this->getContext()->getUser()->getName() );
		}

		$inLanguage = $this->getContext()->getLanguage()->getCode();

		$this->getOutput()->addHTML( Html::openElement( 'ul' ) );

		foreach ( $this->getLanguageFallbackChain()->getFallbackChain() as $lang ) {
			$language = $lang->getLanguage();
			$sourceLanguage = $lang->getSourceLanguage();
			$languageName = Language::fetchLanguageName( $language->getCode(), $inLanguage );

			if ( $sourceLanguage ) {
				$sourceLanguageName = Language::fetchLanguageName( $sourceLanguage->getCode(), $inLanguage );
				$msgHtml = wfMessage( 'wikibase-mylanguagefallbackchain-converted-item' )->params(
					$language->getHtmlCode(), $languageName,
					$sourceLanguage->getHtmlCode(), $sourceLanguageName
				)->parse();
			} else {
				$msgHtml = wfMessage( 'wikibase-mylanguagefallbackchain-verbatim-item' )->params(
					$language->getHtmlCode(), $languageName
				)->parse();
			}

			$this->getOutput()->addHtml( Html::rawElement( 'li', array(), $msgHtml ) );
		}

		$this->getOutput()->addHTML( Html::closeElement( 'ul' ) );
	}

}
