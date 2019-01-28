<?php

namespace Wikibase\Repo\ParserOutput;

use Language;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Termbox\Renderer\TermboxRemoteRenderer;
use Wikibase\View\Termbox\TermboxView;
use Wikibase\View\TermsListView;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * @license GPL-2.0-or-later
 */
class EntityTermsViewFactory {

	/**
	 * @param EntityDocument $entity
	 * @param Language $language
	 * @param LanguageFallbackChain $fallbackChain
	 * @param LanguageFallbackChain $userLanguages - user-specific, likely derived from Babel preferences etc.
	 * @param bool $useTermbox
	 *
	 * @return CacheableEntityTermsView
	 */
	public function newEntityTermsView(
		EntityDocument $entity,
		Language $language,
		LanguageFallbackChain $fallbackChain,
		LanguageFallbackChain $userLanguages,
		$useTermbox = false
	) {
		return $useTermbox ? $this->newTermboxView( $language, $userLanguages )
			: $this->newPlaceHolderEmittingEntityTermsView( $entity, $language, $fallbackChain );
	}

	private function newPlaceHolderEmittingEntityTermsView(
		EntityDocument $entity,
		Language $language,
		LanguageFallbackChain $fallbackChain
	) {
		$textProvider = new MediaWikiLocalizedTextProvider( $language );
		$templateFactory = TemplateFactory::getDefaultInstance();
		$languageDirectionalityLookup = new MediaWikiLanguageDirectionalityLookup();
		$languageNameLookup = new LanguageNameLookup( $language->getCode() );
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
				$fallbackChain
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

	private function newTermboxView( Language $language, LanguageFallbackChain $fallbackChain ) {
		$textProvider = new MediaWikiLocalizedTextProvider( $language );

		return new TermboxView(
			$fallbackChain,
			new TermboxRemoteRenderer(
				MediaWikiServices::getInstance()->getHttpRequestFactory(),
				WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'ssrServerUrl' )
			),
			$textProvider,
			new RepoSpecialPageLinker()
		);
	}

}
