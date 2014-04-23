<?php

namespace Wikibase\View;

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

	public function getEditUrl( $specialpagename, Entity $entity, Language $language = null ) {
		$specialpage = SpecialPageFactory::getPage( $specialpagename );

		if ( $specialpage === null ) {
			return ''; //XXX: this should throw an exception?!
		}

		if ( $entity->getId() ) {
			$subpage = $entity->getId()->getPrefixedId();
		} else {
			$subpage = ''; // can't skip this, that would confuse the order of parameters!
		}

		if ( $language !== null ) {
			$subpage .= '/' . $language->getCode();
		}
		return $specialpage->getPageTitle( $subpage )->getLocalURL();
	}

}
