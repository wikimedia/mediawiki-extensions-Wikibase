<?php

namespace Wikibase\Repo\Specials;

use Exception;
use Html;
use HTMLForm;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\Localizer\ExceptionLocalizer;

/**
 * Special page for creating redirects between entities
 *
 * @license GPL-2.0-or-later
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
	 * @var ItemRedirectCreationInteractor
	 */
	private $interactor;

	/**
	 * @var TokenCheckInteractor
	 */
	private $tokenCheck;

	public function __construct(
		EntityIdParser $idParser,
		ExceptionLocalizer $exceptionLocalizer,
		ItemRedirectCreationInteractor $interactor,
		TokenCheckInteractor $tokenCheck
	) {
		parent::__construct( 'RedirectEntity' );

		$this->idParser = $idParser;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->interactor = $interactor;
		$this->tokenCheck = $tokenCheck;
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
				[ $rawId ],
				"$name \"$rawId\" is not valid"
			);
		}
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

		$this->checkPermissions();
		$this->checkBlocked();
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
		$msg = $this->exceptionLocalizer->getExceptionMessage( $ex );

		$this->showErrorHTML( $msg->parse() );

		// Report chained exceptions recursively
		if ( $ex->getPrevious() ) {
			$this->showExceptionMessage( $ex->getPrevious() );
		}
	}

	private function redirectEntity( EntityId $fromId, EntityId $toId ) {
		$this->tokenCheck->checkRequestToken( $this->getContext(), 'wpEditToken' );

		$this->interactor->createRedirect( $fromId, $toId, false, [], $this->getContext() );

		$this->getOutput()->addWikiMsg(
			'wikibase-redirectentity-success',
			$fromId->getSerialization(),
			$toId->getSerialization()
		);
	}

	/**
	 * Creates the HTML form for redirecting an entity
	 */
	protected function createForm() {
		$pre = '';
		if ( !$this->getUser()->isRegistered() ) {
			$pre = Html::rawElement(
				'p',
				[ 'class' => 'warning' ],
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity' )->text()
				)->parse()
			);
		}

		HTMLForm::factory( 'ooui', $this->getFormElements(), $this->getContext() )
			->setId( 'wb-redirectentity-form1' )
			->setPreHtml( $pre )
			->setSubmitID( 'wb-redirectentity-submit' )
			->setSubmitName( 'wikibase-redirectentity-submit' )
			->setSubmitTextMsg( 'wikibase-redirectentity-submit' )
			->setWrapperLegendMsg( 'special-redirectentity' )
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
				'id' => 'wb-redirectentity-fromid',
				'label-message' => 'wikibase-redirectentity-fromid',
			],
			'toid' => [
				'name' => 'toid',
				'default' => $this->getRequest()->getVal( 'toid' ),
				'type' => 'text',
				'id' => 'wb-redirectentity-toid',
				'label-message' => 'wikibase-redirectentity-toid',
			],
		];
	}

}
