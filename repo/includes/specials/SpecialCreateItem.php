<?php

/**
 * Page for creating new Wikibase items.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialCreateItem extends SpecialCreateEntity {

	/**
	 * @var string|null
	 */
	protected $site;

	/**
	 * @var string|null
	 */
	protected $page;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'CreateItem' );
	}

	/**
	 * @see SpecialCreateEntity::prepareArguments
	 *
	 * @return boolean
	 */
	protected function prepareArguments() {
		parent::prepareArguments();
		$this->site = $this->getRequest()->getVal( 'site', null );
		$this->page = $this->getRequest()->getVal( 'page', null );
		return true;
	}

	/**
	 * @see SpecialCreateEntity::createEntityContent
	 *
	 * @return \Wikibase\ItemContent
	 */
	protected function createEntityContent() {
		return \Wikibase\ItemContent::newEmpty();
	}

	/**
	 * @see SpecialCreateEntity::modifyEntity
	 *
	 * @param \Wikibase\EntityContent $itemContent
	 *
	 * @return Status
	 */
	protected function modifyEntity( \Wikibase\EntityContent &$itemContent ) {
		$status = parent::modifyEntity( $itemContent );

		if ( $this->site !== null && $this->page !== null ) {
			$site = \Sites::singleton()->getSite( $this->site );
			if ( $site === false ) {
				$status->error( 'wikibase-createitem-not-recognized-siteid' );
				return $status;
			}

			$page = $site->normalizePageName( $this->page );
			if ( $page === false ) {
				$status->error( 'wikibase-createitem-no-external-page' );
				return $status;
			}

			$link = new \Wikibase\SiteLink( $site, $page );
			$ret = $itemContent->getItem()->addSiteLink( $link, 'add' );
			if ( $ret === false ) {
				$status->error( 'wikibase-createitem-add-sitelink-failed' );
				return $status;
			}
		}

		return $status;
	}

	/**
	 * @see SpecialCreateEntity::additionalFormElements
	 *
	 * @return string
	 */
	protected function additionalFormElements() {
		if ( $this->site === null || $this->page === null ) {
			return parent::additionalFormElements();
		}

		return parent::additionalFormElements()
		. Html::element(
			'label',
			array(
				'for' => 'wb-createitem-site',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-createitem-site' )->text()
		)
		. Html::input(
			'site',
			$this->site,
			'text',
			array(
				'id' => 'wb-createitem-site',
				'size' => 12,
				'class' => 'wb-input',
				'readonly' => 'readonly'
			)
		)
		. Html::element( 'br' )
		. Html::element(
			'label',
			array(
				'for' => 'wb-createitem-page',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-createitem-page' )->text()
		)
		. Html::input(
			'page',
			$this->page,
			'text',
			array(
				'id' => 'wb-createitem-page',
				'size' => 12,
				'class' => 'wb-input',
				'readonly' => 'readonly'
			)
		)
		. Html::element( 'br' );
	}

	/**
	 * @see SpecialCreateEntity::getLegend
	 *
	 * @return string
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-createitem-fieldset' );
	}

	/**
	 * @see SpecialCreateEntity::getWarnings
	 *
	 * @return array
	 */
	protected function getWarnings() {
		$warnings = array();

		if ( $this->getUser()->isAnon() ) {
			$warnings[] = $this->msg( 'wikibase-anonymouseditwarning-item' );
		}

		return $warnings;
	}

}
