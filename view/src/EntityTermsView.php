<?php

namespace Wikibase\View;

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
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EditSectionGenerator|null $sectionEditLinkGenerator
	 * @param LanguageNameLookup $languageNameLookup
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EditSectionGenerator $sectionEditLinkGenerator = null,
		LanguageNameLookup $languageNameLookup,
		LocalizedTextProvider $textProvider
	) {
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->templateFactory = $templateFactory;
		$this->languageNameLookup = $languageNameLookup;
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
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param string[] $languageCodes The languages the user requested to be shown
	 * @param Title|null $title
	 *
	 * @return string HTML
	 */
	public function getEntityTermsForLanguageListView(
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
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
			htmlspecialchars( $this->textProvider->get( 'wikibase-entitytermsforlanguagelistview-language' ) ),
			htmlspecialchars( $this->textProvider->get( 'wikibase-entitytermsforlanguagelistview-label' ) ),
			htmlspecialchars( $this->textProvider->get( 'wikibase-entitytermsforlanguagelistview-description' ) ),
			htmlspecialchars( $this->textProvider->get( 'wikibase-entitytermsforlanguagelistview-aliases' ) ),
			$entityTermsForLanguageViewsHtml
		);
	}

	/**
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param string $languageCode
	 * @param Title|null $title
	 *
	 * @return string HTML
	 */
	private function getEntityTermsForLanguageView(
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
		$languageCode,
		Title $title = null
	) {
		$languageName = $this->languageNameLookup->getName( $languageCode );
		$labels = $labelsProvider->getLabels();
		$descriptions = $descriptionsProvider->getDescriptions();

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
			$this->getTermView(
				$labels,
				'wikibase-labelview', // Template
				'wikibase-label-empty', // Text key
				$languageCode
			),
			$this->getTermView(
				$descriptions,
				'wikibase-descriptionview', // Template
				'wikibase-description-empty', // Text key
				$languageCode
			),
			$aliasesProvider ? $this->getAliasesView( $aliasesProvider->getAliasGroups(), $languageCode ) : '',
			''
		);
	}

	private function getTermView( TermList $termList, $templateName, $emptyTextKey, $languageCode ) {
		$hasTerm = $termList->hasTermForLanguage( $languageCode );
		return $this->templateFactory->render( $templateName,
			$hasTerm ? '' : 'wb-empty',
			htmlspecialchars( $hasTerm
				? $termList->getByLanguage( $languageCode )->getText()
				: $this->textProvider->get( $emptyTextKey )
			),
			'',
			'auto', // FIXME DirLookup
			$hasTerm ? $languageCode : $this->textProvider->getLanguageOf( $emptyTextKey )
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
				'',
				'auto', // FIXME DirLookup
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
				'',
				'auto', // FIXME DirLookup
				$languageCode
			);
		}
	}

	/**
	 * @param $languageCode The language for which terms should be edited
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	private function getHtmlForLabelDescriptionAliasesEditSection( $languageCode, EntityId $entityId = null ) {
		if ( $this->sectionEditLinkGenerator === null ) {
			return '';
		}

		return $this->sectionEditLinkGenerator->getLabelDescriptionAliasesEditSection(
			$languageCode,
			$entityId
		);
	}

}
