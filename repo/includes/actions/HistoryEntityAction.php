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

		$languageFallbackChainFactory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
		$languageFallbackChain = $languageFallbackChainFactory->newFromContext( $this->getContext() );
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
