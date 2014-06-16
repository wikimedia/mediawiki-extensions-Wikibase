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
	 * @param string $url specifies the URL for the button, default is an empty string
	 * @param Message $message the message to show on the link
	 * @param string $tag allows to specify the type of the outer node
	 * @param bool $enabled can be set to false to display the button disabled
	 *
	 * @return string
	 */
	public function getHtmlForEditSection( $url, Message $message, $tag = 'span', $enabled = true ) {
		wfProfileIn( __METHOD__ );

		$buttonLabel = $message->text();

		$button = ( $enabled ) ?
			wfTemplate( 'wikibase-toolbarbutton',
				$buttonLabel,
				$url // todo: add link to special page for non-JS editing
			) :
			wfTemplate( 'wikibase-toolbarbutton-disabled',
				$buttonLabel
			);

		$html = wfTemplate( 'wb-editsection',
			$tag,
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
	 * @since 0.5
	 *
	 * @param string $specialPageName The special page to link to
	 * @param Entity $entity The entity to edit
	 * @param Language $language The desired language of the special page
	 */
	public function getEditUrl( $specialPageName, Entity $entity, Language $language = null ) {
		$specialPage = SpecialPageFactory::getPage( $specialPageName );

		if ( $specialPage === null ) {
			return ''; //XXX: this should throw an exception?!
		}

		if ( $entity->getId() ) {
			$subPage = $entity->getId()->getPrefixedId();
		} else {
			$subPage = ''; // can't skip this, that would confuse the order of parameters!
		}

		if ( $language !== null ) {
			$subPage .= '/' . $language->getCode();
		}
		return $specialPage->getPageTitle( $subPage )->getLocalURL();
	}

}
