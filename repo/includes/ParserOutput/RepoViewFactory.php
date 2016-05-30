<?php

namespace Wikibase\Repo\ParserOutput;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use SiteStore;
use ValueFormatters\NumberLocalizer;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\HtmlSnakFormatterFactory;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;
use Wikibase\View\ViewFactory;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class RepoViewFactory extends ViewFactory {

	/**
	 * @var TextInjector
	 */
	private $textInjector;

	/**
	 * @param EntityIdFormatterFactory $htmlIdFormatterFactory
	 * @param EntityIdFormatterFactory $plainTextIdFormatterFactory
	 * @param HtmlSnakFormatterFactory $htmlSnakFormatterFactory
	 * @param StatementGrouper $statementGrouper
	 * @param SiteStore $siteStore
	 * @param DataTypeFactory $dataTypeFactory
	 * @param TemplateFactory $templateFactory
	 * @param LanguageNameLookup $languageNameLookup
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param NumberLocalizer $numberLocalizer
	 * @param string[] $siteLinkGroups
	 * @param string[] $specialSiteLinkGroups
	 * @param string[] $badgeItems
	 * @param LocalizedTextProvider $textProvider
	 * @param TextInjector $textInjector
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdFormatterFactory $htmlIdFormatterFactory,
		EntityIdFormatterFactory $plainTextIdFormatterFactory,
		HtmlSnakFormatterFactory $htmlSnakFormatterFactory,
		StatementGrouper $statementGrouper,
		SiteStore $siteStore,
		DataTypeFactory $dataTypeFactory,
		TemplateFactory $templateFactory,
		LanguageNameLookup $languageNameLookup,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		NumberLocalizer $numberLocalizer,
		array $siteLinkGroups = array(),
		array $specialSiteLinkGroups = array(),
		array $badgeItems = array(),
		LocalizedTextProvider $textProvider,
		TextInjector $textInjector,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		parent::__construct(
			$htmlIdFormatterFactory,
			$plainTextIdFormatterFactory,
			$htmlSnakFormatterFactory,
			$statementGrouper,
			$siteStore,
			$dataTypeFactory,
			$templateFactory,
			$languageNameLookup,
			$languageDirectionalityLookup,
			$numberLocalizer,
			$siteLinkGroups,
			$specialSiteLinkGroups,
			$badgeItems,
			$textProvider
		);
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->templateFactory = $templateFactory;
		$this->textInjector = $textInjector;
		$this->textProvider = $textProvider;
	}

	/**
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return EntityTermsView
	 */
	public function newEntityTermsView( EditSectionGenerator $editSectionGenerator ) {
		return new PlaceholderEmittingEntityTermsView(
			new FallbackHintHtmlTermRenderer(
				$this->languageDirectionalityLookup,
				$this->languageNameLookup
			),
			$this->labelDescriptionLookup,
			$this->templateFactory,
			$editSectionGenerator,
			$this->textProvider,
			new TermsListView(
				$this->templateFactory,
				$this->languageNameLookup,
				$this->textProvider,
				$this->languageDirectionalityLookup
			),
			$this->textInjector
		);
	}

}
