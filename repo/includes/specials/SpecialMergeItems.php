<?php

namespace Wikibase\Repo\Specials;

use Exception;
use Html;
use SpecialPage;
use UserInputException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page for merging one item to another.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 */
class SpecialMergeItems extends SpecialPage {

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
	 * Constructor.
	 *
	 * @since 0.5
	 */
	public function __construct() {
		parent::__construct( 'MergeItems', 'item-merge' );

		$repo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$repo->getEntityIdParser(),
			$repo->getExceptionLocalizer(),
			new ItemMergeInteractor(
				$repo->getChangeOpFactoryProvider()->getMergeChangeOpFactory(),
				$repo->getEntityRevisionLookup( 'uncached' ),
				$repo->getEntityStore(),
				$repo->getEntityPermissionChecker(),
				$repo->getSummaryFormatter(),
				$this->getUser()
			)
		);
	}

	public function initServices(
		EntityIdParser $idParser,
		ExceptionLocalizer $exceptionLocalizer,
		ItemMergeInteractor $interactor
	) {
		$this->idParser = $idParser;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->interactor = $interactor;
	}

	private function requireItemIdParam( $name ) {
		$rawId = $this->getTextParam( $name );

		if ( $rawId === '' ) {
			throw new UserInputException(
				'wikibase-mergeitems-missing-parameter',
				array( $name ),
				'Missing required parameter ' . $name
			);
		}

		try {
			return $this->idParser->parse( $rawId );
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

		return $list === '' ? array() : explode( '|', $list );
	}

	private function getTextParam( $name ) {
		$value = $this->getRequest()->getText( $name, '' );
		return trim( $value );
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
		$this->checkReadOnly();

		$this->setHeaders();
		$this->outputHeader();

		//FIXME: token check
		//FIXME: must be posted!

		try {
			$fromId = $this->requireItemIdParam( 'fromid' );
			$toId = $this->requireItemIdParam( 'toid' );

			$ignoreConflicts = $this->getStringListParam( 'ignoreconflicts' );
			$summary = $this->getTextParam( 'summary' );

			$this->mergeItems( $fromId, $toId, $ignoreConflicts, $summary );
		} catch ( Exception $ex ) {
			$this->showExceptionMessage( $ex );
		}

		$this->createForm();
	}

	protected function showExceptionMessage( Exception $ex ) {
		$class = 'error';
		$msg = $this->exceptionLocalizer->getExceptionMessage( $ex );

		$this->getOutput()->addHTML(
			Html::rawElement(
				'p',
				array( 'class' => $class ),
				$msg->parse()
			)
		);
	}


	/**
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param array $ignoreConflicts
	 * @param string $summary
	 */
	private function mergeItems( ItemId $fromId, ItemId $toId, array $ignoreConflicts, $summary ) {
		/** @var EntityRevision $newRevisionFrom  */
		/** @var EntityRevision $newRevisionTo */
		list( $newRevisionFrom, $newRevisionTo ) = $this->interactor->mergeItems( $fromId, $toId, $ignoreConflicts, $summary );

		//XXX: might be nicer to pass pre-rendered links as parameters
		$this->getOutput()->addWikiMsg(
			'wikibase-mergeitems-success',
			$fromId->getSerialization(),
			$newRevisionFrom->getRevision(),
			$toId->getSerialization(),
			$newRevisionTo->getRevision() );
	}

	/**
	 * Creates the HTML form for merging two items.
	 */
	protected function createForm() {
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		if ( $this->getUser()->isAnon() ) {
			$this->getOutput()->addWikiMsg(
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
					'action' => $this->getPageTitle()->getFullUrl(),
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
				// Message: special-mergeitems
				$this->msg( 'special-' . strtolower( $this->getName() ) )->text()
			)
		);

		// Form elements
		$this->getOutput()->addHTML( $this->getFormElements() );

		// Form body
		$this->getOutput()->addHTML(
			Html::input(
				'wikibase-' . strtolower( $this->getName() ) . '-submit',
				// Message: wikibase-mergeitems-submit
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
	 * @return string
	 */
	protected function getFormElements() {
		return Html::rawElement(
			'p',
			array(),
			// Message: wikibase-mergeitems-intro
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
		// TODO: Selector for ignoreconflicts
	}

}
