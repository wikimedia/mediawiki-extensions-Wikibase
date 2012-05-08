<?php

/**
 * Base class for API modules modifying a single item identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file ApiWikibaseModifyItem.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ApiWikibaseModifyItem extends ApiBase {

	/**
	 * Actually modify the item.
	 *
	 * @since 0.1
	 *
	 * @param WikibaseItem $item
	 * @param array $params
	 *
	 * @return boolean Success indicator
	 */
	protected abstract function modifyItem( WikibaseItem &$item, array $params );

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) )
			&& !( isset( $params['item'] ) && $params['item'] === 'add' ) ) {

			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}

		if ( isset( $params['id'] ) && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-with-id' ), 'add-with-id' );
		}
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$hasLink = isset( $params['site'] ) && $params['title'];
		$item = null;

		$this->validateParameters( $params );

		if ( $params['item'] === 'update' && !isset( $params['id'] ) && !$hasLink ) {
			$this->dieUsage( wfMsg( 'wikibase-api-update-without-id' ), 'update-without-id' );
		}

		if ( isset( $params['id'] ) ) {
			$item = WikibaseItem::getFromId( $params['id'] );

			if ( is_null( $item ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
			}
		}
		elseif ( $hasLink ) {
			$item = WikibaseItem::getFromSiteLink( $params['site'], $params['title'] );

			if ( is_null( $item ) && $params['item'] === 'update' ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-link' ), 'no-such-item-id' );
			}
		}

		if ( !is_null( $item ) && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-exists' ), 'add-exists', 0, array( 'item' => array( 'id' => $params['id'] ) ) );
		}

		if ( is_null( $item ) ) {
			$item = WikibaseItem::newEmpty();

			if ( $hasLink ) {
				$item->addSiteLink( $params['site'], $params['title'] );
			}
		}

		$this->modifyItem( $item, $params );

		$isNew = $item->isNew();
		$success = $item->save();

		if ( !$success ) {
			if ( $isNew ) {
				$this->dieUsage( wfMsg( 'wikibase-api-create-failed' ), 'create-failed' );
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-save-failed' ), 'save-failed' );
			}
		}

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);

		if ( $success ) {
			$this->getResult()->addValue(
				null,
				'item',
				array(
					'id' => $item->getId()
				)
			);
		}
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'id-xor-wikititle', 'info' => 'You need to either provide the item id or the title of a corresponding page and the identifier for the wiki this page is on' ),
			array( 'code' => 'add-with-id', 'info' => 'Can not add with an item id' ),
			array( 'code' => 'add-exists', 'info' => 'Can not add to an existing item' ),
			array( 'code' => 'no-such-item-link', 'info' => 'Could not find an existing item for this link' ),
			array( 'code' => 'no-such-item-id', 'info' => 'Could not find an existing item for this id' ),
			array( 'code' => 'create-failed', 'info' => 'Attempted creation of new item failed' ),
			array( 'code' => 'invalid-contentmodel', 'info' => 'The content model of the page on which the item is stored is invalid' ),
		) );
	}

	public function needsToken() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function mustBePosted() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function isWriteMode() {
		return !WBSettings::get( 'apiInDebug' );
	}
	
	public function getAllowedParams() {
		return array(
			'create' => array(
				ApiBase::PARAM_TYPE => 'boolean',
			),
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'site' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => __CLASS__, // TODO
			),
			'item' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'update', 'set' ),
				ApiBase::PARAM_DFLT => 'update',
			),
		);
	}

	public function getParamDescription() {
		return array(
			'id' => array( 'The ID of the item.',
				"Use either 'id' or 'site' and 'title' together."
			),
			'site' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'title'."
			),
			'title' => array( 'Title of the page to associate.',
				"Use together with 'site'."
			),
			'item' => 'Indicates if you are changing the content of the item',
			'summary' => 'Summary for the edit.',
		);
	}

}
