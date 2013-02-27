<?php

use Wikibase\Autocomment;
use Wikibase\EntityId;

/**
 * Special page for merging entities.
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
class SpecialMergeEntities extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param string $title
	 * @param string $valueName The name of the value to set.
	 * @param string $restriction The required user right, 'edit' per default.
	 */
	public function __construct( $restriction = 'edit' ) {
		parent::__construct( 'MergeEntities', $restriction );
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

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkReadOnly();

		//FIXME: make sure page protection is checked!

		$request = $this->getRequest();

		// Get id
		$redir = $this->getRequest()->getVal( 'redir', '' );
		$target = $this->getRequest()->getVal( 'target', '' );

		$redirId = $redir === '' ? null : \Wikibase\EntityId::newFromPrefixedId( $redir );
		$targetId = $target === '' ? null : \Wikibase\EntityId::newFromPrefixedId( $target );

		//FIXME: check that both exist!

		if ( $redirId === null || $targetId === null ) {
			if ( $redir !== '' && $redirId === null ) {
				$this->showError( "bad id: " . $redir ); //FIXME: i18n
			}

			if ( $target !== '' && $targetId === null ) {
				$this->showError( "bad id: " . $target ); //FIXME: i18n
			}

			$this->showForm();
		} else {
			$this->saveRedirect( $redirId, $targetId );
		}
	}

	protected function saveRedirect( EntityId $redirId, EntityId $targetId ) {
		$request = $this->getContext()->getRequest();

		$entityContent = \Wikibase\EntityContentFactory::singleton()->getFromId( $redirId );
		$entity = $entityContent->getEntity();

		$entity->setRedirectTarget( $targetId ); //FIXME: check type first, to low level exception

		//FIXME: check that both exist!

		$summary = "Merged " . $redirId->getPrefixedId . " into " . $targetId->getPrefixedId(); //FIXME: magic summary!

		$editEntity = new \Wikibase\EditEntity( $entityContent, $this->getUser(), false, $this->getContext() );
		$editEntity->attemptSave(
			$summary,
			EDIT_UPDATE,
			$request->getVal( 'wpEditToken' )
		);

		if ( !$editEntity->isSuccess() ) {
			$this->showError( $editEntity->getStatus()->getWikiText() );
		}
		else {
			$entityUrl = $entityContent->getTitle()->getFullUrl();
			$this->getOutput()->redirect( $entityUrl );
		}
	}

	public function showForm( ) {
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		if ( $this->getUser()->isAnon() ) {
			$this->showError( $this->msg( 'wikibase-anonymouseditwarning-item' ), 'warning' );
		}

		$redir = $this->getRequest()->getVal( 'redir', '' );
		$target = $this->getRequest()->getVal( 'target', '' );

		$html = '';

		$html .= Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getTitle()->getFullUrl(),
					'name' => strtolower( $this->getName() ),
					'id' => 'wb-' . strtolower( $this->getName() ) . '-form1',
					'class' => 'wb-form'
				)
			);


		$html .=  Html::openElement(
				'fieldset',
				array( 'class' => 'wb-fieldset' )
			);

		$html .=  Html::element(
				'legend',
				array( 'class' => 'wb-legend' ),
				$this->msg( 'special-' . strtolower( $this->getName() ) )->text()
			);

		$html .= Html::element(
					'p',
					array(),
					$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-intro' )->text()
				);

		$html .= Html::element(
					'label',
					array(
						'for' => 'wb-mergeentities-redir',
						'class' => 'wb-label'
					),
					$this->msg( 'wikibase-mergeentities-redir' )->text()
				);

		$html .= Html::input(
					'redir',
					$redir,
					'text',
					array(
						'class' => 'wb-input',
						'id' => 'wb-mergeentities-redir'
					)
				);

		$html .= Html::element( 'br' );

		$html .= Html::element(
			'label',
			array(
				'for' => 'wb-mergeentities-target',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-mergeentities-target' )->text()
		);

		$html .= Html::input(
			'target',
			$redir,
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-mergeentities-target'
			)
		);

		$html .=  Html::element( 'br' );

		$html .=  Html::input(
				'wikibase-' . strtolower( $this->getName() ) . '-submit',
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-' . strtolower( $this->getName() ) . '-submit',
					'class' => 'wb-button'
				)
			);

		$html .=  Html::input(
				'wpEditToken',
				$this->getUser()->getEditToken(),
				'hidden'
			);

		$html .=  Html::closeElement( 'fieldset' );
		$html .=  Html::closeElement( 'form' );

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Showing an error.
	 *
	 * @since 0.4
	 *
	 * @param string $error the error message
	 * @param string $class the element's class, default 'error'
	 */
	protected function showError( $error, $class = 'error' ) {
		$this->getOutput()->addHTML(
			Html::rawElement(
				'p',
				array( 'class' => $class ),
				$error
			)
		);
	}

	/**
	 * Output an error message telling the user that he is blocked
	 */

	/**
	 * Checks if user is blocked, and if he is blocked throws a UserBlocked
	 *
	 * @since 0.4
	 */
	public function checkBlocked() {
		if ( $this->getUser()->isBlocked() ) {
			throw new UserBlockedError( $this->getUser()->getBlock() );
		}
	}

}