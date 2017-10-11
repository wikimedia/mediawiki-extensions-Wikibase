<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Html;
use Language;
use Status;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Abstract special page for setting a value of a Wikibase entity.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 */
abstract class SpecialModifyTerm extends SpecialModifyEntity {

	/**
	 * The language the value is set in.
	 *
	 * @var string
	 */
	private $languageCode;

	/**
	 * The value to set.
	 *
	 * @var string
	 */
	private $value;

	/**
	 * @var FingerprintChangeOpFactory
	 */
	protected $termChangeOpFactory;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @param string $title The title of the special page
	 * @param SpecialPageCopyrightView $copyrightView
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EditEntityFactory $editEntityFactory
	 * @param EntityPermissionChecker $permissionChecker
	 */
	public function __construct(
		$title,
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory,
		EntityPermissionChecker $permissionChecker
	) {
		parent::__construct(
			$title,
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory
		);

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
		$this->termsLanguages = $wikibaseRepo->getTermsLanguages();
		$this->permissionChecker = $permissionChecker;
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialModifyEntity::processArguments()
	 *
	 * @param string|null $subPage
	 */
	protected function processArguments( $subPage ) {
		parent::processArguments( $subPage );

		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? [] : explode( '/', $subPage, 2 );

		// Language
		$this->languageCode = $request->getVal( 'language', isset( $parts[1] ) ? $parts[1] : '' );

		if ( $this->languageCode === '' ) {
			$this->languageCode = null;
		}

		$this->checkSubPageLanguage();

		// Value
		$this->value = $this->getPostedValue();
		if ( $this->value === null ) {
			$this->value = $request->getVal( 'value' );
		}

		// If the user just enters an item id and a language, dont remove the term.
		// The user can remove the term in the second form where it has to be
		// actually removed. This prevents users from removing terms accidentally.
		if ( !$request->getCheck( 'remove' ) && $this->value === '' ) {
			$this->value = null;
		}
	}

	/**
	 * Check the language given as sup page argument.
	 */
	private function checkSubPageLanguage() {
		if ( $this->languageCode !== null && !$this->termsLanguages->hasLanguage( $this->languageCode ) ) {
			$errorMessage = $this->msg(
				'wikibase-wikibaserepopage-invalid-langcode',
				wfEscapeWikiText( $this->languageCode )
			)->parse();

			$this->showErrorHTML( $errorMessage );
		}
	}

	/**
	 * @see SpecialModifyEntity::validateInput()
	 *
	 * @return bool
	 */
	protected function validateInput() {
		if ( !parent::validateInput() ) {
			return false;
		}

		if ( $this->value === null ) {
			return false;
		}

		$entityId = $this->getEntityId();
		if ( $entityId ) {
			$status = $this->checkTermChangePermissions( $entityId );

			if ( !$status->isOK() ) {
				$this->showErrorHTML( $this->msg( 'permissionserrors' ) );
				return false;
			}
		}

		return true;
	}

	/**
	 * @see SpecialModifyEntity::modifyEntity()
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Summary|bool
	 */
	protected function modifyEntity( EntityDocument $entity ) {
		try {
			$summary = $this->setValue( $entity, $this->languageCode, $this->value );
		} catch ( ChangeOpException $e ) {
			$this->showErrorHTML( $e->getMessage() );
			return false;
		}

		return $summary;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Status
	 */
	private function checkTermChangePermissions( EntityId $entityId ) {
		return $this->permissionChecker->getPermissionStatusForEntityId(
			$this->getUser(),
			EntityPermissionChecker::ACTION_EDIT_TERMS,
			$entityId
		);
	}

	/**
	 * @see SpecialModifyEntity::getForm()
	 *
	 * @param EntityDocument|null $entity
	 *
	 * @return HTMLForm
	 */
	protected function getForm( EntityDocument $entity = null ) {
		if ( $this->languageCode === null ) {
			$this->languageCode = $this->getLanguage()->getCode();
		}

		$this->setValueIfNull( $entity );

		$valueinput = [
			'name' => 'value',
			'cssclass' => 'wb-input',
			'id' => 'wb-modifyterm-value',
			'type' => 'text',
			'default' => $this->getRequest()->getVal( 'value' ) ?: $this->value,
			'nodata' => true
		];

		$languageName = Language::fetchLanguageName( $this->languageCode, $this->getLanguage()->getCode() );

		if ( $entity !== null && $this->languageCode !== null && $languageName !== '' ) {
			// Messages:
			// wikibase-setlabel-introfull
			// wikibase-setdescription-introfull
			// wikibase-setaliases-introfull
			$intro = $this->msg(
				'wikibase-' . strtolower( $this->getName() ) . '-introfull',
				$this->getEntityTitle( $entity->getId() )->getPrefixedText(),
				$languageName
			)->parse();
			$formDescriptor = [
				'language' => [
					'name' => 'language',
					'type' => 'hidden',
					'default' => $this->languageCode
				],
				'id' => [
					'name' => 'id',
					'type' => 'hidden',
					'default' => $entity->getId()->getSerialization()
				],
				'remove' => [
					'name' => 'remove',
					'type' => 'hidden',
					'default' => 'remove'
				],
				'value' => $valueinput,
				'revid' => [
					'name' => 'revid',
					'type' => 'hidden',
					'default' => $this->getBaseRevision()->getRevisionId(),
				],
			];
		} else {
			// Messages:
			// wikibase-setlabel-intro
			// wikibase-setdescription-intro
			// wikibase-setaliases-intro
			$intro = $this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-intro' )->parse();
			$formDescriptor = $this->getFormElements( $entity );
			$formDescriptor['language'] = [
				'name' => 'language',
				'label-message' => 'wikibase-modifyterm-language',
				'type' => 'text',
				'default' => $this->languageCode,
				'cssclass' => 'wb-input',
				'id' => 'wb-modifyterm-language'
			];
			// Messages:
			// wikibase-setlabel-label
			// wikibase-setdescription-label
			// wikibase-setaliases-label
			$valueinput['label-message'] = 'wikibase-' . strtolower( $this->getName() ) . '-label';
			$formDescriptor['value'] = $valueinput;
		}

		return HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setHeaderText( Html::rawElement( 'p', [], $intro ) );
	}

	private function setValueIfNull( EntityDocument $entity = null ) {
		if ( $this->value === null ) {
			if ( $entity === null ) {
				$this->value = '';
			} else {
				$this->value = $this->getValue( $entity, $this->languageCode );
			}
		}
	}

	/**
	 * Returning the posted value of the request.
	 *
	 * @return string|null
	 */
	abstract protected function getPostedValue();

	/**
	 * Returning the value of the entity name by the given language
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 *
	 * @return string
	 */
	abstract protected function getValue( EntityDocument $entity, $languageCode );

	/**
	 * Setting the value of the entity name by the given language
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @return Summary
	 */
	abstract protected function setValue( EntityDocument $entity, $languageCode, $value );

}
