<?php

use \Language;
use Wikibase\LanguageFallbackChain;

/**
 * Page for displaying the current language fallback chain for debugging.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 */
class SpecialMyLanguageFallbackChain extends SpecialPage {

	/**
	 * @var LanguageFallbackChain
	 * @since 0.4
	 */
	private $chain;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'MyLanguageFallbackChain' );
		$this->setLanguageFallbackChain( LanguageFallbackChain::newFromContext( $this->getContext() ) );
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @since 0.4
	 * @return String
	 */
	public function getDescription() {
		return $this->msg( 'special-' . strtolower( $this->getName() ) )->text();
	}

	/**
	 * Get the chain stored for display.
	 *
	 * @return LanguageFallbackChain
	 */
	public function getLanguageFallbackChain() {
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
			$this->getOutput()->addWikiMsg( 'wikibase-mylanguagefallbackchain-babel' );
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
