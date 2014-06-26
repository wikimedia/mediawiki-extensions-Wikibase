<?php

namespace Wikibase\Repo\View;

use Language;
use Message;
use SpecialPageFactory;
use Wikibase\Entity;

/**
 * Generates HTML for a section edit link
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author Henning Snater
 * @author Daniel Werner
 * @author Daniel Kinzler
 */
class SectionEditLinkGenerator {

	/**
	 * Returns a toolbar with an edit link for a single statement. Equivalent to edit toolbar in JavaScript but with
	 * an edit link pointing to a special page where the statement can be edited. In case JavaScript is available, this
	 * toolbar will be removed an replaced with the interactive JavaScript one.
	 *
	 * @since 0.2
	 *
	 * @param string $specialPageName the special page for the button
	 * @param string[] $specialPageUrlParams Additional URL params for the special page
	 * @param Message $message the message to show on the link
	 * @param bool $enabled can be set to false to display the button disabled
	 *
	 * @return string
	 */
	public function getHtmlForEditSection(
		$specialPageName,
		array $specialPageUrlParams,
		Message $message,
		$enabled = true
	) {
		wfProfileIn( __METHOD__ );

		$editUrl = $this->getEditUrl( $specialPageName, $specialPageUrlParams );
		$button = $this->getEditLink( $editUrl, $message, $enabled );

		$html = wfTemplate( 'wb-editsection',
			'span',
			wfTemplate( 'wikibase-toolbar',
				'',
				wfTemplate( 'wikibase-toolbareditgroup',
					'',
					wfTemplate( 'wikibase-toolbar', '', $button )
				)
			)
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Get the Url to an edit special page
	 *
	 * @param string $specialPageName The special page to link to
	 * @param string[] $specialPageUrlParams Additional URL params for the special page
	 */
	private function getEditUrl( $specialPageName, array $specialPageUrlParams ) {
		$specialPage = SpecialPageFactory::getPage( $specialPageName );

		if ( $specialPage === null ) {
			return ''; //XXX: this should throw an exception?!
		}

		$subPage = implode( '/', array_map( 'wfUrlencode', $specialPageUrlParams ) );
		return $specialPage->getPageTitle( $subPage )->getLocalURL();
	}

	/**
	 * @param string $editUrl The edit url
	 * @param Message $message the message to show on the link
	 * @param bool $enabled can be set to false to display the button disabled
	 *
	 * @return string
	 */
	private function getEditLink( $editUrl, Message $message, $enabled = true ) {
		$buttonLabel = $message->text();

		$button = ( $enabled ) ?
			wfTemplate( 'wikibase-toolbarbutton',
				$buttonLabel,
				$editUrl // todo: add link to special page for non-JS editing
			) :
			wfTemplate( 'wikibase-toolbarbutton-disabled',
				$buttonLabel
			);

		return $button;
	}
}
