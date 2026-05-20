<?php

namespace Wikibase\Repo\Specials;

use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Html\Html;
use MediaWiki\Session\SessionManager;
use MediaWiki\Title\Title;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;

/**
 * Abstract base class for special pages of the WikibaseRepo extension.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialWikibaseRepoPage extends SpecialWikibasePage {

	/** @var string[] */
	private $tags;

	/**
	 * @var SpecialPageCopyrightView
	 */
	private $copyrightView;

	/**
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var MediaWikiEditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var EditEntity
	 */
	private $editEntity = null;

	private ?CaptchaFactory $captchaFactory;

	/**
	 * @param string $title The title of the special page
	 * @param string[] $tags List of tags to add to edits
	 * @param SpecialPageCopyrightView $copyrightView
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param MediaWikiEditEntityFactory $editEntityFactory
	 * @param CaptchaFactory|null $captchaFactory Null when ConfirmEdit is not installed.
	 */
	public function __construct(
		$title,
		array $tags,
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		MediaWikiEditEntityFactory $editEntityFactory,
		?CaptchaFactory $captchaFactory = null
	) {
		parent::__construct( $title );
		$this->tags = $tags;
		$this->copyrightView = $copyrightView;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->editEntityFactory = $editEntityFactory;
		$this->captchaFactory = $captchaFactory;
	}

	protected function hasCaptchaFactory(): bool {
		return $this->captchaFactory !== null;
	}

	/** @return SimpleCaptcha|null Null if no captcha is required. */
	protected function getTriggeredCaptcha( string $action, Title $title ): ?SimpleCaptcha {
		if ( $this->captchaFactory === null ) {
			return null;
		}
		$captcha = $this->captchaFactory->getGlobalInstance( $action );
		if ( $captcha->canSkipCaptcha( $this->getUser() ) ) {
			return null;
		}
		return $captcha->triggersCaptcha( $action, $title ) ? $captcha : null;
	}

	protected function renderCaptcha( SimpleCaptcha $captcha, string $action, string $trigger ): string {
		// Some captcha implementations rely on session state.
		SessionManager::getGlobalSession()->persist();
		$captcha->setTrigger( $trigger );
		$captcha->setAction( $action );
		$output = $this->getOutput();
		$formInformation = $captcha->getFormInformation( 1, $output );
		$formMetainfo = $formInformation;
		unset( $formMetainfo['html'] );
		$captcha->addFormInformationToOutput( $output, $formMetainfo );
		return Html::rawElement( 'div', [ 'class' => 'captcha' ], $formInformation['html'] );
	}

	/**
	 * @param EntityId|null $id
	 * @param int $baseRev
	 * @return EditEntity
	 */
	protected function prepareEditEntity( ?EntityId $id = null, int $baseRev = 0 ): EditEntity {
		$this->editEntity = $this->editEntityFactory->newEditEntity(
			$this->getContext(),
			$id,
			$baseRev,
			$this->getRequest()->wasPosted()
		);

		return $this->editEntity;
	}

	/**
	 * Returns the EditEntity interactor.
	 *
	 * @note Call only after calling prepareEditEntity() first.
	 *
	 * @return EditEntity
	 */
	protected function getEditEntity() {
		if ( !$this->editEntity ) {
			throw new RuntimeException( 'Call prepareEditEntity() before calling getEditEntity()' );
		}

		return $this->editEntity;
	}

	/**
	 * Parses an entity id.
	 *
	 * @param string $rawId
	 *
	 * @return EntityId
	 * @throws UserInputException
	 */
	protected function parseEntityId( $rawId ) {
		// TODO inject this!
		$idParser = WikibaseRepo::getEntityIdParser();

		try {
			$id = $idParser->parse( $rawId );
		} catch ( RuntimeException ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-invalid-id',
				[ $rawId ],
				"Entity ID \"$rawId\" is not valid"
			);
		}

		return $id;
	}

	/**
	 * Parses an item id.
	 *
	 * @param string $rawId
	 *
	 * @return ItemId
	 * @throws UserInputException
	 */
	protected function parseItemId( $rawId ) {
		$id = $this->parseEntityId( $rawId );

		if ( !( $id instanceof ItemId ) ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-not-itemid',
				[ $rawId ],
				"Entity ID \"$rawId\" does not refer to an Item"
			);
		}

		return $id;
	}

	/**
	 * @param EntityId $id
	 *
	 * @return null|Title
	 */
	protected function getEntityTitle( EntityId $id ) {
		return $this->entityTitleLookup->getTitleForId( $id );
	}

	/**
	 * Saves the entity using the given summary.
	 *
	 * @note Call prepareEditEntity() first.
	 *
	 * @param EntityDocument $entity
	 * @param FormatableSummary $summary
	 * @param string $token Edit token to check. An empty string is guaranteed to be invalid.
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 *
	 * @return EditEntityStatus
	 */
	protected function saveEntity(
		EntityDocument $entity,
		FormatableSummary $summary,
		$token,
		$flags = EDIT_UPDATE
	) {
		$status = $this->getEditEntity()->attemptSave(
			$entity,
			$this->summaryFormatter->formatSummary( $summary ),
			$flags,
			$token,
			null,
			$this->tags
		);

		return $status;
	}

	/**
	 * Redirect to the page of the entity that was successfully edited.
	 *
	 * @param EditEntityStatus $status A status as returned by {@link self::saveEntity()}.
	 * The status must be {@link StatusValue::isOK() OK}.
	 */
	protected function redirectToEntityPage( EditEntityStatus $status ): void {
		Assert::parameter( $status->isOK(), '$status', 'must be OK' );
		$title = $this->getEntityTitle( $status->getRevision()->getEntity()->getId() );
		$savedTempUser = $status->getSavedTempUser();
		$redirectUrl = '';
		if ( $savedTempUser !== null ) {
			$this->getHookRunner()->onTempUserCreatedRedirect(
				$this->getRequest()->getSession(),
				$savedTempUser,
				$title->getPrefixedDBkey(),
				'',
				'',
				$redirectUrl
			);
		}
		if ( !$redirectUrl ) {
			$redirectUrl = $title->getFullURL();
		}
		$this->getOutput()->redirect( $redirectUrl );
	}

	/**
	 * @param string|null $saveMessageKey Defaults to "wikibase-<special page name>-submit".
	 *
	 * @return string HTML
	 */
	protected function getCopyrightHTML( ?string $saveMessageKey = null ) {
		$saveMessageKey ??= 'wikibase-' . strtolower( $this->getName() ) . '-submit';
		return $this->copyrightView->getHtml( $this->getLanguage(), $saveMessageKey );
	}

}
