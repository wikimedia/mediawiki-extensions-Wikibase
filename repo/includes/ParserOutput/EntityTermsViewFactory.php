<?php

namespace Wikibase\Repo\ParserOutput;

use Language;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
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
	 * @param LanguageFallbackChain $fallbackChain
	 * @param bool $useTermbox
	 *
	 * @return CacheableEntityTermsView
	 */
	public function newEntityTermsView(
		EntityDocument $entity,
		Language $language,
		LanguageFallbackChain $fallbackChain,
		$useTermbox = false
	) {
		return $useTermbox ? $this->newTermboxView( $language )
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

	/**
	 * Note that this always builds a view with the main user interface language
	 * as the only parameter influencing the markup.
	 * This is because the objects created from this factory are assumed to
	 * write into ParserOutput which should not include any user-specific markup.
	 */
	private function newTermboxView( Language $language ) {
		$textProvider = new MediaWikiLocalizedTextProvider( $language );
		$repo = WikibaseRepo::getDefaultInstance();

		return new TermboxView(
			new LanguageFallbackChainFactory(),
			new TermboxRemoteRenderer(
				MediaWikiServices::getInstance()->getHttpRequestFactory(),
				$repo->getSettings()->getSetting( 'ssrServerUrl' ),
				$repo->getSettings()->getSetting( 'ssrServerTimeout' ),
				$repo->getLogger()
			),
			$textProvider,
			new RepoSpecialPageLinker(),
			new TextInjector()
		);
	}

}
