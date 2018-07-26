<?php

namespace Wikibase\Repo\ParserOutput;

use Language;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * @license GPL-2.0-or-later
 */
class EntityTermsViewFactory {

	public function newEntityTermsView(
		EntityDocument $entity,
		Language $language,
		LanguageFallbackChain $fallbackChain
	) {
		return $this->newPlaceHolderEmittingEntityTermsView( $entity, $language, $fallbackChain );
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

}
