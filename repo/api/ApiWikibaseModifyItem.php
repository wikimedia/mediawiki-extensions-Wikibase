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
		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
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

		$this->validateParameters( $params );

		$success = false;

		if ( !isset( $params['id'] ) ) {
			$params['id'] = WikibaseItem::getIdForSiteLink( $params['site'], $params['title'] );

			if ( $params['id'] === false && $params['item'] === 'update' ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-link' ), 'no-such-item-link' );
			}
		}

		if ( $params['id'] !== false && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-exists' ), 'add-exists' );
		}

		if ( isset( $params['id'] ) && $params['id'] !== false ) {
			$page = WikibaseItem::getWikiPageForId( $params['id'] );

			if ( $page->exists() ) {
				$item = $page->getContent();
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
			}
		}
		else {
			// TODO: find good way to do this. Seems like we need a WikiPage::setContent
			$item = WikibaseItem::newEmpty();
			$success = $item->save();

			if ( $success ) {
				$page = $item->getWikiPage();
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-create-failed' ), 'create-failed' );
			}
		}

		if ( $item->getModelName() === CONTENT_MODEL_WIKIBASE ) {
			$success = $this->modifyItem( $item, $params );

			if ( $success ) {
				// TODO: only does update
				$status = $page->doEditContent(
					$item,
					$params['summary'],
					EDIT_AUTOSUMMARY,
					false,
					$this->getUser(),
					'application/json' // TODO: this should not be needed here? (w/o it stuff is stored as wikitext...)
				);

				$success = $status->isOk();
			}
		}
		else {
			$this->dieUsage( wfMsg( 'wikibase-api-invalid-contentmodel' ), 'invalid-contentmodel' );
		}

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'invalid-contentmodel', 'info' => 'The content model of the page on which the item is stored is invalid' ),
		) );
	}

	public function needsToken() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function mustBePosted() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function getAllowedParams() {
		return array(
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
			'id' => 'The ID of the item',
			'site' => 'An identifier for the site on which the page resides',
			'title' => 'Title of the page to associate',
			'summary' => 'Summary for the edit',
		);
	}

}
