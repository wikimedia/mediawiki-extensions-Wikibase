<?php

/**
 * Page for listing assigned pages by requesting external pages and
 * comparing them to internal ones.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad <jeblad@gmail.com>
 */
class SpecialAssignedPages extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.3
	 */
	public function __construct() {
		parent::__construct( 'AssignedPages' );
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		// Setup
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );
		$siteId = $request->getVal( 'site', isset( $parts[0] ) ? $parts[0] : '' );
		$prefix = $request->getVal( 'prefix', isset( $parts[1] ) ? $parts[1] : '' );
		$limit = $request->getVal( 'limit', 50 );

		$this->getOutput()->addHTML( $this->msg( 'wikibase-assignedpages-intro' ) );

		$this->formHtml( $siteId, $prefix, $limit );

		$site = Sites::singleton()->getSite( $siteId );

		$args = array(
			'action' => 'query',
			'list' => 'allpages',
			'aplimit' => $limit,
			'apfilterredir' => 'nonredirects',
			'apfrom' => UtfNormal::cleanUp( $prefix ),
			'format' => 'json',
			//@todo: options for maxlag and maxage
		);

		$url = $site->getFileUrl( 'api.php' ) . '?' . wfArrayToCgi( $args );
		$ret = Http::get( $url );
		if ( $ret === false ) {
			$this->getOutput()->addWikiMsg( 'wikibase-assignedpages-nothing-found' );
			return false;
		}

		$data = FormatJson::decode( $ret, true );
		if ( !is_array( $data ) ) {
			$this->getOutput()->addWikiMsg( 'wikibase-assignedpages-bad-json' );
			return false;
		}

		$this->outerHtml( $site, $data );

	}

	protected function formHtml( $siteId, $prefix, $limit ) {
		//$site = Sites::singleton()->getSite( $siteId );
		$title = $this->getTitle();
		$steps = array( 20, 50, 100, 250, 500 );
		$links = array_map(
			function( $val ) use ( $title, $siteId, $prefix ) {
				return Html::element(
					'a',
					array(
						'href' => $title->getLocalUrl(
							array(
								'limit' => $val,
								'prefix' => htmlspecialchars( $prefix ),
								'site' => htmlspecialchars( $siteId )
							)
						)
					),
					$val
				);
			},
			$steps
		);
		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getTitle()->getFullUrl(),
					'name' => 'assignedpages',
					'id' => 'wb-assignedpages-form1'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element( 'hidden', array( 'name' => 'limit', 'value' => $limit ) )
			. Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-assignedpages-lookup-fieldset' )->text()
			)
			. Html::element(
				'label',
				array( 'for' => 'wb-assignedpages-sitename' ),
				$this->msg( 'wikibase-assignedpages-lookup-site' )->text()
			)
			. Html::input(
				'site',
				$siteId ? htmlspecialchars( $siteId ) : '',
				'text',
				array(
					'id' => 'wb-assignedpages-sitename',
					'size' => 12
				)
			)
			. ' '
			. Html::element(
				'label',
				array( 'for' => 'wb-assignedpages-prefix' ),
				$this->msg( 'wikibase-assignedpages-lookup-prefix' )->text()
			)
			. Html::input(
				'page',
				$prefix ? htmlspecialchars( $prefix ) : '',
				'text',
				array(
					'id' => 'wb-assignedpages-prefix',
					'size' => 36,
					'class' => 'wb-input-text'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-assignedpages-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-assignedpages-submit',
					'class' => 'wb-input-button'
				)
			)
			. implode('|', $links)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

	protected function outerHtml( $site, array $data ) {
		$handler = \Wikibase\ItemHandler::singleton();

		$this->getOutput()->addHTML( Html::openElement( 'ul' ));
		foreach ( $data['query']['allpages'] as $page ) {
			if ( isset( $page['title'] ) ) {
				$itemContent = $handler->getFromSiteLink( $site->getGlobalId(), $page['title'] );
				$this->getOutput()->addHTML(
					isset( $itemContent )
						? $this->itemHtml( $site, $itemContent )
						: $this->pageHtml( $site, $page )
				);
			}
		}
		$this->getOutput()->addHTML( Html::closeElement( 'ul' ));
	}

	protected function itemHtml( $site, $itemContent ) {
		//return 'item';
		$lang = $this->getLanguage()->getCode();
		$html = Html::openElement( 'li' )
			. Html::element(
				'a',
				array(
					'class' => 'wb-item-label',
					'style' => 'font-weight: bold'
				),
				$itemContent->getEntity()->getLabel( $lang )
			)
			. ' '
			. Html::element(
				'span',
				array(
					'class' => 'wb-item-description'
				),
				$itemContent->getEntity()->getDescription( $lang )
			)
			. Html::closeElement( 'li' );
		return $html;
	}

	protected function pageHtml( $site, $page ) {
		//return '<li>page</li>';
		$html = Html::openElement( 'li' )
			. '(create)'
			. ' '
			. Html::element(
				'a',
				array(
					'class' => 'wb-page-title',
					'href' => $site->getPageUrl( $page['title'] )
				),
				$page['title']
			)
			. Html::closeElement( 'li' );
		return $html;
	}
}
