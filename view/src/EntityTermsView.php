<?php

namespace Wikibase\View;

use Message;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\Template\TemplateFactory;

/**
 * Generates HTML to display the fingerprint of an entity
 * in the user's current language.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
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
	 * @var string
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
	 * @param string $languageCode
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
	 * @return string
	 */
	public function getHtml(
		Fingerprint $fingerprint,
		EntityId $entityId = null,
		$termBoxHtml,
		TextInjector $textInjector
	) {
		$labels = $fingerprint->getLabels();
		$descriptions = $fingerprint->getDescriptions();
		$aliasGroups = $fingerprint->getAliasGroups();

		return $this->templateFactory->render( 'wikibase-entitytermsview',
			$labels->hasTermForLanguage( $this->languageCode ) ? '' : 'wb-empty',
			$this->getHtmlForLabel( $labels, $entityId ),
			$descriptions->hasTermForLanguage( $this->languageCode ) ? '' : 'wb-empty',
			$this->getDescriptionText( $descriptions ),
			$aliasGroups->hasGroupForLanguage( $this->languageCode ) ? '' : 'wb-empty',
			$this->getHtmlForAliases( $aliasGroups ),
			$termBoxHtml,
			$textInjector->newMarker(
				'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class'
			),
			$this->getHtmlForLabelDescriptionAliasesEditSection( $entityId )
		);
	}

	/**
	 * @param TermList $labels the list of labels to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 *
	 * @return string
	 */
	private function getHtmlForLabel( TermList $labels, EntityId $entityId = null ) {
		$idInParentheses = '';

		if( !is_null( $entityId ) ) {
			$id = $entityId->getSerialization();
			$idInParentheses = wfMessage( 'parentheses', $id )->text();
		}

		return $this->templateFactory->render( 'wikibase-entitytermsview-heading-label',
			$labels->hasTermForLanguage( $this->languageCode )
				? htmlspecialchars( $labels->getByLanguage( $this->languageCode )->getText() )
				: wfMessage( 'wikibase-label-empty' )->escaped(),
			$idInParentheses
		);
	}

	/**
	 * @param TermList $descriptions the list of descriptions to render
	 *
	 * @return string
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
	 * @return string
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
	 * @param Fingerprint $fingerprint
	 * @param string[] $languageCodes The languages the user requested to be shown
	 * @param Title|null $title
	 * @param boolean $showEntitytermslistview
	 *
	 * @return string
	 */
	public function getEntityTermsForLanguageListView(
		Fingerprint $fingerprint,
		$languageCodes,
		Title $title = null,
		$showEntitytermslistview = false
	) {
		$entityTermsForLanguageViewsHtml = '';

		foreach( $languageCodes as $languageCode ) {
			$entityTermsForLanguageViewsHtml .= $this->getEntityTermsForLanguageView(
				$fingerprint,
				$languageCode,
				$title
			);
		}

		return $this->templateFactory->render( 'wikibase-entitytermsforlanguagelistview',
			$this->msg( 'wikibase-entitytermsforlanguagelistview-language' ),
			$this->msg( 'wikibase-entitytermsforlanguagelistview-label' ),
			$this->msg( 'wikibase-entitytermsforlanguagelistview-description' ),
			$this->msg( 'wikibase-entitytermsforlanguagelistview-aliases' ),
			$entityTermsForLanguageViewsHtml
		);
	}

	/**
	 * @param Fingerprint $fingerprint
	 * @param string $languageCode
	 * @param Title|null $title
	 *
	 * @return string
	 */
	private function getEntityTermsForLanguageView(
		Fingerprint $fingerprint,
		$languageCode,
		Title $title = null
	) {
		$labels = $fingerprint->getLabels();
		$descriptions = $fingerprint->getDescriptions();
		$aliasGroups = $fingerprint->getAliasGroups();

		$hasLabel = $labels->hasTermForLanguage( $languageCode );
		$hasDescription = $descriptions->hasTermForLanguage( $languageCode );

		return $this->templateFactory->render( 'wikibase-entitytermsforlanguageview',
			'tr',
			'td',
			$languageCode,
			$this->templateFactory->render( 'wikibase-entitytermsforlanguageview-language',
				is_null( $title )
					? '#'
					: $title->getLocalURL( array( 'setlang' => $languageCode ) ),
				htmlspecialchars( $this->languageNameLookup->getName( $languageCode, $this->languageCode ) )
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
				''
			),
			$this->getAliasesView( $aliasGroups, $languageCode ),
			''
		);
	}

	/**
	 * @param AliasGroupList $aliasGroups
	 * @param string $languageCode
	 *
	 * @return string
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
	 * @return string
	 */
	private function getHtmlForLabelDescriptionAliasesEditSection( EntityId $entityId = null ) {
		if ( is_null( $this->sectionEditLinkGenerator ) ) {
			return '';
		}

		return $this->sectionEditLinkGenerator->getLabelDescriptionAliasesEditSection(
			$this->languageCode,
			$entityId
		);
	}

	/**
	 * @param $key
	 *
	 * @return Message
	 */
	private function msg( $key ) {
		return wfMessage( $key )->inLanguage( $this->languageCode );
	}
}
