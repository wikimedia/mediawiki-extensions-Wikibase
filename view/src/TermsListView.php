<?php

namespace Wikibase\View;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\Template\TemplateFactory;

/**
 * Generates HTML to display terms of an entity in a list.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class TermsListView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

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
	 * @param LanguageNameLookup $languageNameLookup
	 * @param LocalizedTextProvider $textProvider
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		LanguageNameLookup $languageNameLookup,
		LocalizedTextProvider $textProvider,
		LanguageDirectionalityLookup $languageDirectionalityLookup
	) {
		$this->templateFactory = $templateFactory;
		$this->languageNameLookup = $languageNameLookup;
		$this->textProvider = $textProvider;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
	}

	/**
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param string[] $languageCodes The languages the user requested to be shown
	 *
	 * @return string HTML
	 */
	public function getHtml(
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
		array $languageCodes
	) {
		$entityTermsForLanguageViewsHtml = '';

		foreach ( $languageCodes as $languageCode ) {
			$entityTermsForLanguageViewsHtml .= $this->getListItemHtml(
				$labelsProvider,
				$descriptionsProvider,
				$aliasesProvider,
				$languageCode
			);
		}

		return $this->getListViewHtml( $entityTermsForLanguageViewsHtml );
	}

	/**
	 * @param string $contentHtml
	 *
	 * @return string HTML
	 */
	public function getListViewHtml( $contentHtml ) {
		return $this->templateFactory->render( 'wikibase-entitytermsforlanguagelistview',
			htmlspecialchars( $this->textProvider->get( 'wikibase-entitytermsforlanguagelistview-language' ) ),
			htmlspecialchars( $this->textProvider->get( 'wikibase-entitytermsforlanguagelistview-label' ) ),
			htmlspecialchars( $this->textProvider->get( 'wikibase-entitytermsforlanguagelistview-description' ) ),
			htmlspecialchars( $this->textProvider->get( 'wikibase-entitytermsforlanguagelistview-aliases' ) ),
			$contentHtml
		);

	}

	/**
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param string $languageCode
	 *
	 * @return string HTML
	 */
	public function getListItemHtml(
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
		$languageCode
	) {
		$languageName = $this->languageNameLookup->getName( $languageCode );
		$labels = $labelsProvider->getLabels();
		$descriptions = $descriptionsProvider->getDescriptions();

		return $this->templateFactory->render( 'wikibase-entitytermsforlanguageview',
			'tr',
			'td',
			$languageCode,
			htmlspecialchars( $languageName ),
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
			'',
			'th'
		);
	}

	private function getTermView( TermList $termList, $templateName, $emptyTextKey, $languageCode ) {
		$hasTerm = $termList->hasTermForLanguage( $languageCode );
		$effectiveLanguage = $hasTerm ? $languageCode : $this->textProvider->getLanguageOf( $emptyTextKey );
		return $this->templateFactory->render( $templateName,
			$hasTerm ? '' : 'wb-empty',
			htmlspecialchars( $hasTerm
				? $termList->getByLanguage( $languageCode )->getText()
				: $this->textProvider->get( $emptyTextKey )
			),
			'',
			$this->languageDirectionalityLookup->getDirectionality( $effectiveLanguage ) ?: 'auto',
			$effectiveLanguage
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
				'', // No text, no language
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
				$this->languageDirectionalityLookup->getDirectionality( $languageCode ) ?: 'auto',
				$languageCode
			);
		}
	}

}
