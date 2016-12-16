<?php

namespace Wikibase\Repo\Specials;

use Html;
use HTMLForm;
use InvalidArgumentException;
use Status;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\View\LanguageDirectionalityLookup;

/**
 * Page for creating new Wikibase entities that contain a Fingerprint.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
abstract class SpecialNewEntity extends SpecialWikibaseRepoPage {

	/**
	 * Contains pieces of the sub-page name of this special page if a subpage was called.
	 * E.g. [ 'a', 'b' ] in case of 'Special:NewEntity/a/b'
	 * @var string[]|null
	 */
	protected $parts = null;

	/**
	 * @var string|null
	 */
	private $label;

	/**
	 * @var string|null
	 */
	private $description;

	/**
	 * @var string[]
	 */
	private $aliases = [];

	/**
	 * @var string
	 */
	private $contentLanguageCode;

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @var SpecialPageCopyrightView
	 */
	private $copyrightView;

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param string $name Name of the special page, as seen in links and URLs.
	 * @param string $restriction User right required, 'createpage' per default.
	 *
	 * @since 0.1
	 */
	public function __construct( $name, $restriction = 'createpage' ) {
		parent::__construct( $name, $restriction );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$this->copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);
		$this->languageCodes = $wikibaseRepo->getTermsLanguages()->getLanguages();
		$this->languageDirectionalityLookup = $wikibaseRepo->getLanguageDirectionalityLookup();
		$this->languageNameLookup = $wikibaseRepo->getLanguageNameLookup();
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.1
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkReadOnly();

		$this->parts = ( $subPage === '' ? [] : explode( '/', $subPage ) );
		$this->prepareArguments();

		$out = $this->getOutput();

		$out->addHTML( $this->getCopyrightText() );

		$form = $this->createFormNew();

		$form->prepareForm();

		/** @var Status $submitStatus */
		$submitStatus = $form->tryAuthorizedSubmit();

		if ( $submitStatus && $submitStatus->isGood() ) {
			$savedEntity = $submitStatus->getValue();
			$title = $this->getEntityTitle( $savedEntity->getId() );
			$entityUrl = $title->getFullURL();
			$this->getOutput()->redirect( $entityUrl );

			return;
		}

		foreach ( $this->getWarnings() as $warning ) {
			$out->addHTML( Html::element( 'div', [ 'class' => 'warning' ], $warning ) );
		}

		$form->displayForm( $submitStatus ?: Status::newGood() );
	}

	/**
	 * Tries to extract argument values from web request or of the page's sub-page parts
	 *
	 * Trimming argument values from web request.
	 *
	 * @since 0.1
	 */
	protected function prepareArguments() {
		$label = $this->getRequest()->getVal(
			'label',
			isset( $this->parts[0] ) ? $this->parts[0] : ''
		);
		$this->label = $this->stringNormalizer->trimToNFC( $label );

		$description = $this->getRequest()->getVal(
			'description',
			isset( $this->parts[1] ) ? $this->parts[1] : ''
		);
		$this->description = $this->stringNormalizer->trimToNFC( $description );

		$aliases = $this->getRequest()->getVal( 'aliases' );
		$explodedAliases = $aliases === null ? [] : explode( '|', $aliases );
		foreach ( $explodedAliases as $alias ) {
			$alias = $this->stringNormalizer->trimToNFC( $alias );

			if ( $alias !== '' ) {
				$this->aliases[] = $alias;
			}
		}

		$this->contentLanguageCode = $this->getRequest()->getVal( 'lang', $this->getLanguage()->getCode() );
	}

	/**
	 * Checks whether required arguments are set sufficiently
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	protected function hasSufficientArguments() {
		return $this->label !== ''
			|| $this->description !== ''
			|| $this->aliases !== [];
	}

	/**
	 * @since 0.1
	 *
	 * @return EntityDocument Created entity of correct subtype
	 */
	abstract protected function createEntity();

	/**
	 * Attempt to modify entity
	 *
	 * @since 0.1
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return Status
	 */
	protected function modifyEntity( EntityDocument $entity ) {
		if ( !( $entity instanceof FingerprintProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a FingerprintProvider' );
		}

		$fingerprint = $entity->getFingerprint();
		$status = Status::newGood();

		$languageCode = $this->contentLanguageCode;
		if ( !in_array( $languageCode, $this->languageCodes ) ) {
			$status->error( 'wikibase-newitem-not-recognized-language' );
			return $status;
		}

		$fingerprint->setLabel( $languageCode, $this->label );
		$fingerprint->setDescription( $languageCode, $this->description );
		$fingerprint->setAliasGroup( $languageCode, $this->aliases );

		return $status;
	}

	/**
	 * Get options for language selector
	 *
	 * @return string[]
	 */
	private function getLanguageOptions() {
		$languageOptions = [];
		foreach ( $this->languageCodes as $code ) {
			$languageName = $this->languageNameLookup->getName( $code );
			$languageOptions["$languageName ($code)"] = $code;
		}
		return $languageOptions;
	}

	/**
	 * @return array[]
	 */
	protected function additionalFormElements() {
		$this->getOutput()->addModules( 'wikibase.special.languageLabelDescriptionAliases' );

		$langCode = $this->contentLanguageCode;
		$langDir = $this->languageDirectionalityLookup->getDirectionality( $this->contentLanguageCode );
		return [
			'lang' => [
				'name' => 'lang',
				'options' => $this->getLanguageOptions(),
				'default' => $langCode,
				'type' => 'combobox',
				'id' => 'wb-newentity-language',
				'validation-callback' => function ( $language ) {
					if ( !in_array( $language, $this->languageCodes ) ) {
						return [ $this->msg( 'wikibase-newitem-not-recognized-language' )->text() ];
					}

					return true;
				},
				'label-message' => 'wikibase-newentity-language'
			],
			'label' => [
				'name' => 'label',
				'default' => $this->label ?: '',
				'type' => 'text',
				'id' => 'wb-newentity-label',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-label-edit-placeholder'
				)->text(),
				'label-message' => 'wikibase-newentity-label'
			],
			'description' => [
				'name' => 'description',
				'default' => $this->description ?: '',
				'type' => 'text',
				'id' => 'wb-newentity-description',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-description-edit-placeholder'
				)->text(),
				'label-message' => 'wikibase-newentity-description'
			],
			'aliases' => [
				'name' => 'aliases',
				'default' => $this->aliases ? implode( '|', $this->aliases ) : '',
				'type' => 'text',
				'id' => 'wb-newentity-aliases',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-aliases-edit-placeholder'
				)->text(),
				'label-message' => 'wikibase-newentity-aliases'
			]
		];
	}

	/**
	 * @since 0.1
	 *
	 * @return string Legend for the fieldset
	 */
	abstract protected function getLegend();

	/**
	 * Returns any warnings.
	 *
	 * @since 0.4
	 *
	 * @return string[] Warnings that should be presented to the user
	 */
	abstract protected function getWarnings();

	/**
	 * @return HTMLForm
	 */
	private function createFormNew() {

		$additionalFormElements = $this->additionalFormElements();

		$this->getOutput()->addModules( 'wikibase.special.newEntity' );

		return HTMLForm::factory( 'ooui', $additionalFormElements, $this->getContext() )->setId(
				'mw-newentity-form1'
			)->setSubmitID( 'wb-newentity-submit' )->setSubmitName( 'submit' )->setSubmitTextMsg(
				'wikibase-newentity-submit'
			)->setWrapperLegendMsg( $this->getLegend() )->setSubmitCallback(
				function ( $data, HTMLForm $form ) {
					$status = $this->validateFormData( $data );
					if ( !$status->isGood() ) {
						return $status;
					}

					$entity = $this->formDataToEntity( $data );

					$summary = $this->createSummary( $entity );

					$status = $this->saveEntity(
						$entity,
						$summary,
						$form->getRequest()->getVal( 'wpEditToken' ),
						EDIT_NEW
					);

					if ( !$status->isGood() ) {
						return $status;
					}

					return Status::newGood( $entity );
				}
			);
	}

	/**
	 * @param array $data
	 * @param HTMLForm $form
	 *
	 * @return EntityDocument
	 */
	protected function formDataToEntity( array $data ) {
		$entity = $this->createEntity();

		$status = $this->modifyEntity( $entity );

		return $entity;
	}

	/**
	 * @param $entity
	 *
	 * @return Summary
	 */
	private function createSummary( $entity ) {
		$uiLanguageCode = $this->getLanguage()->getCode();

		$summary = new Summary( 'wbeditentity', 'create' );
		$summary->setLanguage( $uiLanguageCode );
		$summary->addAutoSummaryArgs( $this->label, $this->description );

		return $summary;
	}

	/**
	 * @param array $formData
	 *
	 * @return Status
	 */
	abstract protected function validateFormData( array $formData );

	/**
	 * @return string
	 */
	private function getCopyrightText() {
		return $this->copyrightView->getHtml( $this->getLanguage(), 'wikibase-newentity-submit' );
	}

}
