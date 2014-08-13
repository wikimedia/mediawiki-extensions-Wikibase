<?php

namespace Wikibase\Repo\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Term;

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
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param string $languageCode
	 *
	 * @since 0.5
	 */
	public function __construct( $languageCode ) {
		$this->languageCode = $languageCode;
	}

	/**
	 * Builds and returns the HTML representing a 
	 *
	 * @param Fingerprint $fingerprint the fingerprint to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtml( Fingerprint $fingerprint, EntityId $entityId = null, $editable = true ) {
		$label = $fingerprint->getLabel( $this->languageCode );
		$description = $fingerprint->getDescription( $this->languageCode );
		$aliases = $fingerprint->getAliases( $this->languageCode );
		$html = '';

		$html .= $this->getHtmlForLabel( $label, $editable, $entityId );
		$html .= $this->getHtmlForDescription( $description, $editable, $entityId );
		$html .= wfTemplate( 'wb-entity-header-separator' );
		$html .= $this->getHtmlForAliases( $aliases, $editable, $entityId );

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a label.
	 *
	 * @param Term $term the term to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	private function getHtmlForLabel( Term $term, EntityId $entityId = null, $editable = true ) {
		$label = $term->getText();
		$languageCode = $term->getLanguageCode();
		$idString = 'new';
		$supplement = '';

		if ( $entityId !== null ) {
			$idString = $entityId->getSerialization();
			$supplement .= wfTemplate( 'wb-property-value-supplement', wfMessage( 'parentheses', $idString ) );
			if ( $editable ) {
				$supplement .= $this->getHtmlForEditSection( 'SetLabel', array( $idString, $languageCode ) );
			}
		}

		$html = wfTemplate( 'wb-label',
			$idString,
			wfTemplate( 'wb-property',
				$label === false ? 'wb-value-empty' : '',
				htmlspecialchars( $label === false ? wfMessage( 'wikibase-label-empty' )->text() : $label ),
				$supplement
			)
		);

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a description.
	 *
	 * @param Term $term the term to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	private function getHtmlForDescription( Term $term, EntityId $entityId = null, $editable = true ) {
		$description = $term->getText();
		$languageCode = $term->getLanguageCode();
		$editSection = '';

		if ( $entityId !== null && $editable ) {
			$idString = $entityId->getSerialization();
			$editSection .= $this->getHtmlForEditSection( 'SetDescription', array( $idString, $languageCode ) );
		}

		$html = wfTemplate( 'wb-description',
			wfTemplate( 'wb-property',
				$description === false ? 'wb-value-empty' : '',
				htmlspecialchars( $description === false ? wfMessage( 'wikibase-description-empty' )->text() : $description ),
				$editSection
			)
		);

		return $html;
	}

	/**
	 * Builds and returns the HTML representing aliases.
	 *
	 * @param AliasGroup $aliasGroup the alias group to render
	 * @param EntityId|null $entityId the id of the fingerprint's entity
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	private function getHtmlForAliases( AliasGroup $aliasGroup, EntityId $entityId = null, $editable = true ) {
		$aliases = $aliasGroup->getAliases();
		$languageCode = $aliasGroup->getLanguageCode();
		$editSection = '';

		if ( $entityId !== null && $editable ) {
			$idString = $entityId->getSerialization();
			$action = empty( $aliases ) ? 'add' : 'edit';
			$editSection = $this->getHtmlForEditSection( 'SetAliases', array( $idString, $languageCode ), $action );
		}

		if ( empty( $aliases ) ) {
			$html = wfTemplate( 'wb-aliases-wrapper',
				'wb-aliases-empty',
				'wb-value-empty',
				wfMessage( 'wikibase-aliases-empty' )->text(),
				$editSection
			);
		} else {
			$aliasesHtml = '';
			foreach ( $aliases as $alias ) {
				$aliasesHtml .= wfTemplate( 'wb-alias', htmlspecialchars( $alias ) );
			}
			$aliasList = wfTemplate( 'wb-aliases', $aliasesHtml );

			$html = wfTemplate( 'wb-aliases-wrapper',
				'',
				'',
				wfMessage( 'wikibase-aliases-label' )->text(),
				$aliasList . $editSection
			);
		}

		return $html;
	}

}
