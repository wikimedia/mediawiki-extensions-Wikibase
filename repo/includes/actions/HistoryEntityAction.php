<?php
namespace Wikibase;

use Wikibase\Repo\WikibaseRepo;

/**
 * Handles the history action for Wikibase entities.
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class HistoryEntityAction extends \HistoryAction {

	/**
	 * @var LanguageFallbackChain
	 */
	protected $languageFallbackChain;

	/**
	 * Get the language fallback chain for current context.
	 *
	 * @since 0.4
	 *
	 * @return LanguageFallbackChain
	 */
	public function getLanguageFallbackChain() {
		if ( $this->languageFallbackChain === null ) {
			$this->languageFallbackChain = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory()
				->newFromContext( $this->getContext() );
		}

		return $this->languageFallbackChain;
	}

	/**
	 * Set language fallback chain.
	 *
	 * @since 0.4
	 *
	 * @param LanguageFallbackChain $chain
	 */
	public function setLanguageFallbackChain( LanguageFallbackChain $chain ) {
		$this->languageFallbackChain = $chain;
	}

	/**
	 * Returns the content of the page being viewed.
	 *
	 * @since 0.3
	 *
	 * @return EntityContent|null
	 */
	protected function getContent() {
		return $this->getArticle()->getPage()->getContent();
	}

	/**
	 * Return a string for use as title.
	 *
	 * @since 0.3
	 *
	 * @return \Article
	 */
	protected function getPageTitle() {
		$content = $this->getContent();

		if ( !$content ) {
			// page does not exist
			return parent::getPageTitle();
		}

		$entity = $content->getEntity();

		$languageFallbackChain = $this->getLanguageFallbackChain();
		$labelData = $languageFallbackChain->extractPreferredValueOrAny( $content->getEntity()->getLabels() );

		if ( $labelData ) {
			$labelText = $labelData['value'];
		} else {
			$labelText = null;
		}

		$idPrefixer = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$prefixedId = ucfirst( $idPrefixer->format( $entity->getId() ) );

		if ( isset( $labelText ) ) {
			return $this->msg( 'wikibase-history-title-with-label', $prefixedId, $labelText )->text();
		}
		else {
			return $this->msg( 'wikibase-history-title-without-label', $prefixedId )->text();
		}
	}
}
