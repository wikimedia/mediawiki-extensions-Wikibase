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
			$page = WikibaseUtils::getWikiPageForId( $params['id'] );

			if ( $page->exists() ) {
				$content = $page->getContent();
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
			}
		}
		else {
			// TODO: find good way to do this. Seems like we need a WikiPage::setContent
			$item = WikibaseItem::newFromArray( array() );
			$success = $item->structuredSave();

			if ( $success ) {
				$page = WikibaseUtils::getWikiPageForId( $item->getId() );
				$content = new WikibaseContent( array( 'entity' => $item->getId() ) );
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-create-failed' ), 'create-failed' );
			}
		}

		if ( $content->getModelName() === CONTENT_MODEL_WIKIBASE ) {
			$item = $content->getItem();

			$success = $this->modifyItem( $item, $params );

			if ( $success ) {
				$content->setItem( $item );
				// TODO: only does update
				$status = $page->doEditContent(
					$content,
					$params['summary'],
					EDIT_UPDATE | EDIT_AUTOSUMMARY,
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
			array( 'code' => 'id-xor-wikititle', 'info' => 'Needs an id or a combination of a site and wikititle' ),
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
			'id' => array( 'The ID of the item.',
				"Use either 'id' or 'site' and 'title' together."
			),
			'site' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'title'."
			),
			'title' => array( 'Title of the page to associate.',
				"Use together with 'site'."
			),
			'summary' => 'Summary for the edit.',
		);
	}

}
