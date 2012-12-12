<?php

namespace Wikibase;

/**
 * Contains templates commonly used in server-side output generation and client-side JavaScript
 * processing.
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 *
 * @return array templates
 */

return call_user_func( function() {
	$templates = array();

	$templates['wb-entity'] =
// container reserved for widgets, will be displayed on the right side if there is space
// TODO: no point in inserting this here, is there? Should be generated in JS!
<<<HTML
<div id="wb-$1-$2" class="wb-entity wb-$1" lang="$3" dir="$4">$5</div>
<div id="wb-widget-container-$2" class="wb-widget-container"></div>
HTML;


	$templates['wb-entity-content'] =
<<<HTML
$1
$2
<hr class="wb-hr" />
$3
$4
HTML;

	$templates['wb-section-heading'] =
<<<HTML
<h2 class="wb-section-heading" dir="auto">$1</h2>
HTML;

	$templates['wb-claims-section'] =
<<<HTML
<div class="wb-claims-section">
	<div class="wb-claims">
		$1 <!-- [0,*] wb-claim-section -->
	</div>
	<div class="wb-claims-toolbar">$2</div> <!-- edit section -->
</div>
HTML;

	$templates['wb-claim-section'] =
<<<HTML
<div class="wb-claim-section wb-claim-section-$1">
	<div class="wb-claim-section-name">
		<div class="wb-claim-name" dir="auto">$2</div>
	</div>
	$3 <!-- [1,*] wb-claim -->
</div>
HTML;

	$templates['wb-claim'] =
<<<HTML
<div class="wb-claim-container $1">
	<div class="wb-claim wb-claim-$2">
		<div class="wb-claim-mainsnak" dir="auto">
			$3 <!-- wb-snak (Main Snak) -->
		</div>
	</div>
	<div class="wb-claim-toolbar">$4</div> <!-- edit section -->
</div>
HTML;

	$templates['wb-snak'] =
// This template is not only used for PropertyValueSnak Snaks but also for other Snaks without a
// value which may display some message in the value node.
<<<HTML
<div class="wb-snak $1">
	<div class="wb-snak-property-container">
		<div class="wb-snak-property" dir="auto">$2</div>
	</div>
	<div class="wb-snak-value" dir="auto">$3</div>
</div>
HTML;

	$templates['wb-label'] =
// add an h1 for displaying the entity's label; the actual firstHeading is being hidden by
// css since the original MediaWiki DOM does not represent a Wikidata entity's structure
// where the combination of label and description is the unique "title" of an entity which
// should not be semantically disconnected by having elements in between, like siteSub,
// contentSub and jump-to-nav
<<<HTML
<h1 id="wb-firstHeading-$1" class="wb-firstHeading wb-value-row">$2</h1>
HTML;

	$templates['wb-description'] =
<<<HTML
<div class="wb-property-container wb-value-row wb-description" dir="auto">
	<div class="wb-property-container-key" title="description"></div>
	$1
</div>
HTML;

	$templates['wb-property'] =
<<<HTML
<span class="wb-property-container-value wb-value-container" dir="auto">
	<span class="wb-value $1">$2</span>
	$3
</span>
HTML;

	$templates['wb-aliases-wrapper'] =
<<<HTML
<div class="wb-aliases $1">
	<div class="wb-gridhelper">
		<span class="wb-aliases-label $2">$3</span>
		$4
	</div>
</div>
HTML;

	$templates['wb-aliases'] =
<<<HTML
<ul class="wb-aliases-container">$1</ul>
HTML;

	$templates['wb-alias'] =
<<<HTML
<li class="wb-aliases-alias">$1</li>
HTML;

	$templates['wb-editsection'] =
<<<HTML
<$1 class="wb-editsection">$2</$1>
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

	$templates['wb-sitelinks-table'] =
<<<HTML
<table class="wb-sitelinks">
	<colgroup>
		<col class="wb-sitelinks-sitename" />
		<col class="wb-sitelinks-siteid" />
		<col class="wb-sitelinks-link" />
		<col class="wb-editsection" />
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
	<td class="wb-sitelinks-sitename wb-sitelinks-sitename-$1" lang="$1" dir="auto">$3</td>
	<td class="wb-sitelinks-siteid wb-sitelinks-siteid-$1">$4</td>
	<td class="wb-sitelinks-link wb-sitelinks-link-$1" lang="$1">
		<a href="$5" hreflang="$1" dir="auto">$6</a>
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

	$templates['wb-property-datatype'] =
<<<HTML
<div class="wb-datatype wb-value-row">
	<span class="wb-datatype-label">$1</span>
	<span class="wb-value">$2</span>
</div>
HTML;

	return $templates;
} );
