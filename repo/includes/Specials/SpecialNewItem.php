<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;

/**
 * Page for creating new Wikibase items.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialNewItem extends SpecialNewEntity {

	/**
	 * @var string|null
	 */
	private $site;

	/**
	 * @var string|null
	 */
	private $page;

	/**
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'NewItem' );
	}

	public function doesWrites() {
		return true;
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
	 * @param EntityDocument $item
	 *
	 * @throws InvalidArgumentException
	 * @return Status
	 */
	protected function modifyEntity( EntityDocument &$item ) {
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
	 * @return array
	 */
	protected function additionalFormElements() {
		if ( $this->site === null || $this->page === null ) {
			return parent::additionalFormElements();
		}

		$formDescriptor = parent::additionalFormElements();
		$formDescriptor['site'] = array(
			'name' => 'site',
			'default' => $this->site,
			'type' => 'text',
			'id' => 'wb-newitem-site',
			'readonly' => 'readonly',
			'label-message' => 'wikibase-newitem-site'
		);
		$formDescriptor['page'] = array(
			'name' => 'page',
			'default' => $this->page,
			'type' => 'text',
			'id' => 'wb-newitem-page',
			'readonly' => 'readonly',
			'label-message' => 'wikibase-newitem-page'
		);

		return $formDescriptor;
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
		$warnings = [];

		if ( $this->getUser()->isAnon() ) {
			$warnings[] = $this->msg(
				'wikibase-anonymouseditwarning',
				$this->msg( 'wikibase-entity-item' )
			);
		}

		return $warnings;
	}

}
