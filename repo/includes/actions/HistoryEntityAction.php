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
	 * @var LanguageFallbackChainFactory
	 */
	protected $languageFallbackChainFactory;

	/**
	 * Get the language fallback chain factory previously set, or the default one.
	 *
	 * @since 0.4
	 *
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		if ( $this->languageFallbackChainFactory === null ) {
			$this->languageFallbackChainFactory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
		}

		return $this->languageFallbackChainFactory;
	}

	/**
	 * Set language fallback chain factory and return the previously set one.
	 *
	 * @since 0.4
	 *
	 * @param LanguageFallbackChainFactory $factory
	 *
	 * @return LanguageFallbackChainFactory|null
	 */
	public function setLanguageFallbackChainFactory( LanguageFallbackChainFactory $factory ) {
		return wfSetVar( $this->languageFallbackChainFactory, $factory );
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

		$languageFallbackChain = $this->getLanguageFallbackChainFactory()->newFromContext( $this->getContext() );
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
