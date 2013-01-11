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
 * @author Denny Vrandecic < denny@vrandecic.de > , Bene* < benestar.wikimedia@googlemail.com >
 */
abstract class SpecialSetEntity extends SpecialWikibasePage {
	
	protected $entityName;
	
	/**
	 * Constructor.
	 * 
	 * @since 0.4
	 * 
	 * @param string $title
	 * @param string $entityName The name of the entity to set.
	 */
	public function __construct( $title, $entityName ) {
		parent::__construct( $title );
		$this->entityName = $entityName;
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
		$this->setHeaders();
		$this->outputHeader();

		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

		$rawId = $this->getRequest()->getVal( 'id', $parts[0] );
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
		$value = $request->getVal( $this->entityName );

		if ( $language === '' ) {
			$language = null;
		}

		if ( $language !== null ) {
			if ( ( !Language::isValidBuiltInCode( $language ) or ( !in_array( $language, \Wikibase\Utils::getLanguageCodes() ) ) ) ) {
				$this->getOutput()->addWikiMsg( 'wikibase-set' . $this->entityName . '-invalid-langcode', $language );
				$language = null;
			}
		}

		if ( $entityContent === null && $value !== null ) {
			$this->getOutput()->addWikiMsg( 'wikibase-set' . $this->entityName . '-invalid-id', $rawId );
		}

		if ( $entityContent !== null && $language !== null && $this->getRequest()->wasPosted() ) {
			
			$this->setValue( $language, $value );

			list( $counts, $summary, $lang) = Autocomment::formatAutoSummary(
				array( $value ),
				$this->getLanguage()
			);
			
			$comment = Autocomment::formatAutoComment(
				'special-set' . $this->entityName . '-set',
				array( $counts, $language )
			);

			//TODO: need conflict detection??
			$editEntity = new \Wikibase\EditEntity( $entityContent, $this->getUser(), false, $this->getContext() );
			$editEntity->attemptSave(
				AutoComment::formatTotalSummary( $comment, $summary, $lang ),
				EDIT_AUTOSUMMARY,
				$request->getVal( 'wpEditToken' )
			);

			if ( !$editEntity->isSuccess() ) {
				$editEntity->showErrorPage();
			}
			else {
				$entityUrl = $entityContent->getTitle()->getFullUrl();
				$this->getOutput()->redirect( $entityUrl );
			}
		} else {
			$this->setEntityForm( $entityContent, $language, $value );
		}
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
		$getValue = 'get' . ucfirst( $this->entityName );
		if ( $value === null ) {
			$value = getValue( $entityContent, $language );
		}

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getTitle()->getFullUrl(),
					'name' => 'set' . $this->entityName,
					'id' => 'wb-set' . $this->entityName . '-form1'
				)
			)
		);

		if ( ( $entityContent !== null ) && ( $language !== null ) ) {
			$this->getOutput()->addWikiMsg(
				'wikibase-set' . $this->entityName . '-introfull',
				$entityContent->getTitle()->getPrefixedText(),
				\Language::fetchLanguageName( $language, $this->getLanguage()->getCode() )
			);
			$this->getOutput()->addHTML(
				Html::input( 'language', $language, 'hidden' )
				. Html::input( 'id', $entityContent->getTitle()->getText(), 'hidden' )
			);
		} else {
			$id = $entityContent ? $entityContent->getTitle()->getText() : '';
			$language = $language ? $language : $this->getLanguage()->getCode();
			$this->getOutput()->addHTML(
				$this->msg( 'wikibase-set' . $this->entityName . '-intro' )->text()
				. Html::element( 'br' )
				. $this->msg( 'wikibase-set' . $this->entityName . '-id' )->text()
				. Html::element( 'br' )
				. Html::input(
					'id',
					$id,
					'text'
				)
				. Html::element( 'br' )
				. $this->msg( 'wikibase-set' . $this->entityName . '-language' )->text()
				. Html::element( 'br' )
				. Html::input(
					'language',
					$language,
					'text'
				)
				. Html::element( 'br' )
				. $this->msg( 'wikibase-set' . $this->entityName . '-' . $this->entityName )->text()
				. Html::element( 'br' )
			);
		}

		$this->getOutput()->addHTML(
			Html::input(
				$this->entityName,
				htmlspecialchars( $value ),
				'text',
				array( 'class' => 'wb-input-text wb-input-text-' . $this->entityName )
			)
			. Html::element( 'br' )
			. Html::input(
				'wikibase-set' . $this->entityName . '-submit',
				$this->msg( 'wikibase-set' . $this->entityName . '-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-set' . $this->entityName . '-submit',
					'class' => 'wb-input-button'
				)
			)
			. Html::input(
				'wpEditToken',
				$this->getUser()->getEditToken(),
				'hidden'
			)
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
	private function getValue( $entityContent, $language ) {
		switch( $this->entityName ) {
			case 'label':
				return $entityContent ? $entityContent->getEntity()->getLabel( $language ) : '';
			case 'description':
				return $entityContent ? $entityContent->getEntity()->getDescription( $language ) : '';
			case 'aliases':
				return $entityContent ? $entityContent->getEntity()->getAliases( $language ) : '';
		}
	}
	
	/**
	 * Setting the value of the entity name by the given language
	 * 
	 * @since 0.4
	 * 
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 * @param string $value
	 */
	private function setValue( $entityContent, $language, $value ) {
		switch( $this->entityName ) {
			case 'label':
				$entityContent->getEntity()->setLabel( $language, $value );
				break;
			case 'description':
				$entityContent->getEntity()->setDescription( $language, $value );
				break;
			case 'aliases':
				$entityContent->getEntity()->setAliases( $language, $value );
				break;
		}
	}
}