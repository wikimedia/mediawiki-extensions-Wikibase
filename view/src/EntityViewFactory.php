<?php

namespace Wikibase\View;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Language;
use SiteStore;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\View\Template\TemplateFactory;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityViewFactory {

	/**
	 * @var HtmlSnakFormatterFactory
	 */
	private $htmlSnakFormatterFactory;

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
	 * @param HtmlSnakFormatterFactory $htmlSnakFormatterFactory
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
		HtmlSnakFormatterFactory $htmlSnakFormatterFactory,
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
		$this->htmlSnakFormatterFactory = $htmlSnakFormatterFactory;
		$this->entityLookup = $entityLookup;
		$this->siteStore = $siteStore;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->specialSiteLinkGroups = $specialSiteLinkGroups;
		$this->badgeItems = $badgeItems;
		$this->templateFactory = $templateFactory;
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
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	public function newEntityView(
		$entityType,
		$languageCode,
		LabelLookup $labelLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator
	 ) {

		$entityTermsView = $this->newEntityTermsView( $languageCode, $editSectionGenerator );
		$statementGroupListView = $this->newStatementGroupListView(
			$languageCode,
			$fallbackChain,
			$labelLookup,
			$editSectionGenerator
		);

		// @fixme all that seems needed in EntityView is language code and dir.
		$language = Language::factory( $languageCode );

		// @fixme support more entity types
		switch ( $entityType ) {
			case 'item':
				$siteLinksView = new SiteLinksView(
					$this->templateFactory,
					$this->siteStore->getSites(),
					$editSectionGenerator,
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
					$this->siteLinkGroups
				);
			case 'property':
				return new PropertyView(
					$this->templateFactory,
					$entityTermsView,
					$statementGroupListView,
					$this->dataTypeFactory,
					$language
				);
		}

		throw new InvalidArgumentException( 'No EntityView for entity type: ' . $entityType );
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain $fallbackChain
	 * @param LabelLookup $labelLookup
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return StatementGroupListView
	 */
	private function newStatementGroupListView(
		$languageCode,
		LanguageFallbackChain $fallbackChain,
		LabelLookup $labelLookup,
		EditSectionGenerator $editSectionGenerator
	) {
		$propertyIdFormatter = $this->getPropertyIdFormatter( $labelLookup );

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->templateFactory,
			$this->htmlSnakFormatterFactory->getSnakFormatter( $languageCode, $fallbackChain, $labelLookup ),
			$propertyIdFormatter
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$this->templateFactory,
			$snakHtmlGenerator
		);

		return new StatementGroupListView(
			$this->templateFactory,
			$propertyIdFormatter,
			$editSectionGenerator,
			$claimHtmlGenerator
		);
	}

	/**
	 * @param string $languageCode
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return EntityTermsView
	 */
	private function newEntityTermsView( $languageCode, EditSectionGenerator $editSectionGenerator ) {
		return new EntityTermsView(
			$this->templateFactory,
			$editSectionGenerator,
			$this->languageNameLookup,
			$languageCode
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
