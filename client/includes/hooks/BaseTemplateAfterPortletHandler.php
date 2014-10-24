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
	 * @param BaseTemplate $baseTemplate
	 * @param string $name
	 *
	 * @return string|null
	 */
	public function getEditLink( BaseTemplate $baseTemplate, $name ) {
		if ( $name === 'lang' ) {
			$link = $baseTemplate->get( 'wbeditlanglinks' );
			return $link;
		}

		return null;
	}

}
