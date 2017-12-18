<?php

namespace Wikibase\Repo\Specials;

use ExtensionRegistry;
use Html;
use IContextSource;
use Language;
use SpecialPage;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;

/**
 * Page for displaying the current language fallback chain for debugging.
 *
 * @license GPL-2.0+
 * @author Liangent < liangent@gmail.com >
 */
class SpecialMyLanguageFallbackChain extends SpecialPage {

	/**
	 * @var LanguageFallbackChain
	 */
	private $chain;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory
	) {
		parent::__construct( 'MyLanguageFallbackChain' );

		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
	}

	/**
	 * @see SpecialPage::getGroupName
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'wikibase';
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->msg( 'special-mylanguagefallbackchain' )->text();
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
			$this->setLanguageFallbackChain(
				$this->languageFallbackChainFactory->newFromContext( $this->getContext() )
			);
		}

		return $this->chain;
	}

	/**
	 * Set a new chain for display and return the original one.
	 *
	 * @param LanguageFallbackChain $chain
	 *
	 * @return LanguageFallbackChain
	 */
	public function setLanguageFallbackChain( LanguageFallbackChain $chain ) {
		return wfSetVar( $this->chain, $chain );
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->outputHeader();

		$this->getOutput()->addWikiMsg( 'wikibase-mylanguagefallbackchain-text' );
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Babel' )
			&& !$this->getContext()->getUser()->isAnon()
		) {
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
				$msgHtml = wfMessage(
					'wikibase-mylanguagefallbackchain-converted-item',
					$language->getHtmlCode(),
					$languageName,
					$sourceLanguage->getHtmlCode(),
					$sourceLanguageName
				)->parse();
			} else {
				$msgHtml = wfMessage(
					'wikibase-mylanguagefallbackchain-verbatim-item',
					$language->getHtmlCode(),
					$languageName
				)->parse();
			}

			$this->getOutput()->addHTML( Html::rawElement( 'li', [], $msgHtml ) );
		}

		$this->getOutput()->addHTML( Html::closeElement( 'ul' ) );
	}

}
