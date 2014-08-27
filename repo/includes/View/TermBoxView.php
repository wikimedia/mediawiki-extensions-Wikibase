<?php

namespace Wikibase\Repo\View;

use Language;
use Message;
use Title;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Term\AliasGroupList;
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

		$entityId = $entity->getId()->getSerialization();
		$fingerprint = $entity->getFingerprint();
		$labels = $fingerprint->getLabels();
		$descriptions = $fingerprint->getDescriptions();
		$aliasGroups = $fingerprint->getAliasGroups();

		$tbody = '';

		foreach ( $languageCodes as $languageCode ) {
			$label = array_key_exists( $languageCode, $labels ) ? $labels[$languageCode] : false;
			$description = array_key_exists( $languageCode, $descriptions )
				? $descriptions[$languageCode]
				: false;

			$editLabelSection = $this->sectionEditLinkGenerator->getHtmlForEditSection(
				'SetLabel',
				array( $entityId, $languageCode ),
				$this->msg( 'wikibase-edit' ),
				$editable
			);
			$editDescriptionSection = $this->sectionEditLinkGenerator->getHtmlForEditSection(
				'SetDescription',
				array( $entityId, $languageCode ),
				$this->msg( 'wikibase-edit' ),
				$editable
			);
			$editAliasesSection = $this->sectionEditLinkGenerator->getHtmlForEditSection(
				'SetAliases',
				array( $entityId, $languageCode ),
				$this->msg( 'wikibase-edit' ),
				$editable
			);

			$tbody .= wfTemplate( 'wikibase-fingerprintview',
				$languageCode,
				$title->getLocalURL( array( 'setlang' => $languageCode ) ),
				htmlspecialchars( Utils::fetchLanguageName( $languageCode ) ),
				$label !== false ? '' : 'wb-empty',
				htmlspecialchars( $label !== false
					? $label
					: $this->msg( 'wikibase-label-empty' )->text()
				),
				'<td>' . $editLabelSection . '</td>',
				$description !== false ? '' : 'wb-empty',
				htmlspecialchars( $description !== false
					? $description
					: $this->msg( 'wikibase-description-empty' )->text()
				),
				'<td>' . $editDescriptionSection . '</td>',
				$aliasGroups->hasGroupForLanguage( $languageCode ) ? '' : 'wb-empty',
				$this->getHtmlForAliases( $aliasGroups, $languageCode ),
				'<td>' . $editAliasesSection . '</td>'
			);
		}

		$html = wfTemplate( 'wikibase-fingerprintgroupview',
			$this->msg( 'wikibase-terms' ),
			wfTemplate( 'wikibase-fingerprintlistview', $tbody )
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
			return wfTemplate( 'wikibase-aliasesview',
				'wb-empty',
				wfMessage( 'wikibase-aliases-empty' )->escaped(),
				'',
				''
			);
		} else {
			$aliasesHtml = '';
			$aliases = $aliasGroups->getByLanguage( $languageCode )->getAliases();
			foreach ( $aliases as $alias ) {
				$aliasesHtml .= wfTemplate(
					'wikibase-aliasesview-list-item',
					htmlspecialchars( $alias )
				);
			}

			return wfTemplate( 'wikibase-aliasesview',
				'',
				wfMessage( 'wikibase-aliases-label' )->escaped(),
				$aliasesHtml,
				''
			);
		}
	}
}
