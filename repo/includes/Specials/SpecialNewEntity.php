<?php

namespace Wikibase\Repo\Specials;

use MediaWiki\Exception\UserBlockedError;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Summary;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\SummaryFormatter;

/**
 * Page for creating new Wikibase entities that contain a Fingerprint.
 *
 * @license GPL-2.0-or-later
 */
abstract class SpecialNewEntity extends SpecialWikibaseRepoPage {

	/**
	 * Contains pieces of the subpage name of this special page if a subpage was called.
	 * E.g. [ 'a', 'b' ] in case of 'Special:NewEntity/a/b'
	 * @var string[]|null
	 */
	protected $parts = null;

	/**
	 * @var EntityNamespaceLookup
	 */
	protected $entityNamespaceLookup;

	private bool $isMobileView;

	private ?SimpleCaptcha $captcha = null;

	private bool $captchaFailed = false;

	/**
	 * @param string $name Name of the special page, as seen in links and URLs.
	 * @param string[] $tags List of tags to add to edits
	 * @param SpecialPageCopyrightView $copyrightView
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param MediaWikiEditEntityFactory $editEntityFactory
	 * @param bool $isMobileView
	 * @param CaptchaFactory|null $captchaFactory Null when ConfirmEdit is not installed.
	 */
	public function __construct(
		$name,
		array $tags,
		SpecialPageCopyrightView $copyrightView,
		EntityNamespaceLookup $entityNamespaceLookup,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		MediaWikiEditEntityFactory $editEntityFactory,
		bool $isMobileView,
		?CaptchaFactory $captchaFactory = null
	) {
		parent::__construct(
			$name,
			$tags,
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory,
			$captchaFactory
		);

		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->isMobileView = $isMobileView;
	}

	/**
	 * @see SpecialPage::doesWrites
	 *
	 * @return bool
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isListed() {
		return $this->entityNamespaceLookup->getEntityNamespace( $this->getEntityType() ) !== null;
	}

	/**
	 * @return string Type id of the entity that will be created (eg: Item::ENTITY_TYPE value)
	 */
	abstract protected function getEntityType();

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkBlockedOnNamespace();
		$this->checkReadOnly();

		$this->parts = $subPage ? explode( '/', $subPage ) : [];

		$form = $this->createForm();

		$form->prepareForm();

		/** @var Status|false $submitStatus `false` if form was not submitted */
		$submitStatus = $form->tryAuthorizedSubmit();

		if ( $submitStatus && $submitStatus->isGood() ) {
			// cast it back in case HTMLForm turned it into a generic Status
			$submitStatus = EditEntityStatus::cast( $submitStatus );
			$this->redirectToEntityPage( $submitStatus );

			return;
		}

		$out = $this->getOutput();

		$this->displayBeforeForm( $out );

		if ( $submitStatus && !$submitStatus->isOK() ) {
			$errors = $this->captchaFailed
				? [ 'wikibase-newentity-hcaptcha-fail' ]
				: $submitStatus->getMessages( 'error' );
			$out->addModuleStyles( [ 'mediawiki.codex.messagebox.styles', 'wikibase.special.newEntity.styles' ] );
			foreach ( $errors as $error ) {
				$form->addHeaderHtml( Html::errorBox( $this->msg( $error )->parse() ) );
			}
			$submitStatus = false;
		}

