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

		$html .= $this->getHtmlForLabel( $labels, $entityId, $editable );
		$html .= $this->getHtmlForDescription( $descriptions, $entityId, $editable );
		$html .= wfTemplate( 'wb-entity-header-separator' );
		$html .= $this->getHtmlForAliases( $aliasGroups, $entityId, $editable );

		return $html;
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
		$editSection = $this->getHtmlForEditSection( 'SetLabel', $entityId, $editable );
		if ( $entityId !== null ) {
			$idString = $entityId->getSerialization();
			$editSection = wfTemplate( 'wb-property-value-supplement', wfMessage( 'parentheses', $idString ) ) . $editSection;
		} else {
			$idString = 'new';
		}

		if ( $labelExists ) {
			return wfTemplate( 'wb-label',
				$idString,
				wfTemplate( 'wb-property',
					'',
					htmlspecialchars( $labels->getByLanguage( $this->languageCode )->getText() ),
					$editSection
				)
			);
		} else {
			return wfTemplate( 'wb-label',
				$idString,
				wfTemplate( 'wb-property',
					'wb-value-empty',
					wfMessage( 'wikibase-label-empty' )->text(),
					$editSection
				)
			);
		}
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
		$editSection = $this->getHtmlForEditSection( 'SetDescription', $entityId, $editable );

		if ( $descriptionExists ) {
			return wfTemplate( 'wb-description',
				wfTemplate( 'wb-property',
					'',
					htmlspecialchars( $descriptions->getByLanguage( $this->languageCode )->getText() ),
					$editSection
				)
			);
		} else {
			return wfTemplate( 'wb-description',
				wfTemplate( 'wb-property',
					'wb-value-empty',
					wfMessage( 'wikibase-description-empty' )->text(),
					$editSection
				)
			);
		}
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
		$action = $aliasesExist ? 'edit' : 'add';
		$editSection = $this->getHtmlForEditSection( 'SetAliases', $entityId, $editable, $action );

		if ( $aliasesExist ) {
			$aliases = $aliasGroups->getByLanguage( $this->languageCode )->getAliases();
			$aliasesHtml = '';
			foreach ( $aliases as $alias ) {
				$aliasesHtml .= wfTemplate( 'wb-alias', htmlspecialchars( $alias ) );
			}
			$aliasesList = wfTemplate( 'wb-aliases', $aliasesHtml );

			return wfTemplate( 'wb-aliases-wrapper',
				'',
				'',
				wfMessage( 'wikibase-aliases-label' )->text(),
				$aliasesList . $editSection
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

	/**
	 * Builds and returns the HTML for the edit section.
	 *
	 * @param string $specialPageName
	 * @param EntityId|null $entityId
	 * @param bool $editable
	 * @param string $action
	 * @return string
	 */
	private function getHtmlForEditSection( $specialPageName, EntityId $entityId = null, $editable = true, $action = 'edit' ) {
		if ( $entityId !== null && $editable ) {
			return $this->sectionEditLinkGenerator->getHtmlForEditSection(
				$specialPageName,
				array( $entityId->getSerialization(), $this->languageCode ),
				wfMessage( 'wikibase-' . $action )
			);
		} else {
			return '';
		}
	}

}
