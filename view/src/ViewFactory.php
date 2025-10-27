<?php

namespace Wikibase\View;

use InvalidArgumentException;
use MediaWiki\Language\Language;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Site\SiteLookup;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\NumberLocalizerFactory;
use Wikibase\Lib\Formatters\SnakFormat;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\LocalizedTextProviderFactory;
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
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

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
	 * @var LanguageNameLookupFactory
	 */
	private $languageNameLookupFactory;

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
	 * @var SpecialPageLinker
	 */
	private $specialPageLinker;

	/**
	 * @var NumberLocalizerFactory
	 */
	private $numberLocalizerFactory;

	/**
	 * @var LocalizedTextProviderFactory
	 */
	private $textProviderFactory;

	/**
	 * @var LanguageFactory
	 */
	private $languageFactory;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var Wbui2025FeatureFlag
	 */
	private Wbui2025FeatureFlag $wbui2025FeatureFlag;

	/**
	 * @param EntityIdFormatterFactory $htmlIdFormatterFactory
	 * @param EntityIdFormatterFactory $plainTextIdFormatterFactory
	 * @param HtmlSnakFormatterFactory $htmlSnakFormatterFactory
	 * @param StatementGrouper $statementGrouper
	 * @param SerializerFactory $serializerFactory
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param PropertyOrderProvider $propertyOrderProvider
	 * @param SiteLookup $siteLookup
	 * @param DataTypeFactory $dataTypeFactory
	 * @param TemplateFactory $templateFactory
	 * @param LanguageNameLookupFactory $languageNameLookupFactory
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param NumberLocalizerFactory $numberLocalizerFactory
	 * @param string[] $siteLinkGroups
	 * @param string[] $specialSiteLinkGroups
	 * @param string[] $badgeItems
	 * @param LocalizedTextProviderFactory $textProviderFactory
	 * @param SpecialPageLinker $specialPageLinker
	 * @param LanguageFactory $languageFactory
	 * @param EntityIdParser $entityIdParser
	 * @param Wbui2025FeatureFlag $wbui2025FeatureFlag
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdFormatterFactory $htmlIdFormatterFactory,
		EntityIdFormatterFactory $plainTextIdFormatterFactory,
		HtmlSnakFormatterFactory $htmlSnakFormatterFactory,
		StatementGrouper $statementGrouper,
		SerializerFactory $serializerFactory,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		PropertyOrderProvider $propertyOrderProvider,
		SiteLookup $siteLookup,
		DataTypeFactory $dataTypeFactory,
		TemplateFactory $templateFactory,
		LanguageNameLookupFactory $languageNameLookupFactory,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		NumberLocalizerFactory $numberLocalizerFactory,
		array $siteLinkGroups,
		array $specialSiteLinkGroups,
		array $badgeItems,
		LocalizedTextProviderFactory $textProviderFactory,
		SpecialPageLinker $specialPageLinker,
		LanguageFactory $languageFactory,
		EntityIdParser $entityIdParser,
		Wbui2025FeatureFlag $wbui2025FeatureFlag,
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
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->serializerFactory = $serializerFactory;
		$this->propertyOrderProvider = $propertyOrderProvider;
		$this->siteLookup = $siteLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->templateFactory = $templateFactory;
		$this->languageNameLookupFactory = $languageNameLookupFactory;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->numberLocalizerFactory = $numberLocalizerFactory;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->specialSiteLinkGroups = $specialSiteLinkGroups;
		$this->badgeItems = $badgeItems;
		$this->textProviderFactory = $textProviderFactory;
		$this->specialPageLinker = $specialPageLinker;
		$this->languageFactory = $languageFactory;
		$this->entityIdParser = $entityIdParser;
		$this->wbui2025FeatureFlag = $wbui2025FeatureFlag;
	}

	/**
	 * @param EntityIdFormatterFactory $factory
	 * @param string $expected
	 *
	 * @return bool
	 */
	private function hasValidOutputFormat( EntityIdFormatterFactory $factory, $expected ) {
		$snakFormat = new SnakFormat();
		switch ( $snakFormat->getBaseFormat( $factory->getOutputFormat() ) ) {
			case SnakFormatter::FORMAT_PLAIN:
				return $expected === 'text/plain';
			case SnakFormatter::FORMAT_HTML:
				return $expected === 'text/html';
		}

		return false;
	}

	/**
	 * Creates an ItemView suitable for rendering the item.
	 *
	 * @param Language $language
	 * @param TermLanguageFallbackChain $termFallbackChain
	 * @param CacheableEntityTermsView $entityTermsView
	 * @param array $viewOptions
	 *
	 * @return ItemView
	 */
	public function newItemView(
		Language $language,
		TermLanguageFallbackChain $termFallbackChain,
		CacheableEntityTermsView $entityTermsView,
		array $viewOptions = [],
	) {
		$textProvider = $this->textProviderFactory->getForLanguage( $language );
		$numberLocalizer = $this->numberLocalizerFactory->getForLanguage( $language );
		$editSectionGenerator = $this->newToolbarEditSectionGenerator( $textProvider );

		$statementSectionsView = $this->newStatementSectionsView(
			$language,
			$termFallbackChain,
			$editSectionGenerator,
			$viewOptions,
		);

		$siteLinksView = new SiteLinksView(
			$this->templateFactory,
			$this->siteLookup->getSites(),
			$editSectionGenerator,
			$this->plainTextIdFormatterFactory->getEntityIdFormatter( $language ),
			$this->languageNameLookupFactory->getForLanguage( $language ),
			$numberLocalizer,
			$this->badgeItems,
			$this->specialSiteLinkGroups,
			$textProvider
		);

		return new ItemView(
			$this->templateFactory,
			$entityTermsView,
			$this->languageDirectionalityLookup,
			$statementSectionsView,
			$language->getCode(),
			$siteLinksView,
			$this->siteLinkGroups,
			$textProvider,
			$viewOptions,
		);
	}

	/**
	 * Creates an PropertyView suitable for rendering the property.
	 *
	 * @param Language $language
	 * @param TermLanguageFallbackChain $termFallbackChain
	 * @param CacheableEntityTermsView $entityTermsView
	 * @param array $viewOptions
	 *
	 * @return PropertyView
	 */
	public function newPropertyView(
		Language $language,
		TermLanguageFallbackChain $termFallbackChain,
		CacheableEntityTermsView $entityTermsView,
		array $viewOptions = [],
	) {
		$textProvider = $this->textProviderFactory->getForLanguage( $language );
		$statementSectionsView = $this->newStatementSectionsView(
			$language,
			$termFallbackChain,
			$this->newToolbarEditSectionGenerator( $textProvider ),
			$viewOptions,
		);

		return new PropertyView(
			$this->templateFactory,
			$entityTermsView,
			$this->languageDirectionalityLookup,
			$statementSectionsView,
			$this->dataTypeFactory,
			$language->getCode(),
			$textProvider,
			$viewOptions,
		);
	}

	/**
	 * @param Language $language
	 * @param TermLanguageFallbackChain $termFallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 * @param array $viewOptions
	 *
	 * @return StatementSectionsView
	 */
	public function newStatementSectionsView(
		Language $language,
		TermLanguageFallbackChain $termFallbackChain,
		EditSectionGenerator $editSectionGenerator,
		array $viewOptions = [],
	) {
		$textProvider = $this->textProviderFactory->getForLanguage( $language );
		$statementGroupListView = $this->newStatementGroupListView(
			$language->getCode(),
			$termFallbackChain,
			$editSectionGenerator
		);
		$snakFormatter = $this->htmlSnakFormatterFactory->getSnakFormatter(
			$language->getCode(),
			$termFallbackChain
		);
		$vueNoScriptRendering = new VueNoScriptRendering(
			$this->htmlIdFormatterFactory,
			$this->entityIdParser,
			$language,
			$textProvider,
			$this->propertyDataTypeLookup,
			$this->serializerFactory,
			$snakFormatter,
		);

		return new StatementSectionsView(
			$this->templateFactory,
			$this->statementGrouper,
			$statementGroupListView,
			$textProvider,
			$vueNoScriptRendering,
			Wbui2025FeatureFlag::wbui2025EnabledForViewOptions( $viewOptions ),
		);
	}

	/**
	 * @param string $languageCode
	 * @param TermLanguageFallbackChain $termFallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return StatementGroupListView
	 */
	public function newStatementGroupListView(
		$languageCode,
		TermLanguageFallbackChain $termFallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		$textProvider = $this->textProviderFactory->getForLanguageCode( $languageCode );
		$numberLocalizer = $this->numberLocalizerFactory->getForLanguageCode( $languageCode );
		$snakFormatter = $this->htmlSnakFormatterFactory->getSnakFormatter(
			$languageCode,
			$termFallbackChain
		);
		$propertyIdFormatter = $this->htmlIdFormatterFactory->getEntityIdFormatter(
			$this->languageFactory->getLanguage( $languageCode )
		);
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->templateFactory,
			$snakFormatter,
			$propertyIdFormatter,
			$textProvider
		);
		$statementHtmlGenerator = new StatementHtmlGenerator(
			$this->templateFactory,
			$snakHtmlGenerator,
			$numberLocalizer,
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

	private function newToolbarEditSectionGenerator(
		LocalizedTextProvider $textProvider
	): ToolbarEditSectionGenerator {
		return new ToolbarEditSectionGenerator(
			$this->specialPageLinker,
			$this->templateFactory,
			$textProvider
		);
	}

}
