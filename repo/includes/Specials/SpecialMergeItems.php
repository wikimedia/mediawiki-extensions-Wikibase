<?php

namespace Wikibase\Repo\Specials;

use Exception;
use HTMLForm;
use Html;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityRevision;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page for merging one item to another.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @since 0.5
	 */
	public function __construct() {
		parent::__construct( 'MergeItems', 'item-merge' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getExceptionLocalizer(),
			new TokenCheckInteractor(
				$this->getUser()
			),
			$wikibaseRepo->newItemMergeInteractor( $this->getContext() )
		);
	}

	public function doesWrites() {
		return true;
	}

	public function initServices(
		EntityIdParser $idParser,
		ExceptionLocalizer $exceptionLocalizer,
		TokenCheckInteractor $tokenCheck,
		ItemMergeInteractor $interactor
	) {
		$this->idParser = $idParser;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->tokenCheck = $tokenCheck;
		$this->interactor = $interactor;
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
					array( $name ),
					'Id does not refer to an item: ' . $name
				);
			}

			return $id;
		} catch ( EntityIdParsingException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-invalid-id',
				array( $rawId ),
				'Entity id is not valid'
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
	 * @since 0.5
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
		} catch ( Exception $ex ) {
			$this->showExceptionMessage( $ex );
		}

		$this->createForm();
	}

	protected function showExceptionMessage( Exception $ex ) {
		$msg = $this->exceptionLocalizer->getExceptionMessage( $ex );

		$this->showErrorHTML( $msg->parse(), 'error' );

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

		/** @var EntityRevision $newRevisionFrom  */
		/** @var EntityRevision $newRevisionTo */
		list( $newRevisionFrom, $newRevisionTo, )
			= $this->interactor->mergeItems( $fromId, $toId, $ignoreConflicts, $summary );

		//XXX: might be nicer to pass pre-rendered links as parameters
		$this->getOutput()->addWikiMsg(
			'wikibase-mergeitems-success',
			$fromId->getSerialization(),
			$newRevisionFrom->getRevisionId(),
			$toId->getSerialization(),
			$newRevisionTo->getRevisionId() );
	}

	/**
	 * Creates the HTML form for merging two items.
	 */
	protected function createForm() {
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		$pre = '';
		if ( $this->getUser()->isAnon() ) {
			$pre = Html::rawElement(
				'p',
				array( 'class' => 'warning' ),
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
	 * Returns the form elements.
	 *
	 * @return array
	 */
	protected function getFormElements() {
		return array(
			'fromid' => array(
				'name' => 'fromid',
				'default' => $this->getRequest()->getVal( 'fromid' ),
				'type' => 'text',
				'id' => 'wb-mergeitems-fromid',
				'label-message' => 'wikibase-mergeitems-fromid'
			),
			'toid' => array(
				'name' => 'toid',
				'default' => $this->getRequest()->getVal( 'toid' ),
				'type' => 'text',
				'id' => 'wb-mergeitems-toid',
				'label-message' => 'wikibase-mergeitems-toid'
			)
		);
		// TODO: Selector for ignoreconflicts
	}

}
