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
	 * @see SpecialCreateEntity::prepareArguments()
	 */
	protected function prepareArguments() {
		parent::prepareArguments();
		$this->site = $this->getRequest()->getVal( 'site', null );
		$this->page = $this->getRequest()->getVal( 'page', null );
		return true;
	}

	/**
	 * @see SpecialCreateEntity::createEntityContent
	 */
	protected function createEntityContent() {
		return \Wikibase\ItemContent::newEmpty();
	}

	/**
	 * @see SpecialCreateEntity::modifyEntity()
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
	 * @see SpecialCreateEntity::additionalFormElements()
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
	 * @see SpecialCreateEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-createitem-fieldset' );
	}

}
