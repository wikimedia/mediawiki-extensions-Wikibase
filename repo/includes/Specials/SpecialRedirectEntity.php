<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Specials;

use Exception;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\AnonymousEditWarningBuilder;
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

	private AnonymousEditWarningBuilder $anonymousEditWarningBuilder;
	private EntityIdParser $idParser;
	private ExceptionLocalizer $exceptionLocalizer;
	private ItemRedirectCreationInteractor $interactor;
	private TokenCheckInteractor $tokenCheck;

	public function __construct(
		AnonymousEditWarningBuilder $anonymousEditWarningBuilder,
		EntityIdParser $idParser,
		ExceptionLocalizer $exceptionLocalizer,
		ItemRedirectCreationInteractor $interactor,
		TokenCheckInteractor $tokenCheck
	) {
		parent::__construct( 'RedirectEntity' );

		$this->anonymousEditWarningBuilder = $anonymousEditWarningBuilder;
		$this->idParser = $idParser;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->interactor = $interactor;
		$this->tokenCheck = $tokenCheck;
	}

	/**
	 * @throws UserInputException
	 */
	private function getEntityIdParam( string $name ): ?EntityId {
		$rawId = $this->getTextParam( $name );

		if ( $rawId === '' ) {
			return null;
		}

		try {
			return $this->idParser->parse( $rawId );
		} catch ( EntityIdParsingException ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-invalid-id',
				[ $rawId ],
				"$name \"$rawId\" is not valid"
			);
		}
	}

	private function getTextParam( string $name ): string {
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
				if ( $this->getRequest()->getBool( 'success' ) ) {
					// redirected back here after a successful edit + temp user, show success now
					// (the success may be inaccurate if users created this URL manually, but that’s harmless)
					$this->showSuccess( $fromId, $toId );
				} else {
					$this->redirectEntity( $fromId, $toId );
				}
			}
		} catch ( Exception $ex ) {
			$this->showExceptionMessage( $ex );
		}

		$this->createForm();
	}

	protected function showExceptionMessage( Exception $ex ): void {
		$msg = $this->exceptionLocalizer->getExceptionMessage( $ex );

		$this->showErrorHTML( $msg->parse() );

		// Report chained exceptions recursively
		$previousEx = $ex->getPrevious();
		if ( $previousEx ) {
			$this->showExceptionMessage( $previousEx );
		}
	}

	private function redirectEntity( EntityId $fromId, EntityId $toId ): void {
		$this->tokenCheck->checkRequestToken( $this->getContext(), 'wpEditToken' );

		$status = $this->interactor->createRedirect( $fromId, $toId, false, [], $this->getContext() );
		$savedTempUser = $status->getSavedTempUser();

		if ( $savedTempUser !== null ) {
			$redirectUrl = '';
			$this->getHookRunner()->onTempUserCreatedRedirect(
				$this->getRequest()->getSession(),
				$savedTempUser,
				$this->getPageTitle()->getPrefixedDBkey(),
				"fromid={$fromId->getSerialization()}&toid={$toId->getSerialization()}&success=1",
				'',
				$redirectUrl
			);
			if ( $redirectUrl ) {
				$this->getOutput()->redirect( $redirectUrl );
				return; // success will be shown when returning here from redirect
			}
		}

		$this->showSuccess( $fromId, $toId );
	}

	private function showSuccess( EntityId $fromId, EntityId $toId ): void {
		$this->getOutput()->addWikiMsg(
			'wikibase-redirectentity-success',
			$fromId->getSerialization(),
			$toId->getSerialization()
		);
	}

	/**
	 * Creates the HTML form for redirecting an entity
	 */
	protected function createForm(): void {
		$pre = '';
		if ( !$this->getUser()->isRegistered() ) {
			$this->getOutput()->addModuleStyles( 'mediawiki.codex.messagebox.styles' );
			$pre = Html::warningBox(
				$this->msg( $this->anonymousEditWarningBuilder->buildAnonymousEditWarningMessage( $this->getFullTitle() ) )
					->parse()
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
	protected function getFormElements(): array {
		return [
			'fromid' => [
				'name' => 'fromid',
				'default' => $this->getRequest()->getVal( 'fromid' ),
				'type' => 'text',
				'id' => 'wb-redirectentity-fromid',
				'label-message' => 'wikibase-redirectentity-fromid',
				'required' => true,
			],
			'toid' => [
				'name' => 'toid',
				'default' => $this->getRequest()->getVal( 'toid' ),
				'type' => 'text',
				'id' => 'wb-redirectentity-toid',
				'label-message' => 'wikibase-redirectentity-toid',
				'required' => true,
			],
		];
	}

}
