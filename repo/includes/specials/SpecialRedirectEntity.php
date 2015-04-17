<?php

namespace Wikibase\Repo\Specials;

use Exception;
use Html;
use UserInputException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page for creating redirects between entities
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Addshore
 */
class SpecialRedirectEntity extends SpecialWikibasePage {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var ExceptionLocalizer
	 */
	private $exceptionLocalizer;

	/**
	 * @var RedirectCreationInteractor
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
		parent::__construct( 'RedirectEntity' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getExceptionLocalizer(),
			new TokenCheckInteractor(
				$this->getUser()
			),
			new RedirectCreationInteractor(
				$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
				$wikibaseRepo->getEntityStore(),
				$wikibaseRepo->getEntityPermissionChecker(),
				$wikibaseRepo->getSummaryFormatter(),
				$this->getUser()
			)
		);
	}

	public function initServices(
		EntityIdParser $idParser,
		ExceptionLocalizer $exceptionLocalizer,
		TokenCheckInteractor $tokenCheck,
		RedirectCreationInteractor $interactor
	) {
		$this->idParser = $idParser;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->tokenCheck = $tokenCheck;
		$this->interactor = $interactor;
	}

	/**
	 * @param string $name
	 *
	 * @return EntityId|null
	 * @throws UserInputException
	 */
	private function getEntityIdParam( $name ) {
		$rawId = $this->getTextParam( $name );

		if ( $rawId === '' ) {
			return null;
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
			$fromId = $this->getEntityIdParam( 'fromid' );
			$toId = $this->getEntityIdParam( 'toid' );

			if ( $fromId && $toId ) {
				$this->redirectEntity( $fromId, $toId );
			}
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

		// Report chained exceptions recursively
		if ( $ex->getPrevious() ) {
			$this->showExceptionMessage( $ex->getPrevious() );
		}
	}

	/**
	 * @param EntityId $fromId
	 * @param EntityId $toId
	 */
	private function redirectEntity( EntityId $fromId, EntityId $toId ) {
		$this->tokenCheck->checkRequestToken( $this->getRequest(), 'token' );

		$this->interactor->createRedirect( $fromId, $toId, false );

		$this->getOutput()->addWikiMsg(
			'wikibase-entityredirect-success',
			$fromId->getSerialization(),
			$toId->getSerialization());
	}

	/**
	 * Creates the HTML form for redirecting an entity
	 */
	protected function createForm() {
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		if ( $this->getUser()->isAnon() ) {
			$this->getOutput()->addHTML(
				Html::rawElement(
					'p',
					array( 'class' => 'warning' ),
					$this->msg(
						'wikibase-anonymouseditwarning',
						$this->msg( 'wikibase-entity' )->text()
					)->parse()
				)
			);
		}

		// Form header
		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getPageTitle()->getFullUrl(),
					'name' => 'entityredirect',
					'id' => 'wb-entityredirect-form1',
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
				$this->msg( 'special-entityredirect' )->text()
			)
		);

		// Form elements
		$this->getOutput()->addHTML( $this->getFormElements() );

		// Form body
		$this->getOutput()->addHTML(
			Html::input(
				'wikibase-entityredirect-submit',
				$this->msg( 'wikibase-entityredirect-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-entityredirect-submit',
					'class' => 'wb-button'
				)
			)
			. Html::input(
				'token',
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
		return Html::element(
			'label',
			array(
				'for' => 'wb-entityredirect-fromid',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-entityredirect-fromid' )->text()
		)
		. Html::input(
			'fromid',
			$this->getRequest()->getVal( 'fromid' ),
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-entityredirect-fromid'
			)
		)
		. Html::element( 'br' )
		. Html::element(
			'label',
			array(
				'for' => 'wb-entityredirect-toid',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-entityredirect-toid' )->text()
		)
		. Html::input(
			'toid',
			$this->getRequest()->getVal( 'toid' ),
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-entityredirect-toid'
			)
		)
		. Html::element( 'br' );
	}

}
