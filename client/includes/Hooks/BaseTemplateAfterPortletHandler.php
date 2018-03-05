<?php

namespace Wikibase\Client\Hooks;

use BaseTemplate;

/**
 * @license GPL-2.0-or-later
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
