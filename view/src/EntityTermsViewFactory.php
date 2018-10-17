<?php

namespace Wikibase\View;

use Language;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\View\Template\TemplateFactory;

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
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	public function __construct(
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LanguageNameLookup $languageNameLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		TemplateFactory $templateFactory
	) {
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->templateFactory = $templateFactory;
	}

	/**
	 * @param Language $language
	 * @param bool $isTermbox
	 *
	 * @return EntityTermsView
	 */
	public function newEntityTermsView( Language $language, $isTermbox ) : EntityTermsView {
		return $isTermbox ? $this->newTermboxView( $language )
			: $this->newPlaceHolderEmittingEntityTermsView( $language );
	}

	private function newPlaceHolderEmittingEntityTermsView( Language $language ) {
		$textProvider = new MediaWikiLocalizedTextProvider( $language->getCode() );
		return new PlaceholderEmittingEntityTermsView(
			new FallbackHintHtmlTermRenderer(
				$this->languageDirectionalityLookup,
				$this->languageNameLookup
			),
			$this->labelDescriptionLookupFactory->newLabelDescriptionLookup( $language ), // TODO does this need entity ids?
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
