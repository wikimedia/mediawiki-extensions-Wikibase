<?php

namespace Wikibase\Repo\Specials;

use Html;
use InvalidArgumentException;
use Status;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;

/**
 * Page for creating new Wikibase items.
 *
 * @since 0.1
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
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'NewItem' );
	}

	/**
	 * @see SpecialNewEntity::prepareArguments
	 */
	protected function prepareArguments() {
		parent::prepareArguments();

		$this->site = $this->getRequest()->getVal( 'site' );
		$this->page = $this->getRequest()->getVal( 'page' );
	}

	/**
	 * @see SpecialNewEntity::createEntity
	 *
	 * @return Item
	 */
	protected function createEntity() {
		return new Item();
	}

	/**
	 * @see SpecialNewEntity::modifyEntity
	 *
	 * @param Entity $item
	 *
	 * @throws InvalidArgumentException
	 * @return Status
	 */
	protected function modifyEntity( Entity &$item ) {
		$status = parent::modifyEntity( $item );

		if ( $this->site !== null && $this->page !== null ) {
			if ( !( $item instanceof Item ) ) {
				throw new InvalidArgumentException( 'Unexpected entity type' );
			}

			$site = $this->siteStore->getSite( $this->site );
			if ( $site === null ) {
				$status->error( 'wikibase-newitem-not-recognized-siteid' );
				return $status;
			}

			$page = $site->normalizePageName( $this->page );
			if ( $page === false ) {
				$status->error( 'wikibase-newitem-no-external-page' );
				return $status;
			}

			$item->getSiteLinkList()->addNewSiteLink( $site->getGlobalId(), $page );
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
		. Html::label(
			$this->msg( 'wikibase-newitem-site' )->text(),
			'wb-newitem-site',
			array(
				'class' => 'wb-label'
			)
		)
		. Html::input(
			'site',
			$this->site,
			'text',
			array(
				'id' => 'wb-newitem-site',
				'class' => 'wb-input',
				'readonly' => 'readonly'
			)
		)
		. Html::label(
			$this->msg( 'wikibase-newitem-page' )->text(),
			'wb-newitem-page',
			array(
				'class' => 'wb-label'
			)
		)
		. Html::input(
			'page',
			$this->page,
			'text',
			array(
				'id' => 'wb-newitem-page',
				'class' => 'wb-input',
				'readonly' => 'readonly'
			)
		);
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
