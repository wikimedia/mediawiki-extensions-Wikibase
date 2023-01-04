<?php

namespace Wikibase\Repo\ParserOutput;

use Language;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Termbox\Renderer\TermboxRemoteRenderer;
use Wikibase\View\TermsListView;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * @license GPL-2.0-or-later
 */
class EntityTermsViewFactory {

	/**
	 * @param EntityDocument $entity
	 * @param Language $language
	 * @param TermLanguageFallbackChain $termFallbackChain
	 * @param bool $useTermbox
	 *
	 * @return CacheableEntityTermsView
	 */
	public function newEntityTermsView(
		EntityDocument $entity,
		Language $language,
		TermLanguageFallbackChain $termFallbackChain,
		$useTermbox = false
	) {
		// FIXME: Hack introduced for T230937 - preventing trying to use remote termbox when entity hasn't been saved
		return $useTermbox && $entity->getId() ? $this->newTermboxView( $language )
			: $this->newPlaceHolderEmittingEntityTermsView( $entity, $language, $termFallbackChain );
	}

	private function newPlaceHolderEmittingEntityTermsView(
		EntityDocument $entity,
		Language $language,
		TermLanguageFallbackChain $termFallbackChain
	) {
		$services = MediaWikiServices::getInstance();
		$textProvider = new MediaWikiLocalizedTextProvider( $language );
		$templateFactory = TemplateFactory::getDefaultInstance();
		$languageDirectionalityLookup = WikibaseRepo::getLanguageDirectionalityLookup( $services );
		$languageNameLookup = WikibaseRepo::getLanguageNameLookupFactory()->getForLanguage( $language );
		$entityLookup = new InMemoryEntityLookup();
		if ( $entity->getId() !== null ) {
			$entityLookup->addEntity( $entity );
		}

		return new PlaceholderEmittingEntityTermsView(
			new FallbackHintHtmlTermRenderer(
				$languageDirectionalityLookup,
				$languageNameLookup
			),
			new LanguageFallbackLabelDescriptionLookup(
				new EntityRetrievingTermLookup( $entityLookup ),
				$termFallbackChain
			),
			$templateFactory,
			new ToolbarEditSectionGenerator(
				new RepoSpecialPageLinker(),
				$templateFactory,
				$textProvider
			),
			$textProvider,
			new TermsListView(
				$templateFactory,
				$languageNameLookup,
				$textProvider,
				$languageDirectionalityLookup
			),
			new TextInjector()
		);
	}

	/**
	 * Note that this always builds a view with the main user interface language
	 * as the only parameter influencing the markup.
	 * This is because the objects created from this factory are assumed to
	 * write into ParserOutput which should not include any user-specific markup.
	 */
	private function newTermboxView( Language $language ) {
		$textProvider = new MediaWikiLocalizedTextProvider( $language );
		$services = MediaWikiServices::getInstance();
		$repoSettings = WikibaseRepo::getSettings( $services );

		return new TermboxView(
			WikibaseRepo::getLanguageFallbackChainFactory( $services ),
			new TermboxRemoteRenderer(
				$services->getHttpRequestFactory(),
				$repoSettings->getSetting( 'ssrServerUrl' ),
				$repoSettings->getSetting( 'ssrServerTimeout' ),
				WikibaseRepo::getLogger( $services ),
				$services->getStatsdDataFactory()
			),
			$textProvider,
			new RepoSpecialPageLinker(),
			new TextInjector()
		);
	}

}