		$form->displayForm( $submitStatus ?: Status::newGood() );
	}

	/**
	 * @return array[]
	 */
	abstract protected function getFormFields();

	/**
	 * @return string|Message $msg Legend for the fieldset, Message key or Message object
	 */
	abstract protected function getLegend();

	/**
	 * @return string[] Warnings that should be presented to the user
	 */
	abstract protected function getWarnings();

	/**
	 * @return HTMLForm
	 */
	private function createForm() {
		$formFields = $this->getFormFields();
		$this->maybeAddCaptchaField( $formFields );

		return HTMLForm::factory( 'ooui', $formFields, $this->getContext() )
			->setId( 'mw-newentity-form1' )
			->setSubmitID( 'wb-newentity-submit' )
			// name="submit" shadows HTMLFormElement.submit() in JS (T426089).
			->setSubmitName( 'wikibase-newentity-submit' )
			->setSubmitTextMsg( 'wikibase-newentity-submit' )
			->setWrapperLegendMsg( $this->getLegend() )
			->setSubmitCallback(
				function ( $data, HTMLForm $form ) {
					$validationStatus = $this->validateFormData( $data );
					if ( !$validationStatus->isGood() ) {
						return $validationStatus;
					}

					$entity = $this->createEntityFromFormData( $data );

					$summary = $this->createSummary( $entity );

					$this->prepareEditEntity();
					$status = $this->saveEntity(
						$entity,
						$summary,
						$form->getRequest()->getRawVal( 'wpEditToken' ) ?? '',
						EDIT_NEW
					);
					// ConfirmEdit fails CREATE without a message, so show our own.
					$this->captchaFailed = $this->captcha !== null && !$status->isOK()
						&& $this->getEditEntity()->hasError( EditEntity::FILTERED_ERROR )
						&& !$status->hasMessagesExcept( 'captcha-edit-fail' );
					return $status;
				}
			);
	}

	/** Render a captcha widget so users hit by EditFilterMergedContent can solve it. */
	private function maybeAddCaptchaField( array &$formFields ): void {
		if ( !$this->hasCaptchaFactory() ) {
			return;
		}
		// Reuse the fake title the save path builds in
		// MediaWikiEditFilterHookRunner::getContextForEditFilter().
		$namespace = $this->entityNamespaceLookup->getEntityNamespace( $this->getEntityType() );
		$title = Title::makeTitle( $namespace, 'New' . ucfirst( $this->getEntityType() ) );
		$captcha = $this->getTriggeredCaptcha( CaptchaTriggers::CREATE, $title );
		$this->captcha = $captcha;
		if ( $captcha === null ) {
			return;
		}
		$this->getOutput()->addModuleStyles( 'wikibase.special.newEntity.styles' );
		$formFields['captcha'] = [
			'type' => 'info',
			// Rendered after submit so the widget can show a failed attempt.
			'nodata' => true,
			'default' => function () use ( $captcha ): string {
				$html = $this->renderCaptcha( $captcha, CaptchaTriggers::CREATE, 'new entity' );
				// ConfirmEdit only adds the privacy notice in invisible mode, so we force it.
				if ( $captcha instanceof HCaptcha && !str_contains( $html, 'h-captcha-privacy-policy' ) ) {
					$html .= Html::rawElement( 'div', [ 'class' => 'h-captcha-privacy-policy' ],
						$this->msg( 'hcaptcha-privacy-policy' )->parse() );
				}
				return $html;
			},
			'raw' => true,
		];
	}

	/**
	 * @param array $formData
	 *
	 * @return EntityDocument
	 */
	abstract protected function createEntityFromFormData( array $formData );

	/**
	 * @param array $formData
	 *
	 * @return Status
	 */
	abstract protected function validateFormData( array $formData );

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Summary
	 */
	abstract protected function createSummary( EntityDocument $entity );

	protected function displayBeforeForm( OutputPage $output ) {
		// T324991
		if ( !$this->isMobileView ) {
			$output->addModules( 'wikibase.special.newEntity' );
		}

		$output->addHTML( $this->getCopyrightHTML() );

		foreach ( $this->getWarnings() as $warning ) {
			$this->getOutput()->addModuleStyles( 'mediawiki.codex.messagebox.styles' );
			$this->getOutput()->addHTML( Html::warningBox( $warning ) );
		}
	}

	/**
	 * @param string|null $messageKey ignored here
	 *
	 * @return string HTML
	 */
	protected function getCopyrightHTML( $messageKey = null ) {
		return parent::getCopyrightHTML( 'wikibase-newentity-submit' );
	}

	/**
	 * @throws UserBlockedError
	 */
	private function checkBlockedOnNamespace() {
		$namespace = $this->entityNamespaceLookup->getEntityNamespace( $this->getEntityType() );
		$block = $this->getUser()->getBlock();
		if ( $block && $block->appliesToNamespace( $namespace ) ) {
			throw new UserBlockedError( $block );
		}
	}

}
