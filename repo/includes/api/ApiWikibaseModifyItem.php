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

		$success = false;

		// TODO: Start of bailout
		// our bail out if we can't identify an existing item
		if ( !isset( $params['id'] ) && !isset( $params['site'] ) && !isset( $params['title'] ) ) {
			$item = WikibaseItem::newEmpty();
			// we need a save to get an item id
			$success = $item->save();
			$params['id'] = $item->getId();
			if (!$success) {
				// a little bit odd error message
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}
		// because we commented out the required parameters we must test manually
		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}
		//TODO: End of bailout

		$this->validateParameters( $params );
		
		if ( !isset( $params['id'] ) ) {
			$params['id'] = WikibaseItem::getIdForSiteLink( $params['site'], $params['title'] );

			if ( $params['id'] === false && $params['item'] === 'update' ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-link' ), 'no-such-item-link' );
			}
		}

		if ( $params['id'] !== false && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-exists' ), 'add-exists', 0, array( 'item' => array( 'id' => $params['id'] ) ) );
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
			// now we should never be here
			// TODO: find good way to do this. Seems like we need a WikiPage::setContent
			$item = WikibaseItem::newEmpty();
			//$success = $item->save();

			if ( $success ) {
				$page = $item->getWikiPage();

				if ( isset( $params['site'] ) && isset( $params['title'] ) ) {
					$item->addSiteLink( $params['site'], $params['title'] );
				}
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-create-failed' ), 'create-failed' );
			}
		}

		if ( $item->getModelName() === CONTENT_MODEL_WIKIBASE ) {
			$success = $this->modifyItem( $item, $params );

			if ( $success ) {
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

		// this saves unconditionally if we had a success so far
		// it could be interesting to avoid storing if the item is in fact not changed
		// or if the saves could be queued somehow
		if ($success) {
			$success = $item->save();
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
			'item' => 'Indicates if you are changing the content of the item',
			'summary' => 'Summary for the edit.',
		);
	}

}
