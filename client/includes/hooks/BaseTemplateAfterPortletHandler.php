<?php

namespace Wikibase\Client\Hooks;

use BaseTemplate;
use Html;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BaseTemplateAfterPortletHandler {

	/**
	 * @param BaseTemplate $skinTemplate
	 * @param string $name
	 *
	 * @return string|null
	 */
	public function makeEditLink( BaseTemplate $baseTemplate, $name ) {
		if ( $name === 'lang' ) {
			$link = $baseTemplate->get( 'wbeditlanglinks' );

			if ( $link ) {
				return $this->formatLink( $link );
			}
		}

		return null;
	}

	/**
	 * @param array $link
	 *
	 * @return string
	 */
	private function formatLink( array $link ) {
		$action = $link['action'];
		$link = Html::element( 'a', $link, $link['text'] );

		$html = Html::rawElement(
			'span',
			array(
				'class' => "wb-langlinks-$action wb-langlinks-link"
			),
			$link
		);

		return $html;
	}

}
