<?php

namespace Wikibase\View;

use Language;
use Wikibase\Lib\DataTypeFactory;
use InvalidArgumentException;
use SiteLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\MediaWikiNumberLocalizer;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * This is a basic factory to create views for DataModel objects. It contains all dependencies of
 * the views besides request-specific options. Those are required in the parameters.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
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
	 * @var PropertyOrderProvider
	 */
	private $propertyOrderProvider;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

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
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

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
	 * @param PropertyOrderProvider $propertyOrderProvider
	 * @param SiteLookup $siteLookup
	 * @param DataTypeFactory $dataTypeFactory
	 * @param TemplateFactory $templateFactory
	 * @param LanguageNameLookup $languageNameLookup
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
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
		PropertyOrderProvider $propertyOrderProvider,
		SiteLookup $siteLookup,
		DataTypeFactory $dataTypeFactory,
		TemplateFactory $templateFactory,
		LanguageNameLookup $languageNameLookup,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		array $siteLinkGroups = [],
		array $specialSiteLinkGroups = [],
		array $badgeItems = []
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
		$this->propertyOrderProvider = $propertyOrderProvider;
		$this->siteLookup = $siteLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->templateFactory = $templateFactory;
		$this->languageNameLookup = $languageNameLookup;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
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
				return $expected === 'text/html';
		}

		return false;
	}

	/**
	 * Creates an ItemView suitable for rendering the item.
	 *
	 * @param string $languageCode UI language
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 * @param EntityTermsView $entityTermsView
	 *
	 * @return ItemView
	 */
	public function newItemView(
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator,
		EntityTermsView $entityTermsView
	) {
		$statementSectionsView = $this->newStatementSectionsView(
			$languageCode,
			$labelDescriptionLookup,
			$fallbackChain,
			$editSectionGenerator
		);

		$textProvider = new MediaWikiLocalizedTextProvider( $languageCode );

		$siteLinksView = new SiteLinksView(
			$this->templateFactory,
			$this->siteLookup->getSites(),
			$editSectionGenerator,
			$this->plainTextIdFormatterFactory->getEntityIdFormatter( $labelDescriptionLookup ),
			$this->languageNameLookup,
			new MediaWikiNumberLocalizer( Language::factory( $languageCode ) ),
			$this->badgeItems,
			$this->specialSiteLinkGroups,
			$textProvider
		);

		return new ItemView(
			$this->templateFactory,
			$entityTermsView,
			$this->languageDirectionalityLookup,
			$statementSectionsView,
			$languageCode,
			$siteLinksView,
			$this->siteLinkGroups,
			$textProvider
		);
	}

	/**
	 * Creates an PropertyView suitable for rendering the property.
	 *
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 * @param EntityTermsView $entityTermsView
	 *
	 * @return PropertyView
	 */
	public function newPropertyView(
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator,
		EntityTermsView $entityTermsView
	) {
		$statementSectionsView = $this->newStatementSectionsView(
			$languageCode,
			$labelDescriptionLookup,
			$fallbackChain,
			$editSectionGenerator
		);

		return new PropertyView(
			$this->templateFactory,
			$entityTermsView,
			$this->languageDirectionalityLookup,
			$statementSectionsView,
			$this->dataTypeFactory,
			$languageCode,
			new MediaWikiLocalizedTextProvider( $languageCode )
		);
	}

	/**
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return StatementSectionsView
	 */
	public function newStatementSectionsView(
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		$statementGroupListView = $this->newStatementGroupListView(
			$languageCode,
			$labelDescriptionLookup,
			$fallbackChain,
			$editSectionGenerator
		);

		return new StatementSectionsView(
			$this->templateFactory,
			$this->statementGrouper,
			$statementGroupListView,
			new MediaWikiLocalizedTextProvider( $languageCode )
		);
	}

	/**
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return StatementGroupListView
	 */
	public function newStatementGroupListView(
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		$snakFormatter = $this->htmlSnakFormatterFactory->getSnakFormatter(
			$languageCode,
			$fallbackChain,
			$labelDescriptionLookup
		);
		$propertyIdFormatter = $this->htmlIdFormatterFactory->getEntityIdFormatter(
			$labelDescriptionLookup
		);

		$textProvider = new MediaWikiLocalizedTextProvider( $languageCode );

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->templateFactory,
			$snakFormatter,
			$propertyIdFormatter,
			$textProvider
		);
		$statementHtmlGenerator = new StatementHtmlGenerator(
			$this->templateFactory,
			$snakHtmlGenerator,
			new MediaWikiNumberLocalizer( Language::factory( $languageCode ) ),
			$textProvider
		);

		return new StatementGroupListView(
			$this->propertyOrderProvider,
			$this->templateFactory,
			$propertyIdFormatter,
			$editSectionGenerator,
			$statementHtmlGenerator
		);
	}

}
