<?php

namespace Wikibase\Repo\View;

use Message;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * Generates HTML to display the fingerprint of an entity
 * in the user's current language.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class FingerprintView {

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param SectionEditLinkGenerator $sectionEditLinkGenerator
	 * @param string $languageCode
	 */
	public function __construct( SectionEditLinkGenerator $sectionEditLinkGenerator, $languageCode ) {
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->languageCode = $languageCode;
	}

	/**
	 * Builds and returns the HTML representing a fingerprint.
	 *
	 * @since 0.5
	 *
	 * @param Fingerprint $fingerprint the fingerprint to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtml( Fingerprint $fingerprint, EntityId $entityId = null, $editable = true ) {
		$labels = $fingerprint->getLabels();
		$descriptions = $fingerprint->getDescriptions();
		$aliasGroups = $fingerprint->getAliasGroups();

		$html = '';

		if ( $entityId !== null ) {
			$serializedId = $entityId->getSerialization();
			$html .= wfTemplate( 'wb-property-value-supplement', wfMessage( 'parentheses', $serializedId ) );
		}

		$html .= $this->getHtmlForLabel( $labels, $entityId, $editable );
		$html .= $this->getHtmlForDescription( $descriptions, $entityId, $editable );
		$html .= wfTemplate( 'wb-entity-header-separator' );
		$html .= $this->getHtmlForAliases( $aliasGroups, $entityId, $editable );

		return $html;
	}

	/**
	 * Builds and returns the HTML for the edit section.
	 *
	 * @param Message $message
	 * @param string $specialPageName
	 * @param EntityId|null $entityId
	 * @param bool $editable
	 * @return string
	 */
	private function getHtmlForEditSection( Message $message, $specialPageName, EntityId $entityId = null, $editable = true ) {
		if ( $entityId !== null && $editable ) {
			return $this->sectionEditLinkGenerator->getHtmlForEditSection(
				$specialPageName,
				array( $entityId->getSerialization(), $this->languageCode ),
				$message
			);
		} else {
			return '';
		}
	}

	/**
	 * Builds and returns the HTML representing a label.
	 *
	 * @param TermList $labels the list of labels to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	private function getHtmlForLabel( TermList $labels, EntityId $entityId = null, $editable = true ) {
		$labelExists = $labels->hasTermForLanguage( $this->languageCode );
		$editSection = $this->getHtmlForEditSection( wfMessage( 'wikibase-edit' ), 'SetLabel', $entityId, $editable );
		$idString = $entityId === null ? 'new' : $entityId->getSerialization();

		if ( $labelExists ) {
			return $this->getLabelWrapperHTML(
				$idString,
				$labels->getByLanguage( $this->languageCode )->getText(),
				$editSection
			);
		} else {
			return $this->getLabelWrapperHTML(
				$idString,
				wfMessage( 'wikibase-label-empty' )->text(),
				$editSection
			);
		}
	}

	private function getLabelWrapperHTML( $idString, $content, $editSection ) {
		return wfTemplate( 'wb-label',
			$idString,
			wfTemplate( 'wb-property',
				'',
				htmlspecialchars( $content ),
				$editSection
			)
		);
	}

	/**
	 * Builds and returns the HTML representing a description.
	 *
	 * @param TermList $descriptions the list of descriptions to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	private function getHtmlForDescription( TermList $descriptions, EntityId $entityId = null, $editable = true ) {
		$descriptionExists = $descriptions->hasTermForLanguage( $this->languageCode );
		$editSection = $this->getHtmlForEditSection( wfMessage( 'wikibase-edit' ), 'SetDescription', $entityId, $editable );

		if ( $descriptionExists ) {
			return $this->getDescriptionWrapperHTML(
				$descriptions->getByLanguage( $this->languageCode )->getText(),
				$editSection
			);
		} else {
			return $this->getDescriptionWrapperHTML(
				wfMessage( 'wikibase-label-empty' )->text(),
				$editSection
			);
		}
	}

	private function getDescriptionWrapperHTML( $content, $editSection ) {
		return wfTemplate( 'wb-description',
			wfTemplate( 'wb-property',
				'wb-value-empty',
				htmlspecialchars( $content ),
				$editSection
			)
		);
	}

	/**
	 * Builds and returns the HTML representing aliases.
	 *
	 * @param AliasGroupList $aliasGroups the list of alias groups to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	private function getHtmlForAliases( AliasGroupList $aliasGroups, EntityId $entityId = null, $editable = true ) {
		$aliasesExist = $aliasGroups->hasGroupForLanguage( $this->languageCode );
		$message = wfMessage( 'wikibase-' . $aliasesExist ? 'edit' : 'add' );
		$editSection = $this->getHtmlForEditSection( $message, 'SetAliases', $entityId, $editable );

		if ( $aliasesExist ) {
			$aliasList = $this->getAliasListHTML( $aliasGroups->getByLanguage( $this->languageCode )->getAliases() );

			return wfTemplate( 'wb-aliases-wrapper',
				'',
				'',
				wfMessage( 'wikibase-aliases-label' )->text(),
				$aliasList . $editSection
			);
		} else {
			return wfTemplate( 'wb-aliases-wrapper',
				'wb-aliases-empty',
				'wb-value-empty',
				wfMessage( 'wikibase-aliases-empty' )->text(),
				$editSection
			);
		}
	}

	private function getAliasListHTML( array $aliases ) {
		$aliasesHtml = '';
		foreach ( $aliases as $alias ) {
			$aliasesHtml .= wfTemplate( 'wb-alias', htmlspecialchars( $alias ) );
		}
		return wfTemplate( 'wb-aliases', $aliasesHtml );
	}

}
