<?php

namespace Wikibase\Repo\Specials;

use Html;
use InvalidArgumentException;
use Language;
use PermissionsError;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\Utils;

/**
 * Abstract special page for setting a value of a Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialModifyTerm extends SpecialModifyEntity {

	/**
	 * The language the value is set in.
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * The value to set.
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $value;

	/**
	 * @var string
	 */
	protected $rightsUrl;

	/**
	 * @var string
	 */
	protected $rightsText;

	/**
	 * @since 0.4
	 *
	 * @param string $title The title of the special page
	 * @param string $restriction The required user right, 'edit' per default.
	 */
	public function __construct( $title, $restriction = 'edit' ) {
		parent::__construct( $title, $restriction );

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();

		$this->rightsUrl = $settings->getSetting( 'dataRightsUrl' );
		$this->rightsText = $settings->getSetting( 'dataRightsText' );
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
		$this->language = $request->getVal( 'language', isset( $parts[1] ) ? $parts[1] : '' );

		if ( $this->language === '' ) {
			$this->language = null;
		}

		if ( !$this->isValidLanguageCode( $this->language ) && $this->language !== null ) {
			$this->showErrorHTML( $this->msg( 'wikibase-modifyterm-invalid-langcode', $this->language )->parse() );
		}

		// Value
		$this->value = $this->getPostedValue();
		if ( $this->value === null ) {
			$this->value = $request->getVal( 'value' );
		}
	}

	/**
	 * @see SpecialModifyEntity::modifyEntity()
	 *
	 * @since 0.4
	 *
	 * @return Summary|bool
	 */
	protected function modifyEntity() {
		$request = $this->getRequest();

		// FIXME: This method is supposed to modify the entity and not alter the output. Do not
		// paste message directly into the HTML output in this method.
		if ( $this->entityContent === null || !$this->isValidLanguageCode( $this->language ) || !$request->wasPosted() ) {
			$this->addCopyrightText();

			return false;
		}

		try{
			$this->checkTermChangePermissions( $this->entityContent->getEntity() );
		} catch( PermissionsError $e ) {
			$this->showErrorHTML( $this->msg( 'permissionserrors' ) . ': ' . $e->permission );
			return false;
		}

		// to provide removing after posting the full form
		if ( $request->getVal( 'remove' ) === null && $this->value === '' ) {
			$this->showErrorHTML(
				// Messages: wikibase-setlabel-warning-remove, wikibase-setdescription-warning-remove,
				// wikibase-setaliases-warning-remove
				$this->msg(
					'wikibase-' . strtolower( $this->getName() ) . '-warning-remove',
					$this->entityContent->getTitle()->getText()
				)->parse(),
				'warning'
			);
			return false;
		}

		try {
			$summary = $this->setValue( $this->entityContent, $this->language, $this->value );
		} catch ( ChangeOpException $e ) {
			$this->showErrorHTML( $e->getMessage() );
			return false;
		}

		return $summary;
	}

	/**
	 * @param Entity $entity
	 *
	 * @throws PermissionsError
	 * @throws InvalidArgumentException
	 */
	protected function checkTermChangePermissions( Entity $entity ) {
		if( $entity instanceof Item ) {
			$type = 'item';
		} else if ( $entity instanceof Property ) {
			$type = 'property';
		} else {
			throw new InvalidArgumentException( 'Unexpected Entity type when checking special page term change permissions' );
		}
		$restriction = $type . '-term';
		if ( !$this->getUser()->isAllowed( $restriction ) ) {
			throw new PermissionsError( $restriction );
		}
	}

	/**
	 * @todo could factor this out into a special page form builder and renderer
	 */
	protected function addCopyrightText() {
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$this->rightsUrl,
			$this->rightsText
		);

		$html = $copyrightView->getHtml( $this->getLanguage() );

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Checks if the language code is valid.
	 *
	 * @since 0.4
	 *
	 * @param $languageCode string the language code
	 *
	 * @return bool
	 */
	private function isValidLanguageCode( $languageCode ) {
		return $languageCode !== null && Language::isValidBuiltInCode( $languageCode ) && in_array( $languageCode, Utils::getLanguageCodes() );
	}

	/**
	 * @see SpecialModifyEntity::getFormElements()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getFormElements() {
		$this->language = $this->language ? $this->language : $this->getLanguage()->getCode();
		if ( $this->value === null ) {
			$this->value = $this->getValue( $this->entityContent, $this->language );
		}
		$valueinput = Html::input(
			'value',
			$this->getRequest()->getVal( 'value' ) ? $this->getRequest()->getVal( 'value' ) : $this->value,
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-modifyterm-value',
				'size' => 50
			)
		)
		. Html::element( 'br' );

		$languageName = Language::fetchLanguageName( $this->language, $this->getLanguage()->getCode() );

		if ( $this->entityContent !== null && $this->language !== null && $languageName !== '' ) {
			return Html::rawElement(
				'p',
				array(),
				// Messages: wikibase-setlabel-introfull, wikibase-setdescription-introfull,
				// wikibase-setaliases-introfull
				$this->msg(
					'wikibase-' . strtolower( $this->getName() ) . '-introfull',
					$this->entityContent->getTitle()->getPrefixedText(),
					$languageName
				)->parse()
			)
			. Html::input( 'language', $this->language, 'hidden' )
			. Html::input( 'id', $this->entityContent->getEntity()->getId()->getSerialization(), 'hidden' )
			. Html::input( 'remove', 'remove', 'hidden' )
			. $valueinput;
		}
		else {
			return Html::rawElement(
				'p',
				array(),
				// Messages: wikibase-setlabel-intro, wikibase-setdescription-intro,
				// wikibase-setaliases-intro
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-intro' )->parse()
			)
			. parent::getFormElements()
			. Html::element(
				'label',
				array(
					'for' => 'wb-modifyterm-language',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-modifyterm-language' )->text()
			)
			. Html::input(
				'language',
				$this->language,
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-modifyterm-language'
				)
			)
			. Html::element( 'br' )
			. Html::element(
				'label',
				array(
					'for' => 'wb-modifyterm-value',
					'class' => 'wb-label'
				),
				// Messages: wikibase-setlabel-label, wikibase-setdescription-label,
				// wikibase-setaliases-label
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-label' )->text()
			)
			. $valueinput;
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
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 *
	 * @return string
	 */
	abstract protected function getValue( $entityContent, $language );

	/**
	 * Setting the value of the entity name by the given language
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 * @param string $value
	 *
	 * @return Summary
	 */
	abstract protected function setValue( $entityContent, $language, $value );

}
