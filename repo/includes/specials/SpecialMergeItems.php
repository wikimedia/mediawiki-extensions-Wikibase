<?php

namespace Wikibase\Repo\Specials;

use Html;
use UserInputException;
use InvalidArgumentException;
use Wikibase\EditEntity;
use Wikibase\EntityId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\EntityContent;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;
use Status;

/**
 * Special page for merging one item to another.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialMergeItems extends SpecialWikibaseRepoPage {

	/**
	 * The entity content to merge from.
	 *
	 * @since 0.5
	 *
	 * @var EntityContent
	 */
	private $fromEntityContent;

	/**
	 * The entity content to merge to.
	 *
	 * @since 0.5
	 *
	 * @var EntityContent
	 */
	private $toEntityContent;

	/**
	 * If conflicts should be ignored
	 *
	 * @since 0.5
	 `*
	 * @var boolean
	 */
	private $ignoreConflicts;

	/**
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 */
	public function __construct() {
		parent::__construct( 'MergeItems', 'item-merge' );

		// TODO: find a way to inject this
		$this->summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();
	}

	/**
	 * Main method
	 *
	 * @since 0.5
	 *
	 * @param string $subPage
	 *
	 * @return boolean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkReadOnly();

		$this->setHeaders();
		$this->outputHeader();

		try {
			$this->prepareArguments();
		} catch ( UserInputException $ex ) {
			$error = $this->msg( $ex->getKey(), $ex->getParams() )->parse();
			$this->showErrorHTML( $error );
		}

		$status = Status::newGood();

		if ( $this->modifyEntity( $status ) ) {
			if ( !$status->isGood() ) {
				$this->showErrorHTML( $status->getMessage() );
			} else {
				$editToken = $this->getRequest()->getVal( 'wpEditToken' );

				$toSummary = $this->getSummary( 'to', $this->toEntityContent->getItem()->getId() );

				$fromEditEntity = new EditEntity(
					$this->fromEntityContent,
					$this->getUser(),
					false,
					$this->getContext()
				);

				$fromStatus = $fromEditEntity->attemptSave(
					$this->summaryFormatter->formatSummary( $toSummary ),
					EDIT_UPDATE,
					$editToken
				);

				if ( !$fromStatus->isGood() ) {
					$this->showErrorHTML( $fromStatus->getMessage() );
				} else {
					$fromSummary = $this->getSummary( 'from', $this->fromItemContent->getItem()->getId() );

					$toEditEntity = new EditEntity(
						$this->toEntityContent,
						$this->getUser(),
						false,
						$this->getContext()
					);

					$toStatus = $fromEditEntity->attemptSave(
						$this->summaryFormatter->formatSummary( $fromSummary ),
						EDIT_UPDATE,
						$editToken
					);

					if ( !$toStatus->isGood() ) {
						// TODO: if the second result is not a success we should probably undo the first change
						$this->showErrorHTML( $toStatus->getMessage() );
					} else {
						// Everything went well so redirect to the merged item
						$toEntityUrl = $this->toEntityContent->getTitle()->getFullUrl();
						$this->getOutput()->redirect( $toEntityUrl );
						return true; // no need to create the form now
					}
				}
			}
		}

		$this->createForm();

		return true;
	}

	/**
	 * Prepares the arguments.
	 *
	 * @since 0.5
	 */
	protected function prepareArguments() {
		$request = $this->getRequest();

		// Get from id
		$rawFromId = $request->getVal( 'fromid', null );
		$rawToId = $request->getVal( 'toid', null );

		if ( !$rawFromId || !$rawToId ) {
			return;
		}

		$fromId = $this->parseEntityId( $rawFromId );
		$toId = $this->parseEntityId( $rawToId );

		$this->fromEntityContent = $this->loadEntityContent( $fromId );
		$this->toEntityContent = $this->loadEntityContent( $toId );

		// Get ignore conflicts
		$ignoreConflicts = $request->getVal( 'ignoreconflicts', null );

		if ( $ignoreConflicts ) {
			$this->ignoreConflicts = explode( '|', 'ignoreconflicts' );
		} else {
			$this->ignoreConflicts = array();
		}
	}

	/**
	 * Modifies the entity.
	 *
	 * @since 0.5
	 *
	 * @param Status $status
	 *
	 * @return boolean
	 */
	protected function modifyEntity( Status $status ) {
		if ( $this->fromEntityContent === null || $this->toEntityContent === null ) {
			return false;
		}
		try {
			$changeOps = new ChangeOpsMerge(
				$this->fromEntityContent,
				$this->toEntityContent,
				$this->ignoreConflicts
			);
			$changeOps->apply();
		} catch( InvalidArgumentException $e ) {
			$status->fatal( $e->getMessage() );
		} catch( ChangeOpException $e ) {
			$status->fatal( $e->getMessage() );
		}
		return true;
	}

	/**
	 * Creates the summary.
	 *
	 * @since 0.5
	 *
	 * @param string $direction
	 * @param EntityId $id
	 *
	 * @return Summary
	 */
	protected function getSummary( $direction, $id ) {
		$entityIdFormatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();
		$summary = new Summary(
			'wbmergeitems',
			$direction,
			null,
			array( $entityIdFormatter->format( $id ) )
		);
		return $summary;
	}

	/**
	 * Creates the HTML form for merging two items.
	 *
	 * @since 0.5
	 */
	protected function createForm() {
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		if ( $this->getUser()->isAnon() ) {
			$this->showErrorHTML(
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-item' )->text()
				)->parse(),
				'warning'
			);
		}

		// Form header
		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getTitle()->getFullUrl(),
					'name' => strtolower( $this->getName() ),
					'id' => 'wb-' . strtolower( $this->getName() ) . '-form1',
					'class' => 'wb-form'
				)
			)
			. Html::openElement(
				'fieldset',
				array( 'class' => 'wb-fieldset' )
			)
			. Html::element(
				'legend',
				array( 'class' => 'wb-legend' ),
				$this->msg( 'special-' . strtolower( $this->getName() ) )->text()
			)
		);

		// Form elements
		$this->getOutput()->addHTML( $this->getFormElements() );

		// Form body
		$this->getOutput()->addHTML(
			Html::input(
				'wikibase-' . strtolower( $this->getName() ) . '-submit',
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-' . strtolower( $this->getName() ) . '-submit',
					'class' => 'wb-button'
				)
			)
			. Html::input(
				'wpEditToken',
				$this->getUser()->getEditToken(),
				'hidden'
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

	/**
	 * Returns the form elements.
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	protected function getFormElements() {
		return Html::rawElement(
			'p',
			array(),
			$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-intro' )->parse()
		)
		. Html::element(
			'label',
			array(
				'for' => 'wb-mergeitems-fromid',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-mergeitems-fromid' )->text()
		)
		. Html::input(
			'fromid',
			$this->getRequest()->getVal( 'fromid' ),
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-mergeitems-fromid'
			)
		)
		. Html::element( 'br' )
		. Html::element(
			'label',
			array(
				'for' => 'wb-mergeitems-toid',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-mergeitems-toid' )->text()
		)
		. Html::input(
			'toid',
			$this->getRequest()->getVal( 'toid' ),
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-mergeitems-toid'
			)
		)
		. Html::element( 'br' );
		// TODO: Here should be a way to easily ignore conflicts
	}
}