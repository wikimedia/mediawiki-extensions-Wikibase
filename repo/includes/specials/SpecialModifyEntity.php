<?php

use Wikibase\Autocomment;
use Wikibase\Utils;

/**
 * Abstract special page for modifing Wikibase entity.
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
abstract class SpecialModifyEntity extends SpecialWikibasePage {

	/**
	 * The entity content to modify.
	 *
	 * @since 0.4
	 *
	 * @var \Wikibase\EntityContent
	 */
	protected $entityContent;

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
	 * Main method
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 *
	 * @return boolean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkReadOnly();

		$this->setHeaders();
		$this->outputHeader();

		$this->prepareArguments( $subPage );

		$summary = $this->modifyEntity();

		if ( $summary === false ) {
			$this->setForm();
		}
		else {
			//TODO: need conflict detection??
			$editEntity = new \Wikibase\EditEntity( $this->entityContent, $this->getUser(), false, $this->getContext() );
			$editEntity->attemptSave(
				$summary,
				EDIT_UPDATE,
				$this->getRequest()->getVal( 'wpEditToken' )
			);

			if ( !$editEntity->isSuccess() ) {
				$editEntity->showErrorPage();
			}
			else {
				$entityUrl = $this->entityContent->getTitle()->getFullUrl();
				$this->getOutput()->redirect( $entityUrl );
			}
		}
	}

	/**
	 * Prepares the arguments.
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	protected function prepareArguments( $subPage ) {
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

		// Get id
		$rawId = $this->getRequest()->getVal( 'id', isset( $parts[0] ) ? $parts[0] : '' );
		$id = \Wikibase\EntityId::newFromPrefixedId( $rawId );

		if ( $id === null ) {
			$this->entityContent = null;
		}
		else {
			$this->entityContent = \Wikibase\EntityContentFactory::singleton()->getFromId( $id );
		}

		if ( $rawId === '' ) {
			$rawId = null;
		}

		if ( $this->entityContent === null && $rawId !== null ) {
			$this->showErrorHTML( $this->msg( 'wikibase-setentity-invalid-id', $rawId )->parse() );
		}
	}

	/**
	 * Showing an error.
	 *
	 * @since 0.4
	 *
	 * @param string $error The error message in HTML format
	 * @param string $class The element's class, default 'error'
	 */
	protected function showErrorHTML( $error, $class = 'error' ) {
		$this->getOutput()->addHTML(
			Html::rawElement(
				'p',
				array( 'class' => $class ),
				$error
			)
		);
	}

	/**
	 * Building the HTML form for modifying an entity.
	 *
	 * @since 0.2
	 */
	private function setForm() {
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		if ( $this->getUser()->isAnon() ) {
			$this->showErrorHTML(
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-item' )->text()
				)->parse(),
				'warning'
			);
		}

		// Form header
		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getTitle()->getFullUrl(),
					'name' => strtolower( $this->getName() ),
					'id' => 'wb-' . strtolower( $this->getName() ) . '-form1',
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
				$this->msg( 'special-' . strtolower( $this->getName() ) )->text()
			)
		);

		// Form elements
		$this->getOutput()->addHTML( $this->getFormElements() );

		// Form body
		$this->getOutput()->addHTML(
			Html::input(
				'wikibase-' . strtolower( $this->getName() ) . '-submit',
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-' . strtolower( $this->getName() ) . '-submit',
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
	 * Returns the form elements.
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getFormElements() {
		$id = $this->entityContent ? $this->entityContent->getTitle()->getText() : '';
		return Html::element(
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
		. Html::element( 'br' );
	}

	/**
	 * Modifies the entity.
	 *
	 * @since 0.4
	 *
	 * @return string|boolean The summary or false
	 */
	abstract protected function modifyEntity();

	/**
	 * Returning the summary for editing.
	 *
	 * @since 0.4
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $i18n The i18n key of the summary
	 *
	 * @return string The summary
	 */
	protected function getSummary( $key, $value, $i18n ) {
		list( $counts, $summary, $lang ) = Autocomment::formatAutoSummary(
			array( $value ),
			$this->getLanguage()
		);

		$comment = Autocomment::formatAutoComment(
			$i18n,
			array( $counts, $key )
		);

		return AutoComment::formatTotalSummary( $comment, $summary, $lang );
	}

	/**
	 * Output an error message telling the user that he is blocked
	 */
	function displayBlockedError() {
		throw new UserBlockedError( $this->getUser()->getBlock() );
	}

	/**
	 * Checks if user is blocked, and if he is blocked throws a UserBlocked
	 *
	 * @todo factor out to have some generic code for all editing
	 *	   Wikibase pages to be able to use.  This applies to new entities also.
	 *
	 * @since 0.4
	 */
	public function checkBlocked() {
		if ( $this->getUser()->isBlocked() ) {
			$this->displayBlockedError();
		}
	}

	/**
	 * Show the rights warning message
	 *
	 * @since 0.4
	 */
	public function showRightsMessage() {
		$this->showErrorHTML(
			Html::rawElement(
				'p',
				array(),
				Utils::getRightsWarningMessage()->parse()
			),
			'warning'
		);
	}
}
