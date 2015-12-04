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
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class EntityViewFactory {

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
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @param string $entityType
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	public function newEntityView(
		$entityType,
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator
	 ) {
		$entityTermsView = $this->newEntityTermsView( $languageCode, $editSectionGenerator );

		$statementSectionsView = $this->newStatementSectionsView(
			$entityType,
			$languageCode,
			$fallbackChain,
			$labelDescriptionLookup,
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
					$this->plainTextIdFormatterFactory->getEntityIdFormatter( $labelDescriptionLookup ),
					$this->languageNameLookup,
					$this->badgeItems,
					$this->specialSiteLinkGroups
				);

				return new ItemView(
					$this->templateFactory,
					$entityTermsView,
					$statementSectionsView,
					$language,
					$siteLinksView,
					$this->siteLinkGroups
				);
			case 'property':
				return new PropertyView(
					$this->templateFactory,
					$entityTermsView,
					$statementSectionsView,
					$this->dataTypeFactory,
					$language
				);
		}

		throw new InvalidArgumentException( 'No EntityView for entity type: ' . $entityType );
	}

	/**
	 * @param string $entityType
	 * @param string $languageCode
	 * @param LanguageFallbackChain $fallbackChain
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @return StatementSectionsView
	 */
	private function newStatementSectionsView(
		$entityType,
		$languageCode,
		LanguageFallbackChain $fallbackChain,
		LabelDescriptionLookup $labelDescriptionLookup,
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

}
