<?php

declare( strict_types = 1 );

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

	private TemplateFactory $templateFactory;

	private LanguageDirectionalityLookup $languageDirectionalityLookup;

	private LanguageNameLookup $languageNameLookup;

	private LocalizedTextProvider $textProvider;

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
	): string {
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
	 * @return string HTML
	 */
	public function getListViewHtml( string $contentHtml ): string {
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
	 * @return string HTML
	 */
	public function getListItemHtml(
		TermList $labels,
		TermList $descriptions,
		?AliasGroupList $aliasGroups,
		string $languageCode
	): string {
		$languageName = $this->languageNameLookup->getName( $languageCode );

		return $this->templateFactory->render(
			'wikibase-entitytermsforlanguageview',
			'tr',
			'td',
			$languageCode,
			htmlspecialchars( $languageName ),
			$this->getLabelView(
				$labels,
				$languageCode
			),
			$languageCode === 'mul'
				? $this->getMulDescriptionView()
				: $this->getDescriptionView(
				$descriptions,
				$languageCode
			),
			$aliasGroups ? $this->getAliasesView( $aliasGroups, $languageCode ) : '',
			'',
			'th'
		);
	}

	private function getLabelView( TermList $listOfLabelTerms, string $languageCode ): string {
		if ( $listOfLabelTerms->hasTermForLanguage( $languageCode ) ) {
			$classes = '';
			$text = $listOfLabelTerms->getByLanguage( $languageCode )->getText();
			$effectiveLanguage = $languageCode;
		} else {
			$classes = 'wb-empty';
			$text = $this->textProvider->get( 'wikibase-label-empty' );
			$effectiveLanguage = $this->textProvider->getLanguageOf( 'wikibase-label-empty' );
		}

		return $this->templateFactory->render(
			'wikibase-labelview',
			$classes,
			htmlspecialchars( $text ),
			'',
			$this->languageDirectionalityLookup->getDirectionality( $effectiveLanguage ) ?: 'auto',
			$effectiveLanguage
		);
	}

	private function getDescriptionView( TermList $listOfDescriptionTerms, string $languageCode ): string {
		if ( $listOfDescriptionTerms->hasTermForLanguage( $languageCode ) ) {
			$classes = '';
			$text = $listOfDescriptionTerms->getByLanguage( $languageCode )->getText();
			$effectiveLanguage = $languageCode;
		} else {
			$classes = 'wb-empty';
			$text = $this->textProvider->get( 'wikibase-description-empty' );
			$effectiveLanguage = $this->textProvider->getLanguageOf( 'wikibase-description-empty' );
		}

		return $this->templateFactory->render(
			'wikibase-descriptionview',
			$classes,
			htmlspecialchars( $text ),
			'',
			$this->languageDirectionalityLookup->getDirectionality( $effectiveLanguage ) ?: 'auto',
			$effectiveLanguage
		);
	}

	private function getMulDescriptionView() {
		return $this->templateFactory->render(
			'wikibase-descriptionview-mul',
			htmlspecialchars( $this->textProvider->get( 'wikibase-description-not-applicable' ), ENT_QUOTES ),
			htmlspecialchars( $this->textProvider->get( 'wikibase-description-not-applicable-title' ), ENT_QUOTES ),
			htmlspecialchars( $this->textProvider->getLanguageOf( 'wikibase-description-not-applicable-title' ), ENT_QUOTES )
		);
	}

	/**
	 * @return string HTML
	 */
	private function getAliasesView( AliasGroupList $aliasGroups, string $languageCode ): string {
		if ( $aliasGroups->hasGroupForLanguage( $languageCode ) ) {
			$classes = '';
			$aliasesHtml = '';
			$aliases = $aliasGroups->getByLanguage( $languageCode )->getAliases();
			foreach ( $aliases as $alias ) {
				$aliasesHtml .= $this->templateFactory->render(
					'wikibase-aliasesview-list-item',
					htmlspecialchars( $alias )
				);
			}
			$dir = $this->languageDirectionalityLookup->getDirectionality( $languageCode ) ?: 'auto';
		} else {
			$classes = 'wb-empty';
			$aliasesHtml = '';
			// FIXME: Ideally we would not emit dir and lang attributes at all here
			$dir = ''; // Empty dir attribute is considered invalid and thus the element inherits dir
			// Do not change lang: empty lang attribute would mean "explicitly unknown language"
		}

		return $this->templateFactory->render(
			'wikibase-aliasesview',
			$classes,
			$aliasesHtml,
			'',
			$dir,
			$languageCode
		);
	}

}
