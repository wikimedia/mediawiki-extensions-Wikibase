<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;

/**
 * An EntityTermsView that returns placeholders for some parts of the HTML
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ParserOutputEntityTermsView extends EntityTermsView {

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
		TextInjector $textInjector
	) {
		$termsListView = new PlaceholderEmittingTermsListView( $textInjector );
		parent::__construct( $templateFactory, $sectionEditLinkGenerator, $termsListView, $textProvider );
		$this->textInjector = $textInjector;
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param EntityId|null $entityId the id of the entity
	 * @param string $cssClasses
	 *
	 * @return string HTML
	 */
	public function getHtml(
		$mainLanguageCode,
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
		EntityId $entityId = null,
		$cssClasses = ''
	) {
		if ( $cssClasses !== '' ) {
			$cssClasses .= ' ';
		}
		$cssClasses .= $this->textInjector->newMarker(
			'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class'
		);

		return parent::getHtml(
			$mainLanguageCode,
			$labelsProvider,
			$descriptionsProvider,
			$aliasesProvider,
			$entityId,
			$cssClasses
		);
	}

}
