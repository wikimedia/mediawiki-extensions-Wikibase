<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Specials;

use Exception;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Message\Message;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\AnonymousEditWarningBuilder;
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

	private AnonymousEditWarningBuilder $anonymousEditWarningBuilder;
	private EntityIdParser $idParser;
	private ExceptionLocalizer $exceptionLocalizer;
	private ItemMergeInteractor $interactor;
	private EntityTitleLookup $titleLookup;
	private TokenCheckInteractor $tokenCheck;
	private bool $isMobileView;

	public function __construct(
		AnonymousEditWarningBuilder $anonymousEditWarningBuilder,
		EntityIdParser $idParser,
		EntityTitleLookup $titleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		ItemMergeInteractor $interactor,
		bool $isMobileView,
		TokenCheckInteractor $tokenCheck
	) {
		parent::__construct( 'MergeItems', 'item-merge' );

		$this->anonymousEditWarningBuilder = $anonymousEditWarningBuilder;
		$this->idParser = $idParser;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->interactor = $interactor;
		$this->titleLookup = $titleLookup;
		$this->tokenCheck = $tokenCheck;
		$this->isMobileView = $isMobileView;
	}

	/** @inheritDoc */
	public function doesWrites() {
		return true;
	}

	/**
	 * @throws UserInputException
	 */
	private function getItemIdParam( string $name ): ?ItemId {
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

	private function getStringListParam( string $name ): array {
		$list = $this->getTextParam( $name );

		return $list === '' ? [] : explode( '|', $list );
	}

	private function getTextParam( string $name ): string {
		return trim( $this->getRequest()->getText( $name, '' ) );
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
			$fromId = $this->getItemIdParam( 'fromid' );
			$toId = $this->getItemIdParam( 'toid' );

			$ignoreConflicts = $this->getStringListParam( 'ignoreconflicts' );
			$summary = $this->getTextParam( 'summary' );

			if ( $fromId && $toId ) {
				$success = $this->getStringListParam( 'success' );
				if ( count( $success ) === 2 && ctype_digit( $success[0] ) && ctype_digit( $success[1] ) ) {
					// redirected back here after a successful edit + temp user, show success now
					// (the success may be inaccurate if users created this URL manually, but that’s harmless)
					$this->showSuccess( $fromId, $toId, (int)$success[0], (int)$success[1] );
				} else {
					$this->mergeItems( $fromId, $toId, $ignoreConflicts, $summary );
				}
			}
		} catch ( ItemMergeException $ex ) {
			if ( $ex->getPrevious() instanceof UnresolvedEntityRedirectException ) {
				$this->showErrorHTML( $this->msg( 'wikibase-itemmerge-redirect' )->parse() );
			} else {
				$this->showExceptionMessage( $ex );
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

	/**
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param string[] $ignoreConflicts
	 * @param string $summary
	 */
	private function mergeItems( ItemId $fromId, ItemId $toId, array $ignoreConflicts, $summary ): void {
		$this->tokenCheck->checkRequestToken( $this->getContext(), 'wpEditToken' );

		$status = $this->interactor->mergeItems( $fromId, $toId, $this->getContext(), $ignoreConflicts, $summary );
		$newRevisionFromId = $status->getFromEntityRevision()->getRevisionId();
		$newRevisionToId = $status->getToEntityRevision()->getRevisionId();
		$savedTempUser = $status->getSavedTempUser();
		if ( $savedTempUser !== null ) {
			$redirectUrl = '';
			$this->getHookRunner()->onTempUserCreatedRedirect(
				$this->getRequest()->getSession(),
				$savedTempUser,
				$this->getPageTitle()->getPrefixedDBkey(),
				"fromid={$fromId->getSerialization()}&toid={$toId->getSerialization()}" .
					"&success=$newRevisionFromId|$newRevisionToId",
				'',
				$redirectUrl
			);
			if ( $redirectUrl ) {
				$this->getOutput()->redirect( $redirectUrl );
				return; // success will be shown when returning here from redirect
			}
		}

		$this->showSuccess( $fromId, $toId, $newRevisionFromId, $newRevisionToId );
	}

	private function showSuccess( ItemId $fromId, ItemId $toId, int $newRevisionFromId, int $newRevisionToId ): void {
		$linkRenderer = $this->getLinkRenderer();
		$fromTitle = $this->titleLookup->getTitleForId( $fromId );
		$toTitle = $this->titleLookup->getTitleForId( $toId );

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
			$newRevisionFromId,
			Message::rawParam(
				$linkRenderer->makeKnownLink(
					$toTitle,
					$toId->getSerialization()
				)
			),
			$newRevisionToId
		);
	}

	/**
	 * Creates the HTML form for merging two items.
	 */
	protected function createForm(): void {
		// T324991
		if ( !$this->isMobileView ) {
			$this->getOutput()->addModules( 'wikibase.special.mergeItems' );
		}

		$pre = '';
		if ( !$this->getUser()->isRegistered() ) {
			$pre = Html::rawElement(
				'p',
				[ 'class' => 'warning' ],
				$this->anonymousEditWarningBuilder->buildAnonymousEditWarningHTML( $this->getFullTitle()->getPrefixedText() )
			);
		}

		HTMLForm::factory( 'ooui', $this->getFormElements(), $this->getContext() )
			->setId( 'wb-mergeitems-form1' )
			->setPreHtml( $pre )
			->setHeaderHtml( $this->msg( 'wikibase-mergeitems-intro' )->parse() )
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
	protected function getFormElements(): array {
		return [
			'fromid' => [
				'name' => 'fromid',
				'default' => $this->getRequest()->getVal( 'fromid' ),
				'type' => 'text',
				'id' => 'wb-mergeitems-fromid',
				'label-message' => 'wikibase-mergeitems-fromid',
			],
			'toid' => [
				'name' => 'toid',
				'default' => $this->getRequest()->getVal( 'toid' ),
				'type' => 'text',
				'id' => 'wb-mergeitems-toid',
				'label-message' => 'wikibase-mergeitems-toid',
			],
		];
		// TODO: Selector for ignoreconflicts
	}

}
