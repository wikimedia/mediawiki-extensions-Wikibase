<?php

namespace Wikibase;

/**
 * Contains templates commonly used in server-side output generation and client-side JavaScript
 * processing.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 *
 * @return array HTML templates
 */

return call_user_func( function() {
	$templates = array();

	$templates['wb-editsection'] =
<<<HTML
<$1 class="editsection">
	<span class="wb-ui-toolbar">
		<span class="wb-ui-toolbar-group">[$2]</span>
	</span>
</$1>
HTML;

	$templates['wb-editsection-button'] =
<<<HTML
<a href="$2" class="wb-ui-toolbar-button">$1</a>
HTML;

	$templates['wb-editsection-button-disabled'] =
<<<HTML
<span class="wb-ui-toolbar-button wb-ui-toolbar-button-disabled">$1</span>
HTML;

	$templates['wb-sitelink'] =
<<<HTML
<tr class="wb-sitelinks-$1 $2">
	<td class="wb-sitelinks-sitename wb-sitelinks-sitename-$1">$3</td>
	<td class="wb-sitelinks-siteid wb-sitelinks-siteid-$1">$4</td>
	<td class="wb-sitelinks-link wb-sitelinks-link-$1">
		<a href="$5" dir="auto">$6</a>
	</td>
	$7
</tr>
HTML;

	return $templates;
} );
