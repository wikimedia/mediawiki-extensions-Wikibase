<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\View\Template\TemplateFactory;

/**
 * Generates HTML to display the terms of an entity.
 *
 * @license GPL-2.0-or-later
 */
class SimpleEntityTermsView implements EntityTermsView {

	/**
	 * @var HtmlTermRenderer
	 */
	private $htmlTermRenderer;

	/**
	 * @var LabelDescriptionLookup For getting label/description of entity being rendered.
	 */
	private $labelDescriptionLookup;

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

	public function __construct(
		HtmlTermRenderer $htmlTermRenderer,
		LabelDescriptionLookup $labelDescriptionLookup,
		TemplateFactory $templateFactory,
		EditSectionGenerator $sectionEditLinkGenerator,
		TermsListView $termsListView,
		LocalizedTextProvider $textProvider
	) {
		$this->htmlTermRenderer = $htmlTermRenderer;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->templateFactory = $templateFactory;
		$this->termsListView = $termsListView;
		$this->textProvider = $textProvider;
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param AliasGroupList|null $aliasGroups
	 * @param EntityId|null $entityId the id of the entity
	 *
	 * @return string HTML
	 */
	public function getHtml(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null,
		EntityId $entityId = null
	) {
		return $this->templateFactory->render(
			'wikibase-entitytermsview',
			$this->getHeadingHtml( $mainLanguageCode, $entityId, $aliasGroups ),
			$this->termsListView->getHtml(
				$labels,
				$descriptions,
				$aliasGroups,
				$this->getTermsLanguageCodes(
					$mainLanguageCode,
					$labels,
					$descriptions,
					$aliasGroups
				)
			),
			'',
			$this->getHtmlForLabelDescriptionAliasesEditSection( $mainLanguageCode, $entityId )
		);
	}

	protected function getHeadingHtml(
		$languageCode,
		EntityId $entityId = null,
		AliasGroupList $aliasGroups = null
	) {
		$headingPartsHtml = '';

		$description = null;
		if ( $entityId !== null ) {
			try {
				$description = $this->labelDescriptionLookup->getDescription( $entityId );
			} catch ( LabelDescriptionLookupException $e ) {
				// This masks the differences between missing entities, missing terms and lookup errors.
			}
		}

		$headingPartsHtml .= $this->templateFactory->render(
			'wikibase-entitytermsview-heading-part',
			'description',
			$description === null ? 'wb-empty' : '',
			$this->getDescriptionHtml( $description )
		);

		if ( $aliasGroups ) {
			$headingPartsHtml .= $this->templateFactory->render(
				'wikibase-entitytermsview-heading-part',
				'aliases',
				$aliasGroups->hasGroupForLanguage( $languageCode ) ? '' : 'wb-empty',
				$this->getHtmlForAliases( $languageCode, $aliasGroups )
			);
		}

		return $this->templateFactory->render(
			'wikibase-entitytermsview-heading',
			$headingPartsHtml
		);
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param AliasGroupList|null $aliasGroups
	 *
	 * @return string[]
	 */
	protected function getTermsLanguageCodes(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null
	) {
		$allLanguages = [ $mainLanguageCode ];

		$labelLanguages = array_keys( $labels->toTextArray() );
		$allLanguages = array_merge( $allLanguages, $labelLanguages );

		$descriptionLanguages = array_keys( $descriptions->toTextArray() );
		$allLanguages = array_merge( $allLanguages, $descriptionLanguages );

		if ( $aliasGroups ) {
			$aliasLanguages = array_keys( $aliasGroups->toTextArray() );
			$allLanguages = array_merge( $allLanguages, $aliasLanguages );
		}

		$allLanguages = array_unique( $allLanguages );
		return $allLanguages;
	}

	/**
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	public function getTitleHtml( EntityId $entityId = null ) {
		$isEmpty = true;
		$labelHtml = '';
		$idInParenthesesHtml = '';

		if ( $entityId !== null ) {
			$id = $entityId->getSerialization();
			$idInParenthesesHtml = $this->textProvider->getEscaped( 'parentheses', [ $id ] );

			$label = null;
			try {
				$label = $this->labelDescriptionLookup->getLabel( $entityId );
			} catch ( LabelDescriptionLookupException $e ) {
				// This masks the differences between missing entities, missing terms and lookup errors.
			}
			if ( $label !== null ) {
				$labelHtml = $this->htmlTermRenderer->renderTerm( $label );
				$isEmpty = false;
			}
		}

		return $this->templateFactory->render(
			'wikibase-title',
			$isEmpty ? 'wb-empty' : '',
			$isEmpty ? $this->textProvider->getEscaped( 'wikibase-label-empty' ) : $labelHtml,
			$idInParenthesesHtml
		);
	}

	/**
	 * @param Term|null $description
	 *
	 * @return string HTML
	 */
	private function getDescriptionHtml( Term $description = null ) {
		if ( $description === null ) {
			return $this->textProvider->getEscaped( 'wikibase-description-empty' );
		}
		return $this->htmlTermRenderer->renderTerm( $description );
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
				htmlspecialchars( $alias ),
				$this->textProvider->getEscaped( 'wikibase-aliases-separator' )
			);
		}

		return $this->templateFactory->render( 'wikibase-entitytermsview-aliases', $aliasesHtml );
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
