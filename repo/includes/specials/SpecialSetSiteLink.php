<?php

use Wikibase\SiteLink;

/**
 * Special page for setting the sitelink of a Wikibase entity.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
class SpecialSetSiteLink extends SpecialSetEntity {

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetSiteLink' );
	}

	/**
	 * Returns the posted site id.
	 *
	 * @since 0.4
	 *
	 * @param array $parts the parts of the subpage
	 * @return string
	 */
	function getPostedLanguage( $parts ) {
		$language = $this->getRequest()->getVal( 'site', isset( $parts[1] ) ? $parts[1] : '' );
		if( $language === '' ) {
			$language = $this->getRequest()->getVal( 'language' ); // Fix for posted requests
		}
		return $language;
	}

	/**
	 * @see SpecialSetEntity::getPostedValue()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'sitelink' );
	}

	/**
	 * Checks if the site id is ok.
	 *
	 * @since 0.4
	 */
	protected function checkLanguage( $siteid ) {
		if( \Sites::singleton()->getSite( $siteid ) === false ) {
			$this->showError( $this->msg( 'wikibase-setsitelink-invalid-site', $siteid )->text() );
		}
	}

	/**
	 * Returns the full intro when both id and site are set.
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent the entity to have the value set
	 * @param string $site
	 */
	protected function getIntrofull( $entityContent, $site ) {
		return $this->msg(
			'wikibase-' . strtolower( $this->getName() ) . '-introfull',
			$entityContent->getTitle()->getPrefixedText(),
			'[' . \Sites::singleton()->getSite( $site )->getPageUrl( '' ) . ' ' . $site . ']'
		)->parse();
	}

	/**
	 * @see SpecialSetEntity::getLanguageForm()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getLanguageForm( $language ) {
		return Html::element(
			'label',
			array(
				'for' => 'wb-setentity-site',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-setsitelink-site' )->text()
		)
		. Html::input(
			'site',
			$language == $this->getLanguage()->getCode() ? '' : $language,
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-setentity-site'
			)
		);
	}

	/**
	 * @see SpecialSetEntity::getValue()
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 *
	 * @return string
	 */
	protected function getValue( $entityContent, $language ) {
		if( $entityContent === null ) {
			return '';
		}
		$sitelink = $entityContent->getEntity()->getSitelink( $language );
		if( $sitelink === null ) {
			return '';
		}
		return $sitelink->getPage();
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 * @param string $value
	 * @param string &$summary The summary for this edit will be saved here.
	 *
	 * @return Status
	 */
	protected function setValue( $entityContent, $language, $value, &$summary ) {
		$site = \Sites::singleton()->getSite( $language );
		$status = \Status::newGood();

		if( $site === false ) {
			$status->error( 'wikibase-setsitelink-invalid-site', $language );
			return $status;
		}
		if ( $site->normalizePageName( $value ) === false && $value !== '' ) {
			$status->error( 'wikibase-error-ui-no-external-page' );
			return $status;
		}

		if( $value === '' ) {
			$entityContent->getEntity()->removeSitelink( $language );
			$i18n = 'wbsetsitelink-remove';
		}
		else {
			$siteLink = new SiteLink( $site, $value );
			$entityContent->getEntity()->addSiteLink( $siteLink, 'set' );
			$i18n = 'wbsetsitelink-set';
		}
		$summary = $this->getSummary( $language, $value, $i18n );
		return $status;
	}
}