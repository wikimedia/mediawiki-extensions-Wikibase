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
 * @author Denny Vrandecic < denny@vrandecic.de >
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
abstract class SpecialSetEntity extends SpecialWikibasePage {

	/**
	 * The name of the value to set.
	 * @var string
	 */
	protected $valueName;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param string $title
	 * @param string $valueName The name of the value to set.
	 * @param string $restriction The required user right, 'edit' per default.
	 */
	public function __construct( $valueName, $title = '', $restriction = 'edit' ) {
		parent::__construct( $title == '' ? 'Set' . $valueName : $title, $restriction );
		$this->valueName = strtolower( $valueName );
	}

	/**
	 * Main method
	 *
	 * @since 0.4
	 *
	 * @param string|null $subPage
	 *
	 * @return boolean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$this->setHeaders();
		$this->outputHeader();

		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

		// Get id
		$rawId = $this->getRequest()->getVal( 'id', isset( $parts[0] ) ? $parts[0] : '' );
		$id = \Wikibase\EntityId::newFromPrefixedId( $rawId );

		if ( $id === null ) {
			$entityContent = null;
		}
		else {
			$entityContent = \Wikibase\EntityContentFactory::singleton()->getFromId( $id );
		}

		// Get language
		$language = $request->getVal( 'language', isset( $parts[1] ) ? $parts[1] : '' );
		// Get value
		$value = $request->getVal( $this->valueName );

		if( $rawId === '' ) {
			$rawId = null;
		}

		if ( $language === '' ) {
			$language = null;
		}

		if ( $language !== null ) {
			if ( ( !Language::isValidBuiltInCode( $language ) || ( !in_array( $language, \Wikibase\Utils::getLanguageCodes() ) ) ) ) {
				$this->showError( $this->msg( 'wikibase-setentity-invalid-langcode', $language )->text() );
			}
		}

		if ( $entityContent === null && $value !== null && $rawId !== null ) {
			$this->showError( $this->msg( 'wikibase-setentity-invalid-id', $rawId )->text() );
		}

		if ( $entityContent !== null && $language !== null && $this->getRequest()->wasPosted() ) {
			$status = $this->setValue( $entityContent, $language, $value );

			if ( $status->isGood() ) {
				list( $counts, $summary, $lang ) = Autocomment::formatAutoSummary(
					array( $value ),
					$this->getLanguage()
				);

				$comment = Autocomment::formatAutoComment(
					'special-set' . $this->valueName . '-set',
					array( $counts, $language )
				);

				//TODO: need conflict detection??
				$editEntity = new \Wikibase\EditEntity( $entityContent, $this->getUser(), false, $this->getContext() );
				$editEntity->attemptSave(
					AutoComment::formatTotalSummary( $comment, $summary, $lang ),
					EDIT_UPDATE,
					$request->getVal( 'wpEditToken' )
				);

				if ( !$editEntity->isSuccess() ) {
					$this->showError( $editEntity->getStatus()->getMessage() );
				}
				else {
					$entityUrl = $entityContent->getTitle()->getFullUrl();
					$this->getOutput()->redirect( $entityUrl );
				}
			}
			else {
				$this->showError( $status->getHTML() );
				$this->setEntityForm( $entityContent, $language, $value );
			}
		}
		else {
			$this->setEntityForm( $entityContent, $language, $value );
		}
	}

	/**
	 * Showing an error.
	 *
	 * @since 0.4
	 *
	 * @param string $error the error message
	 * @param string $class the element's class, default 'error'
	 */
	private function showError( $error, $class = 'error' ) {
		$this->getOutput()->addHTML(
			Html::element(
				'p',
				array( 'class' => $class ),
				$error
			)
		);
	}

	/**
	 * Building the HTML form for setting the value of an entity. If the entity and the language are already given,
	 * the form will only ask for the value. If not, a complete form is being shown.
	 *
	 * @since 0.2
	 *
	 * @param \Wikibase\EntityContent|null $entityContent the entity to have the value set
	 * @param string|null $language language code for the value
	 * @param string $value
	 */
	public function setEntityForm( $entityContent, $language, $value ) {
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		if ( $this->getUser()->isAnon() ) {
			$this->showError( $this->msg( 'wikibase-anonymouseditwarning-item' ), 'warning' );
		}

		$getValue = 'get' . ucfirst( $this->valueName );
		if ( $value === null ) {
			$value = $this->getValue( $entityContent, $language );
		}

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getTitle()->getFullUrl(),
					'name' => 'set' . $this->valueName,
					'id' => 'wb-set' . $this->valueName . '-form1',
					'class' => 'wb-form'
				)
			)
			. Html::openElement(
				'fieldset',
				array( 'class' => 'wb-fieldset' )
			)
			. Html::element(
				'legend',
				array( 'class' => 'wb-legend' ),
				$this->msg( 'special-set' . $this->valueName )->text()
			)
		);

		if ( ( $entityContent !== null ) && ( $language !== null ) ) {
			$this->getOutput()->addWikiMsg(
				'wikibase-set' . $this->valueName . '-introfull',
				$entityContent->getTitle()->getPrefixedText(),
				\Language::fetchLanguageName( $language, $this->getLanguage()->getCode() )
			);
			$this->getOutput()->addHTML(
				Html::input( 'language', $language, 'hidden' )
				. Html::input( 'id', $entityContent->getTitle()->getText(), 'hidden' )
			);
		}
		else {
			$id = $entityContent ? $entityContent->getTitle()->getText() : '';
			$language = $language ? $language : $this->getLanguage()->getCode();
			$value = $this->getValue( $entityContent, $language ); // really do this?
			$this->getOutput()->addHTML(
				Html::element(
					'p',
					array(),
					$this->msg( 'wikibase-set' . $this->valueName . '-intro' )->text()
				)
				. Html::element(
					'label',
					array(
						'for' => 'wb-setentity-id',
						'class' => 'wb-label'
					),
					$this->msg( 'wikibase-setentity-id' )->text()
				)
				. Html::input(
					'id',
					$id,
					'text',
					array(
						'class' => 'wb-input',
						'id' => 'wb-setentity-id'
					)
				)
				. Html::element( 'br' )
				. Html::element(
					'label',
					array(
						'for' => 'wb-setentity-language',
						'class' => 'wb-label'
					),
					$this->msg( 'wikibase-setentity-language' )->text()
				)
				. Html::element( 'br' )
				. Html::input(
					'language',
					$language,
					'text',
					array(
						'class' => 'wb-input',
						'id' => 'wb-setentity-language'
					)
				)
				. Html::element(
					'label',
					array(
						'for' => 'wb-setentity-value',
						'class' => 'wb-label'
					),
					$this->msg( 'wikibase-set' . $this->valueName . '-' . $this->valueName )->text()
				)
				. Html::element( 'br' )
			);
		}

		$this->getOutput()->addHTML(
			Html::input(
				$this->valueName,
				htmlspecialchars( $value ),
				'text',
				array(
					'class' => 'wb-input wb-input-text wb-input-text-' . $this->valueName,
					'id' => 'wb-setentity-value',
					'size' => 50
				)
			)
			. Html::element( 'br' )
			. Html::input(
				'wikibase-set' . $this->valueName . '-submit',
				$this->msg( 'wikibase-set' . $this->valueName . '-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-set' . $this->valueName . '-submit',
					'class' => 'wb-button'
				)
			)
			. Html::input(
				'wpEditToken',
				$this->getUser()->getEditToken(),
				'hidden'
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

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
	 * @return Status
	 */
	abstract protected function setValue( $entityContent, $language, $value );
}