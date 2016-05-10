<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\View\Template\TemplateFactory;

/**
 * Generates HTML to display the terms of an entity.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class SimpleEntityTermsView implements EntityTermsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var EditSectionGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var TermsListView
	 */
	private $termsListView;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EditSectionGenerator $sectionEditLinkGenerator
	 * @param TermsListView $termsListView
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EditSectionGenerator $sectionEditLinkGenerator,
		TermsListView $termsListView,
		LocalizedTextProvider $textProvider
	) {
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->templateFactory = $templateFactory;
		$this->termsListView = $termsListView;
		$this->textProvider = $textProvider;
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
		return $this->templateFactory->render( 'wikibase-entitytermsview',
			$this->getHeadingHtml( $mainLanguageCode, $descriptionsProvider, $aliasesProvider ),
			$this->termsListView->getHtml(
				$labelsProvider,
				$descriptionsProvider,
				$aliasesProvider,
				$this->getTermsLanguageCodes(
					$mainLanguageCode,
					$labelsProvider,
					$descriptionsProvider,
					$aliasesProvider
				)
			),
			'',
			$this->getHtmlForLabelDescriptionAliasesEditSection( $mainLanguageCode, $entityId )
		);
	}

	protected function getHeadingHtml(
		$languageCode,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null
	) {
		$headingPartsHtml = '';

		$descriptions = $descriptionsProvider->getDescriptions();
		$headingPartsHtml .= $this->templateFactory->render(
			'wikibase-entitytermsview-heading-part',
			'description',
			$descriptions->hasTermForLanguage( $languageCode ) ? '' : 'wb-empty',
			$this->getDescriptionHtml( $languageCode, $descriptions )
		);

		if ( $aliasesProvider !== null ) {
			$aliasGroups = $aliasesProvider->getAliasGroups();
			$headingPartsHtml .= $this->templateFactory->render(
				'wikibase-entitytermsview-heading-part',
				'aliases',
				$aliasGroups->hasGroupForLanguage( $languageCode ) ? '' : 'wb-empty',
				$this->getHtmlForAliases( $languageCode, $aliasGroups )
			);
		}

		return $this->templateFactory->render( 'wikibase-entitytermsview-heading',
			$headingPartsHtml
		);
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 *
	 * @return string[]
	 */
	protected function getTermsLanguageCodes(
		$mainLanguageCode,
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null
	) {
		$allLanguages = [ $mainLanguageCode ];

		$labelLanguages = array_keys( $labelsProvider->getLabels()->toTextArray() );
		$allLanguages = array_merge( $allLanguages, $labelLanguages );

		$descriptionLanguages = array_keys( $descriptionsProvider->getDescriptions()->toTextArray() );
		$allLanguages = array_merge( $allLanguages, $descriptionLanguages );

		if ( $aliasesProvider ) {
			$aliasLanguages = array_keys( $aliasesProvider->getAliasGroups()->toTextArray() );
			$allLanguages = array_merge( $allLanguages, $aliasLanguages );
		}

		$allLanguages = array_unique( $allLanguages );
		return $allLanguages;
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param LabelsProvider $labelsProvider
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	public function getTitleHtml(
		$mainLanguageCode,
		LabelsProvider $labelsProvider,
		EntityId $entityId = null
	) {
		$labels = $labelsProvider->getLabels();
		$idInParenthesesHtml = '';

		if ( $entityId !== null ) {
			$id = $entityId->getSerialization();
			$idInParenthesesHtml = htmlspecialchars( $this->textProvider->get( 'parentheses', [ $id ] ) );
		}

		if ( $labels->hasTermForLanguage( $mainLanguageCode ) ) {
			return $this->templateFactory->render( 'wikibase-title',
				'',
				htmlspecialchars( $labels->getByLanguage( $mainLanguageCode )->getText() ),
				$idInParenthesesHtml
			);
		} else {
			return $this->templateFactory->render( 'wikibase-title',
				'wb-empty',
				htmlspecialchars( $this->textProvider->get( 'wikibase-label-empty' ) ),
				$idInParenthesesHtml
			);
		}
	}

	/**
	 * @param string $languageCode The language of the description
	 * @param TermList $descriptions The list of descriptions to render
	 *
	 * @return string HTML
	 */
	private function getDescriptionHtml( $languageCode, TermList $descriptions ) {
		if ( $descriptions->hasTermForLanguage( $languageCode ) ) {
			$text = $descriptions->getByLanguage( $languageCode )->getText();
		} else {
			$text = $this->textProvider->get( 'wikibase-description-empty' );
		}
		return htmlspecialchars( $text );
	}

	/**
	 * @param string $languageCode The language of the aliases
	 * @param AliasGroupList $aliasGroups the list of alias groups to render
	 *
	 * @return string HTML
	 */
	private function getHtmlForAliases( $languageCode, AliasGroupList $aliasGroups ) {
		if ( !$aliasGroups->hasGroupForLanguage( $languageCode ) ) {
			return '';
		}

		$aliasesHtml = '';
		$aliases = $aliasGroups->getByLanguage( $languageCode )->getAliases();
		foreach ( $aliases as $alias ) {
			$aliasesHtml .= $this->templateFactory->render(
				'wikibase-entitytermsview-aliases-alias',
				htmlspecialchars( $alias )
			);
		}
		$aliasesHtml = $this->templateFactory->render( 'wikibase-entitytermsview-aliases', $aliasesHtml );
		return $aliasesHtml;
	}

	/**
	 * @param string $languageCode The language for which terms should be edited
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	protected function getHtmlForLabelDescriptionAliasesEditSection( $languageCode, EntityId $entityId = null ) {
		return $this->sectionEditLinkGenerator->getLabelDescriptionAliasesEditSection(
			$languageCode,
			$entityId
		);
	}

}
