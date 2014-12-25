<?php

namespace Wikibase\Repo\View;

use Language;
use Message;
use Title;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Template\TemplateFactory;
use Wikibase\Utils;

/**
 * Generates HTML for displaying the term box, that is, the box
 * of labels and descriptions for additional languages a user understands.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author Daniel Kinzler
 * @author Denny Vrandecic
 */
class TermBoxView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var Language
	 */
	private $language;

	public function __construct( TemplateFactory $templateFactory, Language $language ) {
		$this->language = $language;
		$this->templateFactory = $templateFactory;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator( $templateFactory );
	}

	/**
	 * @param $key
	 *
	 * @return Message
	 */
	private function msg( $key ) {
		return wfMessage( $key )->inLanguage( $this->language );
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's collection of terms.
	 *
	 * @since 0.4
	 *
	 * @param Title $title The title of the page the term box is to be shown on
	 * @param Fingerprint $fingerprint the Fingerprint to render
	 * @param string[] $languageCodes list of language codes to show terms for
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	public function renderTermBox( Title $title, Fingerprint $fingerprint, array $languageCodes, $editable = true ) {
		if ( empty( $languageCodes ) ) {
			return '';
		}

		wfProfileIn( __METHOD__ );

		$labels = $fingerprint->getLabels();
		$descriptions = $fingerprint->getDescriptions();
		$aliasGroups = $fingerprint->getAliasGroups();

		$tbody = '';

		foreach ( $languageCodes as $languageCode ) {
			$hasLabel = $labels->hasTermForLanguage( $languageCode );
			$hasDescription = $descriptions->hasTermForLanguage( $languageCode );

			$tbody .= $this->templateFactory->render( 'wikibase-entitytermsforlanguageview',
				$languageCode,
				$title->getLocalURL( array( 'setlang' => $languageCode ) ),
				htmlspecialchars( Utils::fetchLanguageName( $languageCode ) ),
				$this->templateFactory->render( 'wikibase-labelview',
					$hasLabel ? '' : 'wb-empty',
					htmlspecialchars( $hasLabel
						? $labels->getByLanguage( $languageCode )->getText()
						: $this->msg( 'wikibase-label-empty' )->text()
					),
					'',
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
				$this->getHtmlForAliases( $aliasGroups, $languageCode )
			);
		}

		$html = $this->templateFactory->render( 'wikibase-entitytermsview',
			$this->msg( 'wikibase-terms' )->text(),
			$this->templateFactory->render( 'wikibase-entitytermsforlanguagelistview', $tbody ),
			$this->sectionEditLinkGenerator->getHtmlForEditSection(
				'SpecialPages',
				array(),
				'edit',
				$this->msg( 'wikibase-edit' ),
				$editable
			)
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * @param AliasGroupList $aliasGroups
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function getHtmlForAliases( AliasGroupList $aliasGroups, $languageCode ) {
		if ( !$aliasGroups->hasGroupForLanguage( $languageCode ) ) {
			return $this->templateFactory->render( 'wikibase-aliasesview',
				'wb-empty',
				wfMessage( 'wikibase-aliases-empty' )->escaped(),
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
				wfMessage( 'wikibase-aliases-label' )->escaped(),
				$aliasesHtml,
				''
			);
		}
	}
}
