<?php

namespace Wikibase\Repo\Specials;

use Exception;
use HTMLForm;
use Html;
use Message;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\Localizer\ExceptionLocalizer;

/**
 * Special page for merging one item to another.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 */
class SpecialMergeItems extends SpecialWikibasePage {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var ExceptionLocalizer
	 */
	private $exceptionLocalizer;

	/**
	 * @var ItemMergeInteractor
	 */
	private $interactor;

	/**
	 * @var TokenCheckInteractor
	 */
	private $tokenCheck;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct(
		EntityIdParser $idParser,
		ExceptionLocalizer $exceptionLocalizer,
		TokenCheckInteractor $tokenCheck,
		ItemMergeInteractor $interactor,
		EntityTitleLookup $titleLookup
	) {
		parent::__construct( 'MergeItems', 'item-merge' );

		$this->idParser = $idParser;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->tokenCheck = $tokenCheck;
		$this->interactor = $interactor;
		$this->titleLookup = $titleLookup;
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @param string $name
	 *
	 * @return ItemId|null
	 * @throws UserInputException
	 */
	private function getItemIdParam( $name ) {
		$rawId = $this->getTextParam( $name );

		if ( $rawId === '' ) {
			return null;
		}

		try {
			$id = $this->idParser->parse( $rawId );

			if ( !( $id instanceof ItemId ) ) {
				throw new UserInputException(
					'wikibase-itemmerge-not-item',
					[],
					"$name \"$rawId\" does not refer to an Item"
				);
			}

			return $id;
		} catch ( EntityIdParsingException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-invalid-id',
				[ $rawId ],
				"$name \"$rawId\" is not valid"
			);
		}
	}

	private function getStringListParam( $name ) {
		$list = $this->getTextParam( $name );

		return $list === '' ? [] : explode( '|', $list );
	}

	private function getTextParam( $name ) {
		$value = $this->getRequest()->getText( $name, '' );
		return trim( $value );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->checkReadOnly();

		$this->setHeaders();
		$this->outputHeader();

		try {
			$fromId = $this->getItemIdParam( 'fromid' );
			$toId = $this->getItemIdParam( 'toid' );

			$ignoreConflicts = $this->getStringListParam( 'ignoreconflicts' );
			$summary = $this->getTextParam( 'summary' );

			if ( $fromId && $toId ) {
				$this->mergeItems( $fromId, $toId, $ignoreConflicts, $summary );
			}
		} catch ( ItemMergeException $ex ) {
			if ( $ex->getPrevious() instanceof RevisionedUnresolvedRedirectException ) {
				$this->showErrorHTML( $this->msg( 'wikibase-itemmerge-redirect' )->parse() );
			} else {
				$this->showExceptionMessage( $ex );
			}
		} catch ( Exception $ex ) {
			$this->showExceptionMessage( $ex );
		}

		$this->createForm();
	}

	protected function showExceptionMessage( Exception $ex ) {
		$msg = $this->exceptionLocalizer->getExceptionMessage( $ex );

		$this->showErrorHTML( $msg->parse() );

		// Report chained exceptions recursively
		if ( $ex->getPrevious() ) {
			$this->showExceptionMessage( $ex->getPrevious() );
		}
	}

	/**
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param string[] $ignoreConflicts
	 * @param string $summary
	 */
	private function mergeItems( ItemId $fromId, ItemId $toId, array $ignoreConflicts, $summary ) {
		$this->tokenCheck->checkRequestToken( $this->getRequest(), 'wpEditToken' );
		$fromTitle = $this->titleLookup->getTitleForId( $fromId );
		$toTitle = $this->titleLookup->getTitleForId( $toId );

		/** @var EntityRevision $newRevisionFrom  */
		/** @var EntityRevision $newRevisionTo */
		list( $newRevisionFrom, $newRevisionTo, )
			= $this->interactor->mergeItems( $fromId, $toId, $ignoreConflicts, $summary );

		$linkRenderer = $this->getLinkRenderer();
		$this->getOutput()->addWikiMsg(
			'wikibase-mergeitems-success',
			Message::rawParam(
				$linkRenderer->makePreloadedLink(
					$fromTitle,
					$fromId->getSerialization(),
					'mw-redirect',
					[],
					[ 'redirect' => 'no' ]
				)
			),
			$newRevisionFrom->getRevisionId(),
			Message::rawParam(
				$linkRenderer->makeKnownLink(
					$toTitle,
					$toId->getSerialization()
				)
			),
			$newRevisionTo->getRevisionId()
		);
	}

	/**
	 * Creates the HTML form for merging two items.
	 */
	protected function createForm() {
		$this->getOutput()->addModules( 'wikibase.special.mergeItems' );

		$pre = '';
		if ( $this->getUser()->isAnon() ) {
			$pre = Html::rawElement(
				'p',
				[ 'class' => 'warning' ],
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-item' )->text()
				)->parse()
			);
		}

		HTMLForm::factory( 'ooui', $this->getFormElements(), $this->getContext() )
			->setId( 'wb-mergeitems-form1' )
			->setPreText( $pre )
			->setHeaderText( $this->msg( 'wikibase-mergeitems-intro' )->parse() )
			->setSubmitID( 'wb-mergeitems-submit' )
			->setSubmitName( 'wikibase-mergeitems-submit' )
			->setSubmitTextMsg( 'wikibase-mergeitems-submit' )
			->setWrapperLegendMsg( 'special-mergeitems' )
			->setSubmitCallback( function () {// no-op
			} )->show();
	}

	/**
	 * @return array[]
	 */
	protected function getFormElements() {
		return [
			'fromid' => [
				'name' => 'fromid',
				'default' => $this->getRequest()->getVal( 'fromid' ),
				'type' => 'text',
				'id' => 'wb-mergeitems-fromid',
				'label-message' => 'wikibase-mergeitems-fromid'
			],
			'toid' => [
				'name' => 'toid',
				'default' => $this->getRequest()->getVal( 'toid' ),
				'type' => 'text',
				'id' => 'wb-mergeitems-toid',
				'label-message' => 'wikibase-mergeitems-toid'
			]
		];
		// TODO: Selector for ignoreconflicts
	}

}
