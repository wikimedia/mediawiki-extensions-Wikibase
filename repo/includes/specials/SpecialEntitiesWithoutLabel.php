<?php

use Wikibase\EntityId;

/**
 * Page for listing entities without label.
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
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SpecialEntitiesWithoutLabel extends SpecialWikibaseQueryPage {

	/**
	 * The language used
	 *
	 * @since 0.2
	 *
	 * @var string
	 */
	protected $language = '';

	public function __construct() {
		parent::__construct( 'EntitiesWithoutLabel' );

	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.2
	 *
	 * @param string $subPage
	 * @return boolean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$output = $this->getOutput();
		$request = $this->getRequest();

		$this->language = $request->getText( 'language', '' );
		if ( $this->language === '' && $subPage !== null ) {
			$this->language = $subPage;
		}
		if ( $this->language !== '' && !in_array( $this->language, \Wikibase\Utils::getLanguageCodes() ) ) {
			$output->addWikiMsg( 'wikibase-entitieswithoutlabel-invalid-language', $this->language );
			$this->language = '';
		}

		$output->addHTML(
			Html::openElement(
				'form',
				array(
					'action' => $this->getTitle()->getLocalURL(),
					'name' => 'entitieswithoutlabel',
					'id' => 'wb-entitieswithoutlabel-form'
				)
			) .
			Html::openElement( 'fieldset' ) .
			Html::element( 'legend', array(), $this->msg( 'wikibase-entitieswithoutlabel-legend' )->text() ) .
			Html::openElement( 'p' ) .
			Html::element( 'label', array( 'for' => 'language' ), $this->msg( 'wikibase-entitieswithoutlabel-label-language' )->text() )  . ' ' .
			Html::input(
				'language',
				$this->language,
				'text',
					array(
						'id' => 'language'
				)
			) .
			Html::input(
				'submit',
				$this->msg( 'wikibase-entitieswithoutlabel-submit' )->text(),
				'submit',
				array(
					'id' => 'wikibase-entitieswithoutlabel-submit',
					'class' => 'wb-input-button'
				)
			) .
			Html::closeElement( 'p' ) .
			Html::closeElement( 'fieldset' ) .
			Html::closeElement( 'form' )
		);

		if ( $this->language !== '' ) {
			$this->showQuery();
		}
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.2
	 *
	 * @param integer $offset
	 * @param integer $limit
	 *
	 * @return EntityId[]
	 * TODO: it's a bit odd that this returns an array of EntityId
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$entityPerPage = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		return $entityPerPage->getEntitiesWithoutTerm( \Wikibase\Term::TYPE_LABEL, $this->language, null, $offset, $limit );
	}


	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 *
	 * @since 0.2
	 *
	 * @return Title
	 */
	protected function getTitleForNavigation() {
		return $this->getTitle( $this->language );
	}

}
