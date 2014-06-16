<?php

namespace Wikibase\Repo\View;

use Language;
use SpecialPage;
use Title;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Utils;
use Wikibase\Repo\View\SectionEditLinkGenerator;

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
	protected $sectionEditLinkGenerator;

	/**
	 * @var Language
	 */
	protected $language;

	public function __construct( Language $language ) {
		$this->language = $language;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
	}

	/**
	 * @param $key
	 *
	 * @return \Message
	 */
	protected function msg( $key ) {
		return wfMessage( $key )->inLanguage( $this->language );
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's collection of terms.
	 *
	 * @since 0.4
	 *
	 * @param Title $title The title of the page the term box is to be shown on
	 * @param Entity $entity the entity to render
	 * @param string[] $languages list of languages to show terms for
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	public function renderTermBox( Title $title, Entity $entity, $languages, $editable = true ) {
		if ( empty( $languages ) ) {
			return '';
		}

		wfProfileIn( __METHOD__ );

		$html = $thead = $tbody = '';

		$labels = $entity->getLabels();
		$descriptions = $entity->getDescriptions();

		$html .= wfTemplate( 'wb-terms-heading', $this->msg( 'wikibase-terms' ) );

		$rowNumber = 0;
		foreach( $languages as $language ) {

			$label = array_key_exists( $language, $labels ) ? $labels[$language] : false;
			$description = array_key_exists( $language, $descriptions ) ? $descriptions[$language] : false;

			$alternatingClass = ( $rowNumber++ % 2 ) ? 'even' : 'uneven';

			$entitySubPage = $this->getFormattedIdForEntity( $entity ) . '/' . $language;
			$specialSetLabel = SpecialPage::getTitleFor( "SetLabel", $entitySubPage );
			$specialSetDescription = SpecialPage::getTitleFor( "SetDescription", $entitySubPage );

			$editLabelLink = $specialSetLabel->getLocalURL();
			$editDescriptionLink = $specialSetDescription->getLocalURL();

			$tbody .= wfTemplate( 'wb-term',
				$language,
				$alternatingClass,
				htmlspecialchars( Utils::fetchLanguageName( $language ) ),
				htmlspecialchars( $label !== false ? $label : $this->msg( 'wikibase-label-empty' )->text() ),
				htmlspecialchars( $description !== false ? $description : $this->msg( 'wikibase-description-empty' )->text() ),
				$this->sectionEditLinkGenerator->getHtmlForEditSection( $editLabelLink, $this->msg( 'wikibase-edit' ), 'span', $editable ),
				$this->sectionEditLinkGenerator->getHtmlForEditSection( $editDescriptionLink, $this->msg( 'wikibase-edit' ), 'span', $editable ),
				$label !== false ? '' : 'wb-value-empty',
				$description !== false ? '' : 'wb-value-empty',
				$title->getLocalURL( array( 'setlang' => $language ) )
			);
		}

		$html = $html . wfTemplate( 'wb-terms-table', $tbody );

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return string
	 */
	protected function getFormattedIdForEntity( Entity $entity ) {
		if ( !$entity->getId() ) {
			return ''; //XXX: should probably throw an exception?
		}

		return $entity->getId()->getPrefixedId();
	}

}
