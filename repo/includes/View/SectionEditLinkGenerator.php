<?php

namespace Wikibase\Repo\View;

use Message;
use SpecialPageFactory;

/**
 * Generates HTML for a section edit link
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 */
class SectionEditLinkGenerator {

	/**
	 * Returns a toolbar with an edit link. In JavaScript, an enhanced toolbar will be initialized
	 * on top of the generated HTML.
	 *
	 * @since 0.2
	 *
	 * @param string|null $specialPageName the special page for the button
	 * @param string[] $specialPageUrlParams Additional URL params for the special page
	 * @param string $cssClassSuffix Suffix of the css class applied to the toolbar button node
	 * @param Message $message the message to show on the link
	 * @param bool $enabled can be set to false to display the button disabled
	 *
	 * @return string
	 */
	public function getHtmlForEditSection(
		$specialPageName,
		array $specialPageUrlParams,
		$cssClassSuffix,
		Message $message,
		$enabled = true
	) {
		wfProfileIn( __METHOD__ );

		$editUrl = $enabled ? $this->getEditUrl( $specialPageName, $specialPageUrlParams ) : null;
		$toolbarButton = $this->getToolbarButton( $cssClassSuffix, $message->text(), $editUrl );

		$html = wfTemplate( 'wikibase-toolbar-container',
			wfTemplate( 'wikibase-toolbar',
				'',
				wfTemplate( 'wikibase-toolbar-bracketed',
					$toolbarButton
				)
			)
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Returns a toolbar with an "add" link. In JavaScript, an enhanced toolbar will be initialized
	 * on top of the generated HTML.
	 *
	 * @since 0.5
	 *
	 * @param string|null $specialPageName the special page for the button
	 * @param string[] $specialPageUrlParams Additional URL params for the special page
	 * @param string $cssClassSuffix Suffix of the css class applied to the toolbar button node
	 * @param Message $message the message to show on the link
	 * @param bool $enabled can be set to false to display the button disabled
	 *
	 * @return string
	 */
	public function getSingleButtonToolbarHtml(
		$specialPageName,
		array $specialPageUrlParams,
		$cssClassSuffix,
		Message $message,
		$enabled = true
	) {
		wfProfileIn( __METHOD__ );

		$editUrl = $enabled ? $this->getEditUrl( $specialPageName, $specialPageUrlParams ) : null;
		$toolbarButton = $this->getToolbarButton( $cssClassSuffix, $message->text(), $editUrl );

		$html = wfTemplate( 'wikibase-toolbar-container',
			$toolbarButton
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Get the Url to an edit special page
	 *
	 * @param string|null $specialPageName The special page to link to
	 * @param string[] $specialPageUrlParams Additional URL params for the special page
	 *
	 * @return string
	 */
	private function getEditUrl( $specialPageName, array $specialPageUrlParams ) {
		if ( $specialPageName !== null && !empty( $specialPageUrlParams ) ) {
			$specialPage = SpecialPageFactory::getPage( $specialPageName );

			if ( $specialPage !== null ) {
				$subPage = implode( '/', array_map( 'wfUrlencode', $specialPageUrlParams ) );
				return $specialPage->getPageTitle( $subPage )->getLocalURL();
			}
		}

		return null;
	}

	/**
	 * @param string $cssClassSuffix
	 * @param string $buttonLabel the message to show on the toolbar button link
	 * @param string|null $editUrl The edit url
	 *
	 * @return string
	 */
	private function getToolbarButton( $cssClassSuffix, $buttonLabel, $editUrl = null ) {
		if ( $editUrl !== null ) {
			return wfTemplate( 'wikibase-toolbar-button',
				'wikibase-toolbar-button-' . $cssClassSuffix,
				$editUrl,
				$buttonLabel
			);
		} else {
			return wfTemplate( 'wikibase-toolbar-button',
				'wikibase-toolbar-button-' . $cssClassSuffix . ' ui-state-disabled',
				'#',
				$buttonLabel
			);
		}
	}

}
