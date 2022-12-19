<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\HtmlTermRenderer;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SimpleEntityTermsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;

/**
 * An EntityTermsView that returns placeholders for some parts of the HTML
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class PlaceholderEmittingEntityTermsView extends SimpleEntityTermsView implements CacheableEntityTermsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var TermsListView
	 */
	private $termsListView;

	/**
	 * @var TextInjector
	 */
	private $textInjector;

	public const TERMBOX_VERSION = 1;

	/**
	 * This is used as a value in the ParserCache `termboxVersion` entry, prefixed with TERMBOX_VERSION.
	 *
	 * Note: It's currently set to '' to avoid unnecessarily purging caches. Starting from the next time this is
	 * changed it should be a number, incremented every time the caches need to be purged.
	 */
	public const CACHE_VERSION = '';

	public function __construct(
		HtmlTermRenderer $htmlTermRenderer,
		LabelDescriptionLookup $labelDescriptionLookup,
		TemplateFactory $templateFactory,
		EditSectionGenerator $sectionEditLinkGenerator,
		LocalizedTextProvider $textProvider,
		TermsListView $termsListView,
		TextInjector $textInjector
	) {
		parent::__construct(
			$htmlTermRenderer,
			$labelDescriptionLookup,
			$templateFactory,
			$sectionEditLinkGenerator,
			$termsListView,
			$textProvider
		);
		$this->templateFactory = $templateFactory;
		$this->termsListView = $termsListView;
		$this->textInjector = $textInjector;
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param AliasGroupList|null $aliasGroups
	 * @param EntityId|null $entityId the id of the entity
	 *
	 * @return string HTML
	 */
	public function getHtml(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null,
		EntityId $entityId = null
	) {
		$cssClasses = $this->textInjector->newMarker(
			'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class'
		);

		return $this->templateFactory->render(
			'wikibase-entitytermsview',
			$this->getHeadingHtml( $mainLanguageCode, $entityId, $aliasGroups ),
			$this->textInjector->newMarker( 'termbox' ),
			$cssClasses,
			$this->getHtmlForLabelDescriptionAliasesEditSection( $mainLanguageCode, $entityId )
		);
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param AliasGroupList|null $aliasGroups
	 *
	 * @return string[] HTML snippets
	 */
	public function getTermsListItems(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null
	) {
		$termsListItems = [];

		$termsListLanguages = $this->getTermsLanguageCodes(
			$mainLanguageCode,
			$labels,
			$descriptions,
			$aliasGroups ?: new AliasGroupList()
		);

		foreach ( $termsListLanguages as $languageCode ) {
			$termsListItems[ $languageCode ] = $this->termsListView->getListItemHtml(
				$labels,
				$descriptions,
				$aliasGroups,
				$languageCode
			);
		}

		return $termsListItems;
	}

	/**
	 * @inheritDoc
	 * @suppress PhanUndeclaredMethod
	 */
	public function getPlaceholders(
		EntityDocument $entity,
		$revision,
		$languageCode
	) {
		return [
			'wikibase-view-chunks' =>
				$this->textInjector->getMarkers(),
			'wikibase-terms-list-items' =>
				$this->getTermsListItems(
					$languageCode,
					$entity->getLabels(),
					$entity->getDescriptions(),
					$entity->getAliasGroups()
				),
		];
	}

}
