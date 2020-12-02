<?php

namespace Wikibase\View;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\Template\TemplateFactory;

/**
 * Generates HTML to display terms of an entity in a list.
 *
 * @license GPL-2.0-or-later
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
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param AliasGroupList|null $aliasGroups
	 * @param string[] $languageCodes The languages the user requested to be shown
	 *
	 * @return string HTML
	 */
	public function getHtml(
		TermList $labels,
		TermList $descriptions,
		?AliasGroupList $aliasGroups,
		array $languageCodes
	) {
		$entityTermsForLanguageViewsHtml = '';

		foreach ( $languageCodes as $languageCode ) {
			$entityTermsForLanguageViewsHtml .= $this->getListItemHtml(
				$labels,
				$descriptions,
				$aliasGroups,
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
		return $this->templateFactory->render(
			'wikibase-entitytermsforlanguagelistview',
			$this->textProvider->getEscaped( 'wikibase-entitytermsforlanguagelistview-language' ),
			$this->textProvider->getEscaped( 'wikibase-entitytermsforlanguagelistview-label' ),
			$this->textProvider->getEscaped( 'wikibase-entitytermsforlanguagelistview-description' ),
			$this->textProvider->getEscaped( 'wikibase-entitytermsforlanguagelistview-aliases' ),
			$contentHtml
		);
	}

	/**
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param AliasGroupList|null $aliasGroups
	 * @param string $languageCode
	 *
	 * @return string HTML
	 */
	public function getListItemHtml(
		TermList $labels,
		TermList $descriptions,
		?AliasGroupList $aliasGroups,
		$languageCode
	) {
		$languageName = $this->languageNameLookup->getName( $languageCode );

		return $this->templateFactory->render(
			'wikibase-entitytermsforlanguageview',
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
			$aliasGroups ? $this->getAliasesView( $aliasGroups, $languageCode ) : '',
			'',
			'th'
		);
	}

	private function getTermView( TermList $termList, $templateName, $emptyTextKey, $languageCode ) {
		$hasTerm = $termList->hasTermForLanguage( $languageCode );
		$effectiveLanguage = $hasTerm ? $languageCode : $this->textProvider->getLanguageOf( $emptyTextKey );
		return $this->templateFactory->render(
			$templateName,
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
			return $this->templateFactory->render(
				'wikibase-aliasesview',
				'wb-empty',
				'',
				'',
				// FIXME: Ideally we would not emit dir and lang attributes at all here
				'', // Empty dir attribute is considered invalid and thus the element inherits dir
				$languageCode // Empty lang attribute would mean "explicitly unknown language"
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

			return $this->templateFactory->render(
				'wikibase-aliasesview',
				'',
				$aliasesHtml,
				'',
				$this->languageDirectionalityLookup->getDirectionality( $languageCode ) ?: 'auto',
				$languageCode
			);
		}
	}

}
