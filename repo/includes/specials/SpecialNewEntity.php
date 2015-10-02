<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Html;
use Language;
use Status;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Page for creating new Wikibase entities.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialNewEntity extends SpecialWikibaseRepoPage {

	/**
	 * Contains pieces of the sub-page name of this special page if a subpage was called.
	 * E.g. array( 'a', 'b' ) in case of 'Special:NewEntity/a/b'
	 * @var string[]|null
	 */
	protected $parts = null;

	/**
	 * @var string|null
	 */
	private $label = null;

	/**
	 * @var string|null
	 */
	private $description = null;

	/**
	 * @var Language
	 */
	private $contentLanguage = null;

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @var string
	 */
	private $rightsUrl;

	/**
	 * @var string
	 */
	private $rightsText;

	/**
	 * @var string[]
	 */
	private $aliases;

	/**
	 * @param string $name Name of the special page, as seen in links and URLs.
	 * @param string $restriction User right required, 'createpage' per default.
	 *
	 * @since 0.1
	 */
	public function __construct( $name, $restriction = 'createpage' ) {
		parent::__construct( $name, $restriction );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		// TODO: find a way to inject these
		$this->summaryFormatter = $wikibaseRepo->getSummaryFormatter();
		$this->languageCodes = $wikibaseRepo->getTermsLanguages()->getLanguages();

		$settings = $wikibaseRepo->getSettings();

		$this->rightsUrl = $settings->getSetting( 'dataRightsUrl' );
		$this->rightsText = $settings->getSetting( 'dataRightsText' );
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

		$this->parts = ( $subPage === '' ? array() : explode( '/', $subPage ) );
		$this->prepareArguments();

		$out = $this->getOutput();

		$uiLanguageCode = $this->getLanguage()->getCode();

		if ( $this->getRequest()->wasPosted()
			&& $this->getUser()->matchEditToken( $this->getRequest()->getVal( 'wpEditToken' ) )
		) {
			if ( $this->hasSufficientArguments() ) {
				$entity = $this->createEntity();

				$status = $this->modifyEntity( $entity );

				if ( $status->isGood() ) {
					$summary = new Summary( 'wbeditentity', 'create' );
					$summary->setLanguage( $uiLanguageCode );
					$summary->addAutoSummaryArgs( $this->label, $this->description );

					$status = $this->saveEntity(
						$entity,
						$summary,
						$this->getRequest()->getVal( 'wpEditToken' ),
						EDIT_NEW
					);

					$out = $this->getOutput();

					if ( !$status->isOK() ) {
						$out->addHTML( '<div class="error">' );
						$out->addWikiText( $status->getWikiText() );
						$out->addHTML( '</div>' );
					} elseif ( $entity !== null ) {
						$title = $this->getEntityTitle( $entity->getId() );
						$entityUrl = $title->getFullUrl();
						$this->getOutput()->redirect( $entityUrl );
					}
				} else {
					$out->addHTML( '<div class="error">' );
					$out->addHTML( $status->getHTML() );
					$out->addHTML( '</div>' );
				}
			}
		}

		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		foreach ( $this->getWarnings() as $warning ) {
			$out->addHTML( Html::element( 'div', array( 'class' => 'warning' ), $warning ) );
		}

		$this->createForm( $this->getLegend(), $this->additionalFormElements() );
	}

	/**
	 * Tries to extract argument values from web request or of the page's sub-page parts
	 *
	 * @since 0.1
	 */
	protected function prepareArguments() {
		$this->label = $this->getRequest()->getVal(
			'label',
			isset( $this->parts[0] ) ? $this->parts[0] : ''
		);
		$this->description = $this->getRequest()->getVal(
			'description',
			isset( $this->parts[1] ) ? $this->parts[1] : ''
		);
		$aliases = $this->getRequest()->getVal( 'aliases' );
		$this->aliases = ( $aliases === null ? array() : explode( '|', $aliases ) );
		$this->contentLanguage = Language::factory( $this->getRequest()->getVal(
			'lang',
			$this->getLanguage()->getCode()
		) );
	}

	/**
	 * Checks whether required arguments are set sufficiently
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	protected function hasSufficientArguments() {
		return $this->stringNormalizer->trimWhitespace( $this->label ) !== ''
			|| $this->stringNormalizer->trimWhitespace( $this->description ) !== ''
			|| implode( '', array_map(
					array( $this->stringNormalizer, 'trimWhitespace' ),
					$this->aliases
				) ) !== '';
	}

	/**
	 * @since 0.1
	 *
	 * @return Entity Created entity of correct subtype
	 */
	abstract protected function createEntity();

	/**
	 * Attempt to modify entity
	 *
	 * @since 0.1
	 *
	 * @param Entity &$entity
	 *
	 * @return Status
	 */
	protected function modifyEntity( Entity &$entity ) {
		$contentLanguageCode = $this->contentLanguage->getCode();
		if ( $this->label !== '' ) {
			$entity->setLabel( $contentLanguageCode, $this->label );
		}
		if ( $this->description !== '' ) {
			$entity->setDescription( $contentLanguageCode, $this->description );
		}
		if ( count( $this->aliases ) !== 0 ) {
			$entity->setAliases( $contentLanguageCode, $this->aliases );
		}
		return \Status::newGood();
	}

	/**
	 * Get options for language selector
	 *
	 * @return array
	 */
	private function getLanguageOptions() {
		$names = Language::fetchLanguageNames( null, 'all' );
		$languageOptions = array();
		foreach ( $this->languageCodes as $code ) {
			$languageOptions[isset( $names[$code] ) ? $names[$code] : $code] = $code;
		}
		return $languageOptions;
	}

	/**
	 * Build additional formelements
	 *
	 * @since 0.1
	 *
	 * @return string Formatted HTML for inclusion in the form
	 */
	protected function additionalFormElements() {
		$this->getOutput()->addModules( 'wikibase.special.languageLabelDescriptionAliases' );

		$langCode = $this->contentLanguage->getCode();
		$langDir = $this->contentLanguage->getDir();
		return array(
			'lang' => array(
				'name' => 'lang',
				'options' => $this->getLanguageOptions(),
				'default' => $langCode,
				'type' => 'select',
				'id' => 'wb-newentity-language',
				'label-message' => 'wikibase-newentity-language'
			),
			'label' => array(
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
			),
			'description' => array(
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
			),
			'aliases' => array(
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
			)
		);
	}

	/**
	 * Building the HTML form for creating a new item.
	 *
	 * @param string|null $legend initial value for the label input box
	 * @param array $additionalFormElements initial value for the description input box
	 */
	private function createForm( $legend = null, $additionalFormElements = array() ) {
		$this->addCopyrightText();

		HTMLForm::factory( 'ooui', $additionalFormElements, $this->getContext() )
			->setId( 'mw-newentity-form1' )
			->setSubmitID( 'wb-newentity-submit' )
			->setSubmitName( 'submit' )
			->setSubmitTextMsg( 'wikibase-newentity-submit' )
			->setWrapperLegendMsg( $legend )
			->setSubmitCallback( function () {// no-op
			} )->show();
	}

	/**
	 * @todo could factor this out into a special page form builder and renderer
	 */
	private function addCopyrightText() {
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$this->rightsUrl,
			$this->rightsText
		);

		$html = $copyrightView->getHtml( $this->getLanguage(), 'wikibase-newentity-submit' );

		$this->getOutput()->addHTML( $html );
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

}
