<?php

use Wikibase\Repo\WikibaseRepo;
use Wikibase\Template;

/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

/**
 * Shorthand function to retrieve a template filled with the specified parameters.
 *
 * important! note that the Template class does not escape anything.
 * be sure to escape your params before using this function!
 *
 * @since 0.2
 *
 * @param $key string template key
 * Varargs: normal template parameters
 *
 * @return string
 */
function wfTemplate( $key /*...*/ ) {
	$params = func_get_args();
	array_shift( $params );

	if ( isset( $params[0] ) && is_array( $params[0] ) ) {
		$params = $params[0];
	}

	$template = new Template( WikibaseRepo::getDefaultInstance()->getTemplateRegistry(), $key, $params );

	return $template->render();
}
