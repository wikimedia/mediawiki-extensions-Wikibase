<?php

namespace Wikibase\Repo\View;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Language;
use SiteStore;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdFormatterFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Template\TemplateFactory;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityViewFactory {

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var EntityIdFormatterFactory
	 */
	private $idFormatterFactory;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

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
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param EntityIdFormatterFactory $idFormatterFactory
	 * @param OutputFormatSnakFormatterFactory $snakFormatterFactory
	 * @param EntityLookup $entityLookup
	 * @param SiteStore $siteStore
	 * @param DataTypeFactory $dataTypeFactory
	 * @param TemplateFactory $templateFactory
	 * @param LanguageNameLookup $languageNameLookup
	 * @param string[] $siteLinkGroups
	 * @param string[] $specialSiteLinkGroups
	 * @param string[] $badgeItems
	 */
	public function __construct(
		EntityIdFormatterFactory $idFormatterFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		EntityLookup $entityLookup,
		SiteStore $siteStore,
		DataTypeFactory $dataTypeFactory,
		TemplateFactory $templateFactory,
		LanguageNameLookup $languageNameLookup,
		array $siteLinkGroups,
		array $specialSiteLinkGroups,
		array $badgeItems
	) {
		$this->checkOutputFormat( $idFormatterFactory->getOutputFormat() );

		$this->idFormatterFactory = $idFormatterFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->entityLookup = $entityLookup;
		$this->siteStore = $siteStore;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->specialSiteLinkGroups = $specialSiteLinkGroups;
		$this->badgeItems = $badgeItems;
		$this->templateFactory = $templateFactory;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator(
			$this->templateFactory
		);
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @param string $format
	 *
	 * @throws InvalidArgumentException
	 */
	private function checkOutputFormat( $format ) {
		if ( $format !== SnakFormatter::FORMAT_HTML
			&& $format !== SnakFormatter::FORMAT_HTML_DIFF
			&& $format !== SnakFormatter::FORMAT_HTML_WIDGET
		) {
			throw new InvalidArgumentException( 'HTML format expected, got ' . $format );
		}
	}

	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @param string $entityType
	 * @param string $languageCode
	 * @param LabelLookup $labelLookup
	 * @param LanguageFallbackChain|null $fallbackChain
	 * @param bool $editable
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	public function newEntityView(
		$entityType,
		$languageCode,
		LabelLookup $labelLookup,
		LanguageFallbackChain $fallbackChain = null,
		$editable = true
	 ) {
		$entityTermsView = $this->newEntityTermsView( $languageCode );
		$statementGroupListView = $this->newStatementGroupListView(
			$languageCode,
			$fallbackChain,
			$labelLookup
		);

		// @fixme all that seems needed in EntityView is language code and dir.
		$language = Language::factory( $languageCode );

		// @fixme support more entity types
		switch ( $entityType ) {
			case 'item':
				$siteLinksView = new SiteLinksView(
					$this->templateFactory,
					$this->siteStore->getSites(),
					$this->sectionEditLinkGenerator,
					$this->entityLookup,
					$this->languageNameLookup,
					$this->badgeItems,
					$this->specialSiteLinkGroups,
					$language->getCode()
				);

				return new ItemView(
					$this->templateFactory,
					$entityTermsView,
					$statementGroupListView,
					$language,
					$siteLinksView,
					$this->siteLinkGroups,
					$editable
				);
			case 'property':
				return new PropertyView(
					$this->templateFactory,
					$entityTermsView,
					$statementGroupListView,
					$this->dataTypeFactory,
					$language,
					$editable
				);
		}

		throw new InvalidArgumentException( 'No EntityView for entity type: ' . $entityType );
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $fallbackChain
	 * @param LabelLookup $labelLookup
	 *
	 * @return StatementGroupListView
	 */
	private function newStatementGroupListView(
		$languageCode,
		LanguageFallbackChain $fallbackChain = null,
		LabelLookup $labelLookup
	) {
		$propertyIdFormatter = $this->getPropertyIdFormatter( $labelLookup );

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->templateFactory,
			$this->getSnakFormatter( $languageCode, $fallbackChain, $labelLookup ),
			$propertyIdFormatter
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$this->templateFactory,
			$snakHtmlGenerator
		);

		return new StatementGroupListView(
			$this->templateFactory,
			$propertyIdFormatter,
			$this->sectionEditLinkGenerator,
			$claimHtmlGenerator
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return EntityTermsView
	 */
	private function newEntityTermsView( $languageCode ) {
		return new EntityTermsView(
			$this->templateFactory,
			$this->sectionEditLinkGenerator,
			$this->languageNameLookup,
			$languageCode
		);
	}

	/**
	 * @param $languageCode
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param LabelLookup $labelLookup
	 *
	 * @return FormatterOptions
	 */
	private function getFormatterOptions(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain = null,
		LabelLookup $labelLookup = null
	) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );

		if ( $languageFallbackChain ) {
			$formatterOptions->setOption( 'languages', $languageFallbackChain );
		}

		if ( $labelLookup ) {
			$formatterOptions->setOption( 'LabelLookup', $labelLookup );
		}

		return $formatterOptions;
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $languageFallbackChain
	 * @param LabelLookup|null $labelLookup
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain = null,
		LabelLookup $labelLookup = null
	) {
		$formatterOptions = $this->getFormatterOptions( $languageCode, $languageFallbackChain, $labelLookup );

		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$formatterOptions
		);
	}

	/**
	 * @param LabelLookup $labelLookup
	 *
	 * @return EntityIdFormatter
	 */
	private function getPropertyIdFormatter( LabelLookup $labelLookup ) {
		return $this->idFormatterFactory->getEntityIdFormater( $labelLookup );
	}

}
