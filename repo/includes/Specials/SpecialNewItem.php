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
	private $siteId;

	/**
	 * @var string|null
	 */
	private $pageName;

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

		$this->siteId = $this->getRequest()->getVal( 'site' );
		$this->pageName = $this->getRequest()->getVal( 'page' );
	}

	/**
	 * @return bool
	 */
	private function isSiteLinkProvided() {
		return $this->siteId !== null && $this->pageName !== null;
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
	protected function modifyEntity( EntityDocument $item ) {
		$status = parent::modifyEntity( $item );

		if ( $this->isSiteLinkProvided() ) {
			if ( !( $item instanceof Item ) ) {
				throw new InvalidArgumentException( 'Unexpected entity type' );
			}

			$site = $this->siteStore->getSite( $this->siteId );

			if ( $site === null ) {
				$status->error( 'wikibase-newitem-not-recognized-siteid' );
				return $status;
			}

			$normalizedPageName = $site->normalizePageName( $this->pageName );

			if ( $normalizedPageName === false ) {
				$status->error( 'wikibase-newitem-no-external-page', $this->siteId, $this->pageName );
				return $status;
			}

			$item->getSiteLinkList()->addNewSiteLink( $this->siteId, $normalizedPageName );
		}

		return $status;
	}

	/**
	 * @see SpecialNewEntity::additionalFormElements
	 *
	 * @return array[]
	 */
	protected function additionalFormElements() {
		$formDescriptor = parent::additionalFormElements();

		if ( $this->isSiteLinkProvided() ) {
			$formDescriptor['site'] = [
				'name' => 'site',
				'default' => $this->siteId,
				'type' => 'text',
				'id' => 'wb-newitem-site',
				'readonly' => 'readonly',
				'label-message' => 'wikibase-newitem-site'
			];

			$formDescriptor['page'] = [
				'name' => 'page',
				'default' => $this->pageName,
				'type' => 'text',
				'id' => 'wb-newitem-page',
				'readonly' => 'readonly',
				'label-message' => 'wikibase-newitem-page'
			];
		}

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
	 * @return string[]
	 */
	protected function getWarnings() {
		if ( $this->getUser()->isAnon() ) {
			return [
				$this->msg( 'wikibase-anonymouseditwarning', $this->msg( 'wikibase-entity-item' ) ),
			];
		}

		return [];
	}

}
