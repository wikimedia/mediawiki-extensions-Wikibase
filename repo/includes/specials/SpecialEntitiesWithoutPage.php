<?php

/**
 * Base page for pages listing entities without a specific value.
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
 * @author Thomas Pellissier Tanon
 * @author Bene*
 */
abstract class SpecialEntitiesWithoutPage extends SpecialWikibaseQueryPage {

	/**
	 * The language used
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language = '';

	/**
	 * The type used
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $type = null;

	/**
	 * Map entity types to objects representing the corresponding entity
	 *
	 * @since 0.4
	 *
	 * @var array
	 */
	protected $possibleTypes;

	/**
	 * @see SpecialWikibasePage::execute
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

		// 10 seconds server-side caching max
		$this->getOutput()->setSquidMaxage( 10 );

		$this->prepareArguments( $subPage );

		$this->setForm();

		if ( $this->language !== '' ) {
			$this->showQuery();
		}
	}

	/**
	 * Prepare the arguments
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	private function prepareArguments( $subPage ) {
		$request = $this->getRequest();
		$output = $this->getOutput();

		$this->language = '';
		$this->type = null;
		if ( $subPage !== null ) {
			$parts = explode( '/', $subPage );
			if ( isset( $parts[1] ) ) {
				$this->type = $parts[1];
			}
			$this->language = $parts[0];
		}

		$this->language = $request->getText( 'language', $this->language );
		if ( $this->language !== '' && !in_array( $this->language, \Wikibase\Utils::getLanguageCodes() ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-entitieswithoutlabel-invalid-language', $this->language )->parse() );
			$this->language = '';
		}

		$this->type = $request->getText( 'type', $this->type );
		$this->possibleTypes = \Wikibase\EntityFactory::singleton()->getEntityTypes();
		if ( $this->type === '' ) {
			$this->type = null;
		}
		if ( $this->type !== null && !in_array( $this->type, $this->possibleTypes ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-entitieswithoutlabel-invalid-type', $this->type )->parse() );
			$this->type = null;
		}
	}

	/**
	 * Show an error
	 *
	 * @since 0.4
	 *
	 * @param string $error The error message in HTML format
	 * @param string $class The element's class, default 'error'
	 */
	private function showErrorHTML( $error, $class = 'error' ) {
		$this->getOutput()->addHTML(
			Html::rawElement(
				'p',
				array( 'class' => $class ),
				$error
			)
		);
	}

	/**
	 * Build the HTML form
	 *
	 * @since 0.4
	 */
	private function setForm() {
		$typeSelect = new XmlSelect( 'type', 'wb-entitieswithoutpage-type', $this->type );
		$typeSelect->addOption( $this->msg( 'wikibase-entitieswithoutlabel-label-alltypes' )->text(), '' );
		// item, property and query
		foreach( $this->possibleTypes as $type ) {
			$typeSelect->addOption( $this->msg( 'wikibase-entity-' . $type )->text(), $type );
		}

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'action' => $this->getTitle()->getLocalURL(),
					'name' => 'entitieswithoutpage',
					'id' => 'wb-entitieswithoutpage-form'
				)
			) .
			Html::openElement( 'fieldset' ) .
			Html::element(
				'legend',
				array(),
				$this->getLegend()
			) .
			Html::openElement( 'p' ) .
			Html::element(
				'label',
				array(
					'for' => 'wb-entitieswithoutpage-language'
				),
				$this->msg( 'wikibase-entitieswithoutlabel-label-language' )->text()
			) . ' ' .
			Html::input(
				'language',
				$this->language,
				'text',
				array(
					'id' => 'wb-entitieswithoutpage-language'
				)
			) . ' ' .
			Html::element(
				'label',
				array(
					'for' => 'wb-entitieswithoutpage-type'
				),
				$this->msg( 'wikibase-entitieswithoutlabel-label-type' )->text()
			) . ' ' .
			$typeSelect->getHTML() . ' ' .
			Html::input(
				'submit',
				$this->msg( 'wikibase-entitieswithoutlabel-submit' )->text(),
				'submit',
				array(
					'id' => 'wikibase-entitieswithoutpage-submit',
					'class' => 'wb-input-button'
				)
			) .
			Html::closeElement( 'p' ) .
			Html::closeElement( 'fieldset' ) .
			Html::closeElement( 'form' )
		);
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
	 * @since 0.4
	 *
	 * @param integer $offset
	 * @param integer $limit
	 *
	 * @return EntityId[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$entityPerPage = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		return $entityPerPage->getEntitiesWithoutTerm( $this->getTerm(), $this->language, $this->type, $limit, $offset );
	}


	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 *
	 * @since 0.4
	 *
	 * @return Title
	 */
	protected function getTitleForNavigation() {
		return $this->getTitle( $this->language . '/' . $this->type );
	}

	/**
	 * Get the term (member of Term::TYPE_ enum)
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected abstract function getTerm();

	/**
	 * Get the legend
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected abstract function getLegend();

}
