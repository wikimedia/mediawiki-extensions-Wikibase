<?php

namespace Wikibase\Repo\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;

/**
 * Generates HTML to display the fingerprint of an entity
 * in the user's current language.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author Thiemo Mättig
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
	 * @param Fingerprint $fingerprint the fingerprint to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
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
	 * @param TermList $labels the list of labels to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	private function getHtmlForLabel( TermList $labels, EntityId $entityId = null, $editable ) {
		$hasLabel = $labels->hasTermForLanguage( $this->languageCode );
		$editSection = $this->getHtmlForEditSection( 'SetLabel', $entityId, $editable );

		if ( $entityId === null ) {
			$idString = 'new';
			$suplement = '';
		} else {
			$idString = $entityId->getSerialization();
			$suplement = wfTemplate( 'wb-property-value-supplement', wfMessage( 'parentheses', $idString ) );
		}

		if ( $hasLabel ) {
			return wfTemplate( 'wb-label',
				$idString,
				wfTemplate( 'wb-property',
					'',
					htmlspecialchars( $labels->getByLanguage( $this->languageCode )->getText() ),
					$suplement . $editSection
				)
			);
		} else {
			return wfTemplate( 'wb-label',
				$idString,
				wfTemplate( 'wb-property',
					'wb-value-empty',
					wfMessage( 'wikibase-label-empty' )->escaped(),
					$suplement . $editSection
				)
			);
		}
	}

	/**
	 * @param TermList $descriptions the list of descriptions to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	private function getHtmlForDescription( TermList $descriptions, EntityId $entityId = null, $editable ) {
		$hasDescription = $descriptions->hasTermForLanguage( $this->languageCode );
		$editSection = $this->getHtmlForEditSection( 'SetDescription', $entityId, $editable );

		if ( $hasDescription ) {
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
					wfMessage( 'wikibase-description-empty' )->escaped(),
					$editSection
				)
			);
		}
	}

	/**
	 * @param AliasGroupList $aliasGroups the list of alias groups to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	private function getHtmlForAliases( AliasGroupList $aliasGroups, EntityId $entityId = null, $editable ) {
		$hasAliases = $aliasGroups->hasGroupForLanguage( $this->languageCode );
		$action = $hasAliases ? 'edit' : 'add';
		$editSection = $this->getHtmlForEditSection( 'SetAliases', $entityId, $editable, $action );

		if ( $hasAliases ) {
			$aliasesHtml = '';
			$aliases = $aliasGroups->getByLanguage( $this->languageCode )->getAliases();
			foreach ( $aliases as $alias ) {
				$aliasesHtml .= wfTemplate( 'wb-alias', htmlspecialchars( $alias ) );
			}
			$aliasesList = wfTemplate( 'wb-aliases', $aliasesHtml );

			return wfTemplate( 'wb-aliases-wrapper',
				'',
				'',
				wfMessage( 'wikibase-aliases-label' )->escaped(),
				$aliasesList . $editSection
			);
		} else {
			return wfTemplate( 'wb-aliases-wrapper',
				'wb-aliases-empty',
				'wb-value-empty',
				wfMessage( 'wikibase-aliases-empty' )->escaped(),
				$editSection
			);
		}
	}

	/**
	 * @param string $specialPageName
	 * @param EntityId|null $entityId
	 * @param bool $editable
	 * @param string $action by default 'edit', for aliases this could also be 'add'
	 *
	 * @return string
	 */
	private function getHtmlForEditSection( $specialPageName, EntityId $entityId = null, $editable, $action = 'edit' ) {
		if ( $entityId === null || !$editable ) {
			return '';
		}

		return $this->sectionEditLinkGenerator->getHtmlForEditSection(
			$specialPageName,
			array( $entityId->getSerialization(), $this->languageCode ),
			wfMessage( $action === 'add' ? 'wikibase-add' : 'wikibase-edit' )
		);
	}

}
