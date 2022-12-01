<?php

namespace Wikibase;

/**
 * Contains templates commonly used in server-side output generation and client-side JavaScript
 * processing.
 *
 * @license GPL-2.0-or-later
 * @author H. Snater <mediawiki@snater.com>
 *
 * @return array templates
 */

return call_user_func( function() {
	$templates = [];

	$templates['wikibase-entityview'] =
<<<HTML
<div id="wb-$1-$2" class="wikibase-entityview wb-$1" lang="$3" dir="$4">
	<div class="wikibase-entityview-main">$5</div>
	<div class="wikibase-entityview-side">$6</div>
</div>
HTML;

	$templates['wb-entity-header-separator'] =
<<<HTML
<hr class="wb-hr" />
HTML;

	$templates['wikibase-title'] =
<<<HTML
<span class="wikibase-title $1">
	<span class="wikibase-title-label">$2</span>
	<span class="wikibase-title-id">$3</span>
</span>
HTML;

	$templates['wb-section-heading'] =
<<<HTML
<h2 class="wb-section-heading section-heading $3" dir="auto" id="$2">$1</h2>
HTML;

	// empty toc to help MobileFrontend
	$templates['wikibase-toc'] =
<<<HTML
<div id="toc"></div>
HTML;

	$templates['wikibase-statementgrouplistview'] =
<<<HTML
<div class="wikibase-statementgrouplistview"><!-- wikibase-listview -->$1</div>
HTML;

	$templates['wikibase-statementgroupview'] =
<<<HTML
<div class="wikibase-statementgroupview" id="$3" data-property-id="$4">
	<div class="wikibase-statementgroupview-property">
		<div class="wikibase-statementgroupview-property-label" dir="auto">$1</div>
	</div>
	<!-- wikibase-statementlistview -->$2
</div>
HTML;

	$templates['wikibase-statementlistview'] =
<<<HTML
<div class="wikibase-statementlistview">
	<div class="wikibase-statementlistview-listview">
		<!-- [0,*] wikibase-statementview -->$1
	</div>
	<!-- [0,1] wikibase-toolbar -->$2
</div>
HTML;

	$templates['wikibase-snakview'] =
<<<HTML
<div class="wikibase-snakview wikibase-snakview-$4">
	<div class="wikibase-snakview-property-container">
		<div class="wikibase-snakview-property" dir="auto">$1</div>
	</div>
	<div class="wikibase-snakview-value-container" dir="auto">
		<div class="wikibase-snakview-typeselector"></div>
		<div class="wikibase-snakview-body">
			<div class="wikibase-snakview-value $2">$3</div>
			<div class="wikibase-snakview-indicators"></div>
		</div>
	</div>
</div>
HTML;

	$templates['wikibase-statementview'] =
<<<HTML
<div id="$1" class="wikibase-statementview wikibase-statement-$1 wb-$2">
	<div class="wikibase-statementview-rankselector">$3</div>
	<div class="wikibase-statementview-mainsnak-container">
		<div class="wikibase-statementview-mainsnak" dir="auto"><!-- wikibase-snakview -->$4</div>
		<div class="wikibase-statementview-qualifiers"><!-- wikibase-listview -->$5</div>
	</div>
	<!-- wikibase-toolbar -->$6
	<div class="wikibase-statementview-references-container">
		<div class="wikibase-statementview-references-heading">$7</div>
		<div class="wikibase-statementview-references $9"><!-- wikibase-listview -->$8</div>
	</div>
</div>
HTML;

	$templates['wikibase-rankselector'] =
<<<HTML
<div class="wikibase-rankselector $1">
	<span class="ui-icon ui-icon-rankselector $2" title="$3"></span>
</div>
HTML;

	$templates['wikibase-referenceview'] =
<<<HTML
<div class="wikibase-referenceview $1">
	<div class="wikibase-referenceview-heading"></div>
	<div class="wikibase-referenceview-listview">$2<!-- [0,*] wikibase-snaklistview --></div>
</div>
HTML;

	$templates['wikibase-listview'] =
<<<HTML
<div class="wikibase-listview">$1</div>
HTML;

	$templates['wikibase-snaklistview'] =
<<<HTML
<div class="wikibase-snaklistview">
	<div class="wikibase-snaklistview-listview"><!-- wikibase-listview -->$1</div>
</div>
HTML;

	// T308991: Avoid newlines and other whitespace characters between the elements
	// because they get copied into the clipboard on a selection by a triple click.
	$templates['wikibase-labelview'] =
<<<HTML
<div class="wikibase-labelview $1" dir="$4" lang="$5"><div class="wikibase-labelview-container"><span class="wikibase-labelview-text">$2</span><!-- wikibase-toolbar -->$3</div></div>
HTML;

	// T308991: Avoid newlines and other whitespace characters between the elements
	// because they get copied into the clipboard on a selection by a triple click.
	$templates['wikibase-descriptionview'] =
<<<HTML
<div class="wikibase-descriptionview $1" dir="$4" lang="$5"><div class="wikibase-descriptionview-container"><span class="wikibase-descriptionview-text">$2</span><!-- wikibase-toolbar -->$3</div></div>
HTML;

	$templates['wikibase-aliasesview'] =
<<<HTML
<div class="wikibase-aliasesview $1">
	<ul class="wikibase-aliasesview-list" dir="$4" lang="$5"><!-- [0,*] wikibase-aliasesview-list-item -->$2</ul>
	<!-- wikibase-toolbar -->$3
</div>
HTML;

	$templates['wikibase-aliasesview-list-item'] =
<<<HTML
<li class="wikibase-aliasesview-list-item">$1</li>
HTML;

	$templates['wikibase-entitytermsview'] =
<<<HTML
<div class="wikibase-entitytermsview">
	<!-- wikibase-entitytermsview-heading -->$1
	<!-- ? wikibase-toolbar -->$4
	<div class="wikibase-entitytermsview-entitytermsforlanguagelistview $3"><!-- wikibase-entitytermsforlanguagelistview -->$2</div>
</div>
HTML;

	$templates['wikibase-entitytermsview-heading'] =
<<<HTML
<div class="wikibase-entitytermsview-heading">
	<!-- [0,*] wikibase-entitytermsview-heading-part -->$1
</div>
HTML;

	$templates['wikibase-entitytermsview-heading-part'] =
<<<HTML
		<div class="wikibase-entitytermsview-heading-$1 $2">$3</div>
HTML;

	$templates['wikibase-entitytermsview-aliases'] =
<<<HTML
<ul class="wikibase-entitytermsview-aliases"><!-- wikibase-entitytermsview-aliases-alias -->$1</ul>
HTML;

	$templates['wikibase-entitytermsview-aliases-alias'] =
<<<HTML
<li class="wikibase-entitytermsview-aliases-alias" data-aliases-separator="$2">$1</li>
HTML;

	$templates['wikibase-entitytermsforlanguagelistview'] =
<<<HTML
<table class="wikibase-entitytermsforlanguagelistview">
	<thead class="wikibase-entitytermsforlanguagelistview-header">
		<tr class="wikibase-entitytermsforlanguagelistview-header-row">
			<th scope="col" class="wikibase-entitytermsforlanguagelistview-cell wikibase-entitytermsforlanguagelistview-language">$1</th>
			<th scope="col" class="wikibase-entitytermsforlanguagelistview-cell wikibase-entitytermsforlanguagelistview-label">$2</th>
			<th scope="col" class="wikibase-entitytermsforlanguagelistview-cell wikibase-entitytermsforlanguagelistview-description">$3</th>
			<th scope="col" class="wikibase-entitytermsforlanguagelistview-cell wikibase-entitytermsforlanguagelistview-aliases">$4</th>
		</tr>
	</thead>
	<tbody class="wikibase-entitytermsforlanguagelistview-listview"><!-- [0,*] wikibase-entitytermsforlanguageview -->$5</tbody>
</table>
HTML;

	$templates['wikibase-entitytermsforlanguageview'] =
<<<HTML
<$1 class="wikibase-entitytermsforlanguageview wikibase-entitytermsforlanguageview-$3" >
	<$9 class="wikibase-entitytermsforlanguageview-language">$4</$9>
	<$2 class="wikibase-entitytermsforlanguageview-label">$5</$2>
	<$2 class="wikibase-entitytermsforlanguageview-description">$6</$2>
	<$2 class="wikibase-entitytermsforlanguageview-aliases">$7</$2>
	<!-- ? wikibase-toolbar -->$8
</$1>
HTML;

	$templates['wikibase-sitelinkgrouplistview'] =
<<<HTML
<div class="wikibase-sitelinkgrouplistview"><!-- wikibase-listview -->$1</div>
HTML;

	$templates['wikibase-sitelinkgroupview'] =
<<<HTML
<div class="wikibase-sitelinkgroupview$7" data-wb-sitelinks-group="$5">
	<div class="wikibase-sitelinkgroupview-heading-section">
		<div class="wikibase-sitelinkgroupview-heading-container">
			<h3 class="wb-sitelinks-heading" dir="auto" id="$1">$2<span class="wikibase-sitelinkgroupview-counter">$3</span></h3>
			<!-- wikibase-toolbar -->$6
		</div>
	</div>
	<div class="mw-collapsible-content">
		<!-- wikibase-sitelinklistview -->$4
	</div>
</div>
HTML;

	$templates['wikibase-sitelinklistview'] =
<<<HTML
<div class="wikibase-sitelinklistview">
	<ul class="wikibase-sitelinklistview-listview"><!-- [0,*] wikibase-sitelinkview -->$1</ul>
</div>
HTML;

	$templates['wikibase-sitelinkview'] =
<<<HTML
<li class="wikibase-sitelinkview wikibase-sitelinkview-$1" data-wb-siteid="$1">
	<span class="wikibase-sitelinkview-siteid-container">
		<span class="wikibase-sitelinkview-siteid wikibase-sitelinkview-siteid-$1" title="$3">$2</span>
	</span><span class="wikibase-sitelinkview-link wikibase-sitelinkview-link-$1"><!-- wikibase-sitelinkview-pagename -->$4</span>
</li>
HTML;

	$templates['wikibase-sitelinkview-pagename'] =
<<<HTML
<span class="wikibase-sitelinkview-page" dir="$5" lang="$4"><a href="$1" hreflang="$4" title="$2">$2</a></span><!-- wikibase-badgeselector -->$3
HTML;

	$templates['wikibase-sitelinkview-unknown'] =
<<<HTML
<li class="wikibase-sitelinkview-site-unknown">
	<span class="wikibase-sitelinkview-siteid wikibase-sitelinkview-siteid-unknown">$1</span>
	<span class="wikibase-sitelinkview-link wikibase-sitelinkview-link-unknown">$2</span>
</li>
HTML;

	$templates['wb-badge'] =
<<<HTML
<span class="wb-badge wb-badge-$1" title="$2" data-wb-badge="$3"></span>
HTML;

	$templates['wikibase-badgeselector'] =
<<<HTML
<span class="wikibase-badgeselector wikibase-sitelinkview-badges"><!-- [0,*] wb-badge -->$1</span>
HTML;

	$templates['wikibase-propertyview-datatype'] =
<<<HTML
<div class="wikibase-propertyview-datatype">
	<div class="wikibase-propertyview-datatype-value">$1</div>
</div>
HTML;

	$templates['wikibase-toolbar-item'] =
<<<HTML
<span class="wikibase-toolbar-item">$1</span>
HTML;

	$templates['wikibase-toolbar-button'] =
<<<HTML
<span class="wikibase-toolbar-item wikibase-toolbar-button $1"><a href="$2" title="$4"><span class="wb-icon"></span>$3</a></span>
HTML;

	$templates['wikibase-toolbar'] =
<<<HTML
<span class="wikibase-toolbar-item wikibase-toolbar $1">$2</span>
HTML;

	$templates['wikibase-toolbar-container'] =
<<<HTML
<span class="wikibase-toolbar-container">$1</span>
HTML;

// Helper template for styling
// TODO: Remove template
	$templates['wikibase-toolbar-wrapper'] =
<<<HTML
<span class="wikibase-toolbar-wrapper">$1</span>
HTML;

	$templates['wikibase-toolbar-bracketed'] =
<<<HTML
[$1]
HTML;

	$templates['ui-closeable'] =
<<<HTML
<div class="ui-closeable">
	<div class="ui-closeable-close">✕</div>
	<div class="ui-closeable-content">$1</div>
</div>
HTML;

	$templates['wikibase-pageimage'] =
<<<HTML
<div class="wikibase-pageImage">
	<div class="help">
		<span class="wb-help-field-hint wikibase-toolbar-item wikibase-wbtooltip" title="$1">&nbsp;</span>
	</div>
</div>
HTML;

	return $templates;
} );
