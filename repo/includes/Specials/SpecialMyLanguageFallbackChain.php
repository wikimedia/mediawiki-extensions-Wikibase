<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Specials;

use ExtensionRegistry;
use Html;
use IContextSource;
use MediaWiki\Languages\LanguageNameUtils;
use SpecialPage;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * Page for displaying the current language fallback chain for debugging.
 *
 * @license GPL-2.0-or-later
 * @author Liangent < liangent@gmail.com >
 */
class SpecialMyLanguageFallbackChain extends SpecialPage {

	/**
	 * @var TermLanguageFallbackChain
	 */
	private $chain;

	/**
	 * @var LanguageNameUtils
	 */
	private $languageNameUtils;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	public function __construct(
		LanguageNameUtils $languageNameUtils,
		LanguageFallbackChainFactory $languageFallbackChainFactory
	) {
		parent::__construct( 'MyLanguageFallbackChain' );

		$this->languageNameUtils = $languageNameUtils;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
	}

	/** @inheritDoc */
	protected function getGroupName(): string {
		return 'wikibase';
	}

	/** @inheritDoc */
	public function getDescription(): string {
		return $this->msg( 'special-mylanguagefallbackchain' )->text();
	}

	/**
	 * @param IContextSource $context
	 */
	public function setContext( $context ): void {
		$this->chain = null;
		parent::setContext( $context );
	}

	/**
	 * Get the chain stored for display.
	 */
	public function getLanguageFallbackChain(): TermLanguageFallbackChain {
		if ( $this->chain === null ) {
			$this->chain = $this->languageFallbackChainFactory->newFromContext( $this->getContext() );
		}

		return $this->chain;
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ): void {
		$this->setHeaders();
		$this->outputHeader();

		$this->getOutput()->addWikiMsg( 'wikibase-mylanguagefallbackchain-text' );
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Babel' )
			&& $this->getContext()->getUser()->isRegistered()
		) {
			$this->getOutput()->addWikiMsg( 'wikibase-mylanguagefallbackchain-babel',
				$this->getContext()->getUser()->getName() );
		}

		$inLanguage = $this->getContext()->getLanguage()->getCode();

		$this->getOutput()->addHTML( Html::openElement( 'ul' ) );

		foreach ( $this->getLanguageFallbackChain()->getFallbackChain() as $lang ) {
			$language = $lang->getLanguage();
			$sourceLanguage = $lang->getSourceLanguage();
			$languageName = $this->languageNameUtils->getLanguageName( $language->getCode(), $inLanguage );

			if ( $sourceLanguage ) {
				$sourceLanguageName = $this->languageNameUtils
					->getLanguageName( $sourceLanguage->getCode(), $inLanguage );
				$msgHtml = $this->msg(
					'wikibase-mylanguagefallbackchain-converted-item',
					$language->getHtmlCode(),
					$languageName,
					$sourceLanguage->getHtmlCode(),
					$sourceLanguageName
				)->parse();
			} else {
				$msgHtml = $this->msg(
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
