<?php

namespace Wikibase\View;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Language;
use SiteStore;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\Template\TemplateFactory;

/**
 * This is a basic factory to create views for DataModel objects. It contains all dependencies of
 * the views besides request-specific options. Those are required in the parameters.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ViewFactory {

	/**
	 * @var HtmlSnakFormatterFactory
	 */
	private $htmlSnakFormatterFactory;

	/**
	 * @var EntityIdFormatterFactory
	 */
	private $htmlIdFormatterFactory;

	/**
	 * @var EntityIdFormatterFactory
	 */
	private $plainTextIdFormatterFactory;

	/**
	 * @var StatementGrouper
	 */
	private $statementGrouper;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var string[]
	 */
	private $specialSiteLinkGroups;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @param EntityIdFormatterFactory $htmlIdFormatterFactory
	 * @param EntityIdFormatterFactory $plainTextIdFormatterFactory
	 * @param HtmlSnakFormatterFactory $htmlSnakFormatterFactory
	 * @param StatementGrouper $statementGrouper
	 * @param SiteStore $siteStore
	 * @param DataTypeFactory $dataTypeFactory
	 * @param TemplateFactory $templateFactory
	 * @param LanguageNameLookup $languageNameLookup
	 * @param string[] $siteLinkGroups
	 * @param string[] $specialSiteLinkGroups
	 * @param string[] $badgeItems
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
		array $siteLinkGroups = array(),
		array $specialSiteLinkGroups = array(),
		array $badgeItems = array()
	) {
		if ( !$this->hasValidOutputFormat( $htmlIdFormatterFactory, 'text/html' )
			|| !$this->hasValidOutputFormat( $plainTextIdFormatterFactory, 'text/plain' )
		) {
			throw new InvalidArgumentException( 'Expected an HTML and a plain text EntityIdFormatter factory' );
		}

		$this->htmlIdFormatterFactory = $htmlIdFormatterFactory;
		$this->plainTextIdFormatterFactory = $plainTextIdFormatterFactory;
		$this->htmlSnakFormatterFactory = $htmlSnakFormatterFactory;
		$this->statementGrouper = $statementGrouper;
		$this->siteStore = $siteStore;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->templateFactory = $templateFactory;
		$this->languageNameLookup = $languageNameLookup;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->specialSiteLinkGroups = $specialSiteLinkGroups;
		$this->badgeItems = $badgeItems;
	}

	/**
	 * @param EntityIdFormatterFactory $factory
	 * @param string $expected
	 *
	 * @return bool
	 */
	private function hasValidOutputFormat( EntityIdFormatterFactory $factory, $expected ) {
		switch ( $factory->getOutputFormat() ) {
			case SnakFormatter::FORMAT_PLAIN:
				return $expected === 'text/plain';

			case SnakFormatter::FORMAT_HTML:
			case SnakFormatter::FORMAT_HTML_DIFF:
			case SnakFormatter::FORMAT_HTML_WIDGET:
				return $expected === 'text/html';
		}

		return false;
	}

	/**
	 * Creates an ItemView suitable for rendering the item.
	 *
	 * @param string $uiLanguageCode UI language
	 * @param string $contentLanguageCode Content language
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return ItemView
	 */
	public function newItemView(
		$uiLanguageCode,
		$contentLanguageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		$entityTermsView = $this->newEntityTermsView( $uiLanguageCode, $editSectionGenerator );

		$statementSectionsView = $this->newStatementSectionsView(
			$uiLanguageCode,
			$labelDescriptionLookup,
			$fallbackChain,
			$editSectionGenerator
		);

		// @fixme all that seems needed in ItemView is language code and dir.
		$uiLanguage = Language::factory( $uiLanguageCode );

		$siteLinksView = new SiteLinksView(
			$this->templateFactory,
			$this->siteStore->getSites(),
			$editSectionGenerator,
			$this->plainTextIdFormatterFactory->getEntityIdFormatter( $labelDescriptionLookup ),
			$this->languageNameLookup,
			$this->badgeItems,
			$this->specialSiteLinkGroups
		);

		return new ItemView(
			$this->templateFactory,
			$entityTermsView,
			$statementSectionsView,
			$uiLanguage,
			$contentLanguageCode,
			$siteLinksView,
			$this->siteLinkGroups
		);
	}

	/**
	 * Creates an PropertyView suitable for rendering the property.
	 *
	 * @param string $uiLanguageCode UI language
	 * @param string $contentLanguageCode Content language
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return PropertyView
	 */
	public function newPropertyView(
		$uiLanguageCode,
		$contentLanguageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		$entityTermsView = $this->newEntityTermsView( $uiLanguageCode, $editSectionGenerator );

		$statementSectionsView = $this->newStatementSectionsView(
			$uiLanguageCode,
			$labelDescriptionLookup,
			$fallbackChain,
			$editSectionGenerator
		);

		// @fixme all that seems needed in PropertyView is language code and dir.
		$uiLanguage = Language::factory( $uiLanguageCode );

		return new PropertyView(
			$this->templateFactory,
			$entityTermsView,
			$statementSectionsView,
			$this->dataTypeFactory,
			$uiLanguage,
			$contentLanguageCode
		);
	}

	/**
	 * @param string $uiLanguageCode
	 * @param LanguageFallbackChain $fallbackChain
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return StatementSectionsView
	 */
	private function newStatementSectionsView(
		$uiLanguageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		$snakFormatter = $this->htmlSnakFormatterFactory->getSnakFormatter(
			$uiLanguageCode,
			$fallbackChain,
			$labelDescriptionLookup
		);
		$propertyIdFormatter = $this->htmlIdFormatterFactory->getEntityIdFormatter(
			$labelDescriptionLookup
		);
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->templateFactory,
			$snakFormatter,
			$propertyIdFormatter
		);
		$statementGroupListView = new StatementGroupListView(
			$this->templateFactory,
			$propertyIdFormatter,
			$editSectionGenerator,
			new ClaimHtmlGenerator( $this->templateFactory, $snakHtmlGenerator )
		);

		return new StatementSectionsView(
			$this->templateFactory,
			$this->statementGrouper,
			$statementGroupListView
		);
	}

	/**
	 * @param string $uiLanguageCode
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return EntityTermsView
	 */
	private function newEntityTermsView( $uiLanguageCode, EditSectionGenerator $editSectionGenerator ) {
		return new EntityTermsView(
			$this->templateFactory,
			$editSectionGenerator,
			$this->languageNameLookup,
			$uiLanguageCode
		);
	}

}
