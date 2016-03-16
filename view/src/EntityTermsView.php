<?php

namespace Wikibase\View;

use Message;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\LanguageNameLookup;
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
 */
class EntityTermsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var EditSectionGenerator|null
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var string Language of the terms in the title and header section.
	 */
	private $languageCode;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EditSectionGenerator|null $sectionEditLinkGenerator
	 * @param LanguageNameLookup $languageNameLookup
	 * @param string $languageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EditSectionGenerator $sectionEditLinkGenerator = null,
		LanguageNameLookup $languageNameLookup,
		$languageCode
	) {
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->languageCode = $languageCode;
		$this->templateFactory = $templateFactory;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @param Fingerprint $fingerprint the fingerprint to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param string $termBoxHtml
	 * @param TextInjector $textInjector
	 *
	 * @return string HTML
	 */
	public function getHtml(
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
			$descriptions->hasTermForLanguage( $this->languageCode ) ? '' : 'wb-empty',
			$this->getDescriptionText( $descriptions ),
			$aliasGroups->hasGroupForLanguage( $this->languageCode ) ? '' : 'wb-empty',
			$this->getHtmlForAliases( $aliasGroups ),
			$termBoxHtml,
			$marker,
			$this->getHtmlForLabelDescriptionAliasesEditSection( $entityId )
		);
	}

	/**
	 * @param Fingerprint $fingerprint
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	public function getTitleHtml(
		Fingerprint $fingerprint,
		EntityId $entityId = null
	) {
		$labels = $fingerprint->getLabels();
		$idInParentheses = '';

		if ( $entityId !== null ) {
			$id = $entityId->getSerialization();
			$idInParentheses = wfMessage( 'parentheses', $id )->text();
		}

		if ( $labels->hasTermForLanguage( $this->languageCode ) ) {
			return $this->templateFactory->render( 'wikibase-title',
				'',
				htmlspecialchars( $labels->getByLanguage( $this->languageCode )->getText() ),
				$idInParentheses
			);
		} else {
			return $this->templateFactory->render( 'wikibase-title',
				'wb-empty',
				wfMessage( 'wikibase-label-empty' )->escaped(),
				$idInParentheses
			);
		}
	}

	/**
	 * @param TermList $descriptions the list of descriptions to render
	 *
	 * @return string HTML
	 */
	private function getDescriptionText( TermList $descriptions ) {
		if ( $descriptions->hasTermForLanguage( $this->languageCode ) ) {
			$text = $descriptions->getByLanguage( $this->languageCode )->getText();
			return htmlspecialchars( $text );
		} else {
			return wfMessage( 'wikibase-description-empty' )->escaped();
		}
	}

	/**
	 * @param AliasGroupList $aliasGroups the list of alias groups to render
	 *
	 * @return string HTML
	 */
	private function getHtmlForAliases( AliasGroupList $aliasGroups ) {
		if ( $aliasGroups->hasGroupForLanguage( $this->languageCode ) ) {
			$aliasesHtml = '';
			$aliases = $aliasGroups->getByLanguage( $this->languageCode )->getAliases();
			foreach ( $aliases as $alias ) {
				$aliasesHtml .= $this->templateFactory->render(
					'wikibase-entitytermsview-aliases-alias',
					htmlspecialchars( $alias )
				);
			}

			return $this->templateFactory->render( 'wikibase-entitytermsview-aliases', $aliasesHtml );
		} else {
			return $this->templateFactory->render( 'wikibase-entitytermsview-aliases',
				wfMessage( 'wikibase-aliases-empty' )->escaped()
			);
		}
	}

	/**
	 * @param EntityId $entityId
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider $aliasesProvider
	 * @param string[] $languageCodes The languages the user requested to be shown
	 * @param Title|null $title
	 *
	 * @return string HTML
	 */
	public function getEntityTermsForLanguageListView(
		EntityId $entityId,
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider,
		array $languageCodes,
		Title $title = null
	) {
		$entityTermsForLanguageViewsHtml = '';

		foreach ( $languageCodes as $languageCode ) {
			$entityTermsForLanguageViewsHtml .= $this->getEntityTermsForLanguageView(
				$labelsProvider,
				$descriptionsProvider,
				$aliasesProvider,
				$languageCode,
				$title
			);
		}

		return $this->templateFactory->render( 'wikibase-entitytermsforlanguagelistview',
			$this->msg( 'wikibase-entitytermsforlanguagelistview-language' )->escaped(),
			$this->msg( 'wikibase-entitytermsforlanguagelistview-label' )->escaped(),
			$this->msg( 'wikibase-entitytermsforlanguagelistview-description' )->escaped(),
			$this->msg( 'wikibase-entitytermsforlanguagelistview-aliases' )->escaped(),
			$entityTermsForLanguageViewsHtml
		);
	}

	/**
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider $aliasesProvider
	 * @param string $languageCode
	 * @param Title|null $title
	 *
	 * @return string HTML
	 */
	private function getEntityTermsForLanguageView(
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider,
		$languageCode,
		Title $title = null
	) {
		$languageName = $this->languageNameLookup->getName( $languageCode );
		$labels = $labelsProvider->getLabels();
		$descriptions = $descriptionsProvider->getDescriptions();
		$hasLabel = $labels->hasTermForLanguage( $languageCode );
		$hasDescription = $descriptions->hasTermForLanguage( $languageCode );

		return $this->templateFactory->render( 'wikibase-entitytermsforlanguageview',
			'tr',
			'td',
			$languageCode,
			$this->templateFactory->render( 'wikibase-entitytermsforlanguageview-language',
				htmlspecialchars( $title === null
					? '#'
					: $title->getLocalURL( array( 'setlang' => $languageCode ) )
				),
				htmlspecialchars( $languageName )
			),
			$this->templateFactory->render( 'wikibase-labelview',
				$hasLabel ? '' : 'wb-empty',
				htmlspecialchars( $hasLabel
					? $labels->getByLanguage( $languageCode )->getText()
					: $this->msg( 'wikibase-label-empty' )->text()
				),
				''
			),
			$this->templateFactory->render( 'wikibase-descriptionview',
				$hasDescription ? '' : 'wb-empty',
				htmlspecialchars( $hasDescription
					? $descriptions->getByLanguage( $languageCode )->getText()
					: $this->msg( 'wikibase-description-empty' )->text()
				),
				'',
				''
			),
			$this->getAliasesView( $aliasesProvider->getAliasGroups(), $languageCode ),
			''
		);
	}

	/**
	 * @param AliasGroupList $aliasGroups
	 * @param string $languageCode
	 *
	 * @return string HTML
	 */
	private function getAliasesView( AliasGroupList $aliasGroups, $languageCode ) {
		if ( !$aliasGroups->hasGroupForLanguage( $languageCode ) ) {
			return $this->templateFactory->render( 'wikibase-aliasesview',
				'wb-empty',
				'',
				''
			);
		} else {
			$aliasesHtml = '';
			$aliases = $aliasGroups->getByLanguage( $languageCode )->getAliases();
			foreach ( $aliases as $alias ) {
				$aliasesHtml .= $this->templateFactory->render(
					'wikibase-aliasesview-list-item',
					htmlspecialchars( $alias )
				);
			}

			return $this->templateFactory->render( 'wikibase-aliasesview',
				'',
				$aliasesHtml,
				''
			);
		}
	}

	/**
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	private function getHtmlForLabelDescriptionAliasesEditSection( EntityId $entityId = null ) {
		if ( $this->sectionEditLinkGenerator === null ) {
			return '';
		}

		return $this->sectionEditLinkGenerator->getLabelDescriptionAliasesEditSection(
			$this->languageCode,
			$entityId
		);
	}

	/**
	 * @param string $key
	 *
	 * @return Message
	 */
	private function msg( $key ) {
		return wfMessage( $key );
	}

}
