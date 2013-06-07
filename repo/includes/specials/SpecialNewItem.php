<?php

use Wikibase\DataModel\SimpleSiteLink;

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
class SpecialNewItem extends SpecialNewEntity {

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
		parent::__construct( 'NewItem', 'item-create' );
	}

	/**
	 * @see SpecialNewEntity::prepareArguments
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
	 * @see SpecialNewEntity::createEntityContent
	 *
	 * @return \Wikibase\ItemContent
	 */
	protected function createEntityContent() {
		return \Wikibase\ItemContent::newEmpty();
	}

	/**
	 * @see SpecialNewEntity::modifyEntity
	 *
	 * @param \Wikibase\EntityContent $itemContent
	 *
	 * @return Status
	 */
	protected function modifyEntity( \Wikibase\EntityContent &$itemContent ) {
		$status = parent::modifyEntity( $itemContent );

		if ( $this->site !== null && $this->page !== null ) {
			$site = \SiteSQLStore::newInstance()->getSite( $this->site );

			if ( $site === null ) {
				$status->error( 'wikibase-newitem-not-recognized-siteid' );
				return $status;
			}

			$page = $site->normalizePageName( $this->page );
			if ( $page === false ) {
				$status->error( 'wikibase-newitem-no-external-page' );
				return $status;
			}

			$link = new SimpleSiteLink( $site->getGlobalId(), $page );
			$ret = $itemContent->getItem()->addSimpleSiteLink( $link );

			if ( $ret === false ) {
				$status->error( 'wikibase-newitem-add-sitelink-failed' );
				return $status;
			}
		}

		return $status;
	}

	/**
	 * @see SpecialNewEntity::additionalFormElements
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
				'for' => 'wb-newitem-site',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-newitem-site' )->text()
		)
		. Html::input(
			'site',
			$this->site,
			'text',
			array(
				'id' => 'wb-newitem-site',
				'size' => 12,
				'class' => 'wb-input',
				'readonly' => 'readonly'
			)
		)
		. Html::element( 'br' )
		. Html::element(
			'label',
			array(
				'for' => 'wb-newitem-page',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-newitem-page' )->text()
		)
		. Html::input(
			'page',
			$this->page,
			'text',
			array(
				'id' => 'wb-newitem-page',
				'size' => 12,
				'class' => 'wb-input',
				'readonly' => 'readonly'
			)
		)
		. Html::element( 'br' );
	}

	/**
	 * @see SpecialNewEntity::getLegend
	 *
	 * @return string
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newitem-fieldset' );
	}

	/**
	 * @see SpecialCreateEntity::getWarnings
	 *
	 * @return array
	 */
	protected function getWarnings() {
		$warnings = array();

		if ( $this->getUser()->isAnon() ) {
			$warnings[] = $this->msg(
				'wikibase-anonymouseditwarning',
				$this->msg( 'wikibase-entity-item' )
			);
		}

		return $warnings;
	}

}
