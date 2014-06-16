<?php

namespace Wikibase\Repo\View;

use Language;
use Message;
use SpecialPage;
use Title;
use Wikibase\DataModel\Entity\Entity;
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
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var Language
	 */
	private $language;

	public function __construct( Language $language ) {
		$this->language = $language;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
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
	 * @param Entity $entity the entity to render
	 * @param string[] $languageCodes list of language codes to show terms for
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	public function renderTermBox( Title $title, Entity $entity, array $languageCodes, $editable = true ) {
		if ( empty( $languageCodes ) ) {
			return '';
		}

		wfProfileIn( __METHOD__ );

		$html = $thead = $tbody = '';

		$entityId = $entity->getId();
		$labels = $entity->getLabels();
		$descriptions = $entity->getDescriptions();

		// FIXME: Reuse SectionEditLinkGenerator::getEditUrl here if possible.
		// All special pages relevant here accept the first parameter to be empty
		$subPage = '';
		if ( $entityId !== null ) {
			$subPage .= $entityId->getSerialization();
		}

		$html .= wfTemplate( 'wb-terms-heading', $this->msg( 'wikibase-terms' ) );

		$rowNumber = 0;
		foreach ( $languageCodes as $languageCode ) {
			$label = array_key_exists( $languageCode, $labels ) ? $labels[$languageCode] : false;
			$description = array_key_exists( $languageCode, $descriptions ) ? $descriptions[$languageCode] : false;

			$alternatingClass = ( $rowNumber++ % 2 ) ? 'even' : 'uneven';

			$localSubPage = $subPage . '/' . $languageCode;
			$specialSetLabel = SpecialPage::getTitleFor( 'SetLabel', $localSubPage );
			$specialSetDescription = SpecialPage::getTitleFor( 'SetDescription', $localSubPage );

			$editLabelLink = $specialSetLabel->getLocalURL();
			$editDescriptionLink = $specialSetDescription->getLocalURL();

			$tbody .= wfTemplate( 'wb-term',
				$languageCode,
				$alternatingClass,
				htmlspecialchars( Utils::fetchLanguageName( $languageCode ) ),
				htmlspecialchars( $label !== false ? $label : $this->msg( 'wikibase-label-empty' )->text() ),
				htmlspecialchars( $description !== false ? $description : $this->msg( 'wikibase-description-empty' )->text() ),
				$this->sectionEditLinkGenerator->getHtmlForEditSection( $editLabelLink, $this->msg( 'wikibase-edit' ), 'span', $editable ),
				$this->sectionEditLinkGenerator->getHtmlForEditSection( $editDescriptionLink, $this->msg( 'wikibase-edit' ), 'span', $editable ),
				$label !== false ? '' : 'wb-value-empty',
				$description !== false ? '' : 'wb-value-empty',
				$title->getLocalURL( array( 'setlang' => $languageCode ) )
			);
		}

		$html = $html . wfTemplate( 'wb-terms-table', $tbody );

		wfProfileOut( __METHOD__ );
		return $html;
	}

}
