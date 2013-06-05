<?php

use Wikibase\Autocomment;

/**
 * Abstract special page for setting a value of a Wikibase entity.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
abstract class SpecialSetEntity extends SpecialModifyEntity {

	/**
	 * The langauge the value is set in.
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
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param string $title The title of the special page
	 * @param string $restriction The required user right, 'edit' per default.
	 */
	public function __construct( $title, $restriction = 'edit' ) {
		parent::__construct( $title, $restriction );
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
			$this->showErrorHTML( $this->msg( 'wikibase-setentity-invalid-langcode', $this->language )->parse() );
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
	 * @return string|boolean The summary or false
	 */
	protected function modifyEntity() {
		$request = $this->getRequest();

		if ( $this->entityContent === null || !$this->isValidLanguageCode( $this->language ) || !$request->wasPosted() ) {
			$this->showRightsMessage();

			return false;
		}

		// to provide removing after posting the full form
		if ( $request->getVal( 'remove' ) === null && $this->value === '' ) {
			$this->showErrorHTML(
				$this->msg(
					'wikibase-' . strtolower( $this->getName() ) . '-warning-remove',
					$this->entityContent->getTitle()->getText()
				)->parse(),
				'warning'
			);
			return false;
		}

		$status = $this->setValue( $this->entityContent, $this->language, $this->value, $summary );

		if ( !$status->isGood() ) {
			$this->showErrorHTML( $status->getHTML() );
			return false;
		}

		return $summary;
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
		return $languageCode !== null && Language::isValidBuiltInCode( $languageCode ) && in_array( $languageCode, \Wikibase\Utils::getLanguageCodes() );
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
			$this->value,
			'text',
			array(
				'class' => 'wb-input wb-input-text',
				'id' => 'wb-setentity-value',
				'size' => 50
			)
		)
		. Html::element( 'br' );

		$languageName = \Language::fetchLanguageName( $this->language, $this->getLanguage()->getCode() );

		if ( $this->entityContent !== null && $this->language !== null && $languageName !== '' ) {
			return Html::rawElement(
				'p',
				array(),
				$this->msg(
					'wikibase-' . strtolower( $this->getName() ) . '-introfull',
					$this->entityContent->getTitle()->getPrefixedText(),
					$languageName
				)->parse()
			)
			. Html::input( 'language', $this->language, 'hidden' )
			. Html::input( 'id', $this->entityContent->getTitle()->getText(), 'hidden' )
			. Html::input( 'remove', 'remove', 'hidden' )
			. $valueinput;
		}
		else {
			return Html::rawElement(
				'p',
				array(),
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-intro' )->parse()
			)
			. parent::getFormElements()
			. Html::element(
				'label',
				array(
					'for' => 'wb-setentity-language',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-setentity-language' )->text()
			)
			. Html::input(
				'language',
				$this->language,
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-setentity-language'
				)
			)
			. Html::element( 'br' )
			. Html::element(
				'label',
				array(
					'for' => 'wb-setentity-value',
					'class' => 'wb-label'
				),
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
	 * @param string &$summary The summary for this edit will be saved here.
	 *
	 * @return Status
	 */
	abstract protected function setValue( $entityContent, $language, $value, &$summary );

}
