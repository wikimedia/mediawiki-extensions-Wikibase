<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\View\Template\TemplateFactory;

/**
 * Generates HTML to display the fingerprint of an entity
 * in the user's current language.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class EntityTermsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var EditSectionGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EditSectionGenerator $sectionEditLinkGenerator
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EditSectionGenerator $sectionEditLinkGenerator,
		LocalizedTextProvider $textProvider
	) {
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->templateFactory = $templateFactory;
		$this->textProvider = $textProvider;
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param Fingerprint $fingerprint the fingerprint to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param string $termBoxHtml
	 * @param TextInjector $textInjector
	 *
	 * @return string HTML
	 */
	public function getHtml(
		$mainLanguageCode,
		Fingerprint $fingerprint,
		EntityId $entityId = null,
		$termBoxHtml,
		TextInjector $textInjector
	) {
		$descriptions = $fingerprint->getDescriptions();
		$aliasGroups = $fingerprint->getAliasGroups();
		$marker = $textInjector->newMarker(
			'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class'
		);

		return $this->templateFactory->render( 'wikibase-entitytermsview',
			$descriptions->hasTermForLanguage( $mainLanguageCode ) ? '' : 'wb-empty',
			$this->getDescriptionHtml( $mainLanguageCode, $descriptions ),
			$aliasGroups->hasGroupForLanguage( $mainLanguageCode ) ? '' : 'wb-empty',
			$this->getHtmlForAliases( $mainLanguageCode, $aliasGroups ),
			$termBoxHtml,
			$marker,
			$this->getHtmlForLabelDescriptionAliasesEditSection( $mainLanguageCode, $entityId )
		);
	}

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param Fingerprint $fingerprint
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	public function getTitleHtml(
		$mainLanguageCode,
		Fingerprint $fingerprint,
		EntityId $entityId = null
	) {
		$labels = $fingerprint->getLabels();
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
		if ( $aliasGroups->hasGroupForLanguage( $languageCode ) ) {
			$aliasesHtml = '';
			$aliases = $aliasGroups->getByLanguage( $languageCode )->getAliases();
			foreach ( $aliases as $alias ) {
				$aliasesHtml .= $this->templateFactory->render(
					'wikibase-entitytermsview-aliases-alias',
					htmlspecialchars( $alias )
				);
			}
		} else {
			$aliasesHtml = htmlspecialchars( $this->textProvider->get( 'wikibase-aliases-empty' ) );
		}
		return $this->templateFactory->render( 'wikibase-entitytermsview-aliases', $aliasesHtml );
	}

	/**
	 * @param string $languageCode The language for which terms should be edited
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	private function getHtmlForLabelDescriptionAliasesEditSection( $languageCode, EntityId $entityId = null ) {
		return $this->sectionEditLinkGenerator->getLabelDescriptionAliasesEditSection(
			$languageCode,
			$entityId
		);
	}

}
