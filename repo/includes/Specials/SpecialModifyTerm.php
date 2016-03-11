<?php

namespace Wikibase\Repo\Specials;

use Html;
use InvalidArgumentException;
use Language;
use PermissionsError;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Abstract special page for setting a value of a Wikibase entity.
 *
 * @since 0.4
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
	 * @since 0.4
	 *
	 * @param string $title The title of the special page
	 * @param string $restriction The required user right, 'edit' per default.
	 */
	public function __construct( $title, $restriction = 'edit' ) {
		parent::__construct( $title, $restriction );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
		$this->termsLanguages = $wikibaseRepo->getTermsLanguages();
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialModifyEntity::prepareArguments()
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	protected function prepareArguments( $subPage ) {
		parent::prepareArguments( $subPage );

		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

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
	}

	/**
	 * Check the language given as sup page argument.
	 */
	private function checkSubPageLanguage() {
		if ( $this->languageCode !== null && !$this->termsLanguages->hasLanguage( $this->languageCode ) ) {
			$errorMessage = $this->msg(
				'wikibase-wikibaserepopage-invalid-langcode',
				$this->languageCode
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
		$request = $this->getRequest();

		if ( !parent::validateInput() ) {
			return false;
		}

		try {
			$this->checkTermChangePermissions( $this->entityRevision->getEntity() );
		} catch ( PermissionsError $e ) {
			$this->showErrorHTML( $this->msg( 'permissionserrors' ) . ': ' . $e->permission );
			return false;
		}

		// If the user just enters an item id and a language, dont remove the term.
		// The user can remove the term in the second form where it has to be
		// actually removed. This prevents users from removing terms accidentally.
		if ( !$request->getCheck( 'remove' ) && $this->value === '' ) {
			$this->value = null;
			return false;
		}

		return true;
	}

	/**
	 * @see SpecialModifyEntity::modifyEntity()
	 *
	 * @since 0.5
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
	 * @param EntityDocument $entity
	 *
	 * @throws PermissionsError
	 * @throws InvalidArgumentException
	 */
	private function checkTermChangePermissions( EntityDocument $entity ) {
		$restriction = $entity->getType() . '-term';

		if ( !$this->getUser()->isAllowed( $restriction ) ) {
			throw new PermissionsError( $restriction );
		}
	}

	/**
	 * @see SpecialModifyEntity::getFormElements()
	 *
	 * @param EntityDocument|null $entity
	 *
	 * @return string HTML
	 */
	protected function getFormElements( EntityDocument $entity = null ) {
		if ( $this->languageCode === null ) {
			$this->languageCode = $this->getLanguage()->getCode();
		}

		$this->setValueIfNull( $entity );

		$valueinput = Html::input(
			'value',
			$this->getRequest()->getVal( 'value' ) ? $this->getRequest()->getVal( 'value' ) : $this->value,
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-modifyterm-value',
			)
		);

		$languageName = Language::fetchLanguageName( $this->languageCode, $this->getLanguage()->getCode() );

		if ( $entity !== null && $this->languageCode !== null && $languageName !== '' ) {
			return Html::rawElement(
				'p',
				array(),
				// Messages: wikibase-setlabel-introfull, wikibase-setdescription-introfull,
				// wikibase-setaliases-introfull
				$this->msg(
					'wikibase-' . strtolower( $this->getName() ) . '-introfull',
					$this->getEntityTitle( $entity->getId() )->getPrefixedText(),
					$languageName
				)->parse()
			)
			. Html::input( 'language', $this->languageCode, 'hidden' )
			. Html::input( 'id', $entity->getId()->getSerialization(), 'hidden' )
			. Html::input( 'remove', 'remove', 'hidden' )
			. $valueinput;
		} else {
			return Html::rawElement(
				'p',
				array(),
				// Messages: wikibase-setlabel-intro, wikibase-setdescription-intro,
				// wikibase-setaliases-intro
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-intro' )->parse()
			)
			. parent::getFormElements( $entity )
			. Html::element( 'br' )
			. Html::label(
				$this->msg( 'wikibase-modifyterm-language' )->text(),
				'wb-modifyterm-language',
				array(
					'class' => 'wb-label'
				)
			)
			. Html::input(
				'language',
				$this->languageCode,
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-modifyterm-language'
				)
			)
			. Html::element( 'br' )
			. Html::label(
				// Messages:
				// wikibase-setlabel-label
				// wikibase-setdescription-label
				// wikibase-setaliases-label
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-label' )->text(),
				'wb-modifyterm-value',
				array(
					'class' => 'wb-label'
				)
			)
			. $valueinput
			. Html::element( 'br' );
		}
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
	 * @since 0.4
	 *
	 * @return string
	 */
	abstract protected function getPostedValue();

	/**
	 * Returning the value of the entity name by the given language
	 *
	 * @since 0.5
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
	 * @since 0.5
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 * @param string $value
	 * @return Summary
	 */
	abstract protected function setValue( EntityDocument $entity, $languageCode, $value );

}
