<?php

namespace Wikibase\Client\Hooks;

use BaseTemplate;

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
	public function handle( BaseTemplate $baseTemplate, $name ) {
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
		$html = '<span class="wb-langlinks-' . $link['action']  . ' wb-langlinks-link"><a';

		unset( $link['action'] );

		foreach( $link as $key => $value ) {
			if ( $key !== 'text' ) {
				$html .= " $key='$value'";
			}
		}

		$html .= '>' . $link['text'] . '</a></span>';

		return $html;
	}

}

