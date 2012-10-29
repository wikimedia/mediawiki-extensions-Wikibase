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
 * @return array templates
 */

return call_user_func( function() {
	$templates = array();

	$templates['wb-editsection'] =
<<<HTML
<$1 class="editsection">$2</$1>
HTML;

	$templates['wb-toolbar'] =
<<<HTML
<span class="wb-ui-toolbar">$1</span>
HTML;

	$templates['wb-toolbar-group'] =
<<<HTML
<span class="wb-ui-toolbar-group">[$1]</span>
HTML;

	$templates['wb-toolbar-button'] =
<<<HTML
<a href="$2" class="wb-ui-toolbar-button">$1</a>
HTML;

	$templates['wb-toolbar-button-disabled'] =
<<<HTML
<span class="wb-ui-toolbar-button wb-ui-toolbar-button-disabled">$1</span>
HTML;

	$templates['wb-sitelinks-heading'] =
<<<HTML
<h2 class="wb-sitelinks-heading">$1</h2>
HTML;

	$templates['wb-sitelinks-table'] =
<<<HTML
<table class="wb-sitelinks">
	<colgroup>
		<col class="wb-sitelinks-sitename" />
		<col class="wb-sitelinks-siteid" />
		<col class="wb-sitelinks-link" />
		<col class="editsection" />
	</colgroup>
	<thead>$1</thead>
	<tbody>$2</tbody>
	<tfoot>$3</tfoot>
</table>
HTML;

	$templates['wb-sitelinks-thead'] =
<<<HTML
<tr class="wb-sitelinks-columnheaders">
	<th class="wb-sitelinks-sitename">$1</th>
	<th class="wb-sitelinks-siteid">$2</th>
	<th class="wb-sitelinks-link">$3</th>
	<th class="unsortable"></th>
</tr>
HTML;

	$templates['wb-sitelinks-tfoot'] =
<<<HTML
<tr>
	<td colspan="3" class="wb-sitelinks-placeholder">$1</td>
	$2
</tr>
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

	$templates['wb-sitelink-unknown'] =
<<<HTML
<tr class="wb-sitelinks-site-unknown $1">
	<td class="wb-sitelinks-sitename wb-sitelinks-sitename-unknown">$2</td>
	<td class="wb-sitelinks-link wb-sitelinks-link-unknown">$3</td>
	$4
</tr>
HTML;

	$templates['wb-sitelink-new'] =
<<<HTML
<tr>
	<td colspan="2" class="wb-sitelinks-sitename"></td>
	<td class="wb-sitelinks-link"></td>
	<td></td><!-- cell for toolbar -->
</tr>
HTML;

	return $templates;
} );
