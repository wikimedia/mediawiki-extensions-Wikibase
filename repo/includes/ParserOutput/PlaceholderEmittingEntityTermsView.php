<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SimpleEntityTermsView;
use Wikibase\View\TermsListView;
use Wikibase\View\Template\TemplateFactory;

/**
 * An EntityTermsView that returns placeholders for some parts of the HTML
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class PlaceholderEmittingEntityTermsView extends SimpleEntityTermsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var TextInjector
	 */
	private $textInjector;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EditSectionGenerator $sectionEditLinkGenerator
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EditSectionGenerator $sectionEditLinkGenerator,
		LocalizedTextProvider $textProvider,
		TermsListView $termsListView,
		TextInjector $textInjector
	) {
		parent::__construct( $templateFactory, $sectionEditLinkGenerator, $termsListView, $textProvider );
		$this->templateFactory = $templateFactory;
		$this->textInjector = $textInjector;
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param EntityId|null $entityId the id of the entity
	 *
	 * @return string HTML
	 */
	public function getHtml(
		$mainLanguageCode,
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
		EntityId $entityId = null
	) {
		$cssClasses = $this->textInjector->newMarker(
			'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class'
		);

		return $this->templateFactory->render( 'wikibase-entitytermsview',
			$this->getHeadingHtml( $mainLanguageCode, $descriptionsProvider, $aliasesProvider ),
			$this->textInjector->newMarker( 'termbox' ),
			$cssClasses,
			$this->getHtmlForLabelDescriptionAliasesEditSection( $mainLanguageCode, $entityId )
		);
	}

}
