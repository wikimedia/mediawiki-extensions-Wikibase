<?php

namespace Wikibase\View;

use Language;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\View\Template\TemplateFactory;

/**
 * @license GPL-2.0-or-later
 */
class EntityTermsViewFactory {

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	public function __construct(
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LanguageNameLookup $languageNameLookup,
		TemplateFactory $templateFactory
	) {
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->templateFactory = $templateFactory;
	}

	/**
	 * @param Language $language
	 * @param bool $isTermbox
	 *
	 * @return EntityTermsView
	 */
	public function newEntityTermsView(
		EntityDocument $entity,
		Language $language,
		LanguageFallbackChain $fallbackChain,
		$isTermbox
	) : EntityTermsView {
		return $isTermbox ? $this->newTermboxView( $language )
			: $this->newPlaceHolderEmittingEntityTermsView( $entity, $language, $fallbackChain );
	}

	private function newPlaceHolderEmittingEntityTermsView(
		EntityDocument $entity,
		Language $language,
		LanguageFallbackChain $fallbackChain
	) {
		$textProvider = new MediaWikiLocalizedTextProvider( $language->getCode() );
		$entityLookup = new InMemoryEntityLookup();
		if ( $entity->getId() !== null ) {
			$entityLookup->addEntity( $entity );
		}

		return new PlaceholderEmittingEntityTermsView(
			new FallbackHintHtmlTermRenderer(
				$this->languageDirectionalityLookup,
				$this->languageNameLookup
			),
			new LanguageFallbackLabelDescriptionLookup(
				new EntityRetrievingTermLookup( $entityLookup ),
				$fallbackChain
			),
			$this->templateFactory,
			new ToolbarEditSectionGenerator(
				new RepoSpecialPageLinker(),
				$this->templateFactory,
				$textProvider
			),
			$textProvider,
			new TermsListView(
				$this->templateFactory,
				$this->languageNameLookup,
				$textProvider,
				$this->languageDirectionalityLookup
			),
			new TextInjector()
		);
	}

	private function newTermboxView( $language ) {
		return new TermboxView();
	}

}
