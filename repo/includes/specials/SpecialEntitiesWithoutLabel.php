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

		# 10 seconds server-side caching max
		$this->getOutput()->setSquidMaxage( 10 );

		$output = $this->getOutput();
		$request = $this->getRequest();

		$this->language = '';
		$this->type = null;
		if ( $subPage !== null ) {
			$parts = explode( '/', $subPage );
			if ( array_key_exists( 1, $parts ) ) {
				$this->type = $parts[1];
			}
			$this->language = $parts[0];
		}

		$this->language = $request->getText( 'language', $this->language );
		if ( $this->language !== '' && !in_array( $this->language, \Wikibase\Utils::getLanguageCodes() ) ) {
			$output->addWikiMsg( 'wikibase-entitieswithoutlabel-invalid-language', $this->language );
			$this->language = '';
		}

		$this->type = $request->getText( 'type', $this->type );
		$possibleTypes = array( 'item', 'property', 'query' );
		if ( $this->type === '' ) {
			$this->type = null;
		}
		if ( $this->type !== null && !in_array( $this->type, $possibleTypes ) ) {
			$output->addWikiMsg( 'wikibase-entitieswithoutlabel-invalid-type', $this->type );
			$this->type = null;
		}
		$typeSelect = new XmlSelect( 'type', 'wb-entitieswithoutlabel-type', $this->type );
		$typeSelect->addOption( $this->msg( 'wikibase-entitieswithoutlabel-label-alltypes' )->text(), '' );
		// Give grep a chance to find the usages:
		// wikibase-entity-item, wikibase-entity-property, wikibase-entity-query
		foreach( $possibleTypes as $possibleType ) {
			$typeSelect->addOption( $this->msg( 'wikibase-entity-' . $possibleType )->text(), $possibleType );
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
			Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-entitieswithoutlabel-legend' )->text()
			) .
			Html::openElement( 'p' ) .
			Html::element(
				'label',
				array(
					'for' => 'wb-entitieswithoutlabel-language'
				),
				$this->msg( 'wikibase-entitieswithoutlabel-label-language' )->text()
			) . ' ' .
			Html::input(
				'language',
				$this->language,
				'text',
				array(
					'id' => 'wb-entitieswithoutlabel-language'
				)
			) . ' ' .
			Html::element(
				'label',
				array(
					'for' => 'wb-entitieswithoutlabel-type'
				),
				$this->msg( 'wikibase-entitieswithoutlabel-label-type' )->text()
			) . ' ' .
			$typeSelect->getHTML() . ' ' .
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
	 * @see SpecialWikibaseQueryPage::formatRow
	 *
	 * @since 0.3
	 *
	 * @param $entry The entry is for this call an EntityId
	 *
	 * @return string|null
	 */
	protected function formatRow( $entry ) {
		try {
			$title = \Wikibase\EntityContentFactory::singleton()->getTitleForId( $entry );
			return Linker::linkKnown( $title );
		} catch ( MWException $e ) {
			wfWarn( "Error formatting result row: " . $e->getMessage() );
			return false;
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
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$entityPerPage = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		return $entityPerPage->getEntitiesWithoutTerm( \Wikibase\Term::TYPE_LABEL, $this->language, $this->type, $limit, $offset );
	}


	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 *
	 * @since 0.2
	 *
	 * @return Title
	 */
	protected function getTitleForNavigation() {
		return $this->getTitle( $this->language . '/' . $this->type );
	}

}
