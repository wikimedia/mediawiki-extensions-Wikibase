<?php

/**
 * Page for creating new Wikibase items.
 *
 * @since 0.1
 *
 * @file 
 * @ingroup Wikibase
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
		parent::__construct( 'NewItem' );
	}

	/**
	 * @see SpecialNewEntity::prepareArguments()
	 */
	protected function prepareArguments() {
		parent::prepareArguments();
		$this->site = $this->getRequest()->getVal( 'site', null );
		$this->page = $this->getRequest()->getVal( 'page', null );
		return true;
	}

	/**
	 * @see SpecialNewEntity::createEntityContent
	 */
	protected function createEntityContent() {
		return \Wikibase\ItemContent::newEmpty();
	}

	/**
	 * @see SpecialNewEntity::modifyEntity()
	 */
	protected function modifyEntity( \Wikibase\EntityContent &$itemContent ) {
		$status = parent::modifyEntity( $itemContent );

		if ( $this->site !== null && $this->page !== null ) {
			$site = \Sites::singleton()->getSite( $this->site );
			if ( $site === false ) {
				$status->error( 'wikibase-newitem-not-recognized-siteid' );
				return $status;
			}

			$page = $site->normalizePageName( $this->page );
			if ( $page === false ) {
				$status->error( 'wikibase-newitem-no-external-page' );
				return $status;
			}

			$link = new \Wikibase\SiteLink( $site, $page );
			$ret = $itemContent->getItem()->addSiteLink( $link, 'add' );
			if ( $ret === false ) {
				$status->error( 'wikibase-newitem-add-sitelink-failed' );
				return $status;
			}
		}

		return $status;
	}

	/**
	 * @see SpecialNewEntity::additionalFormElements()
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
	 * @see SpecialNewEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newitem-fieldset' );
	}

}
