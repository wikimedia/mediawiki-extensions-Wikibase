<?php

namespace Wikibase;

/**
 * Contains templates commonly used in server-side output generation and client-side JavaScript
 * processing.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 *
 * @return array templates
 */

return call_user_func( function() {
	$templates = array();

	$templates['wikibase-entityview'] =
<<<HTML
<div id="wb-$1-$2" class="wikibase-entityview wb-$1" lang="$3" dir="$4">
	<div class="wikibase-entityview-main">$5</div>
	<div class="wikibase-entityview-side">$6</div>
</div>
HTML;

	$templates['wb-entity-toc'] =
<<<HTML
<div id="toc" class="toc wb-toc">
	<div id="toctitle">
		<h2>$1</h2>
	</div>
	<ul>$2</ul>
</div>
HTML;

// $1: Index of the section
// $2: Target of the link
// $3: Text of the link
	$templates['wb-entity-toc-section'] =
<<<HTML
<li class="toclevel-1 tocsection-$1"><a href="#$2"><span class="toctext">$3</span></a></li>
HTML;

// $1: Text of the heading.
// $2: Optional ID for the heading.
	$templates['wb-section-heading'] =
<<<HTML
<h2 class="wb-section-heading" dir="auto" id="$2">$1</h2>
HTML;

	$templates['wikibase-statementgrouplistview'] =
<<<HTML
<div class="wikibase-statementgrouplistview"><!-- wikibase-listview -->$1</div>
HTML;

	$templates['wikibase-statementgroupview'] =
<<<HTML
<div class="wikibase-statementgroupview" id="$3">
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
<div class="wikibase-snakview">
	<div class="wikibase-snakview-property-container">
		<div class="wikibase-snakview-property" dir="auto">$1</div>
	</div>
	<div class="wikibase-snakview-value-container" dir="auto">
		<div class="wikibase-snakview-typeselector"></div>
		<div class="wikibase-snakview-value $2">$3</div>
	</div>
</div>
HTML;

	$templates['wikibase-statementview'] =
<<<HTML
<div class="wikibase-statementview wikibase-statement-$1">
	<div class="wikibase-statementview-rankselector">$2</div>
	<div class="wikibase-statementview-mainsnak-container">
		<div class="wikibase-statementview-mainsnak" dir="auto"><!-- wikibase-snakview -->$3</div>
		<div class="wikibase-statementview-qualifiers"><!-- wikibase-listview -->$4</div>
	</div>
	<!-- wikibase-toolbar -->$5
	<div class="wikibase-statementview-references-container">
		<div class="wikibase-statementview-references-heading">$6</div>
		<div class="wikibase-statementview-references"><!-- wikibase-listview -->$7</div>
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

	$templates['wikibase-labelview'] =
<<<HTML
<div class="wikibase-labelview $1" dir="auto">
	<div class="wikibase-labelview-container">
		<span class="wikibase-labelview-text">$2</span>
		<span class="wikibase-labelview-entityid">$3</span>
		<!-- wikibase-toolbar -->$4
	</div>
</div>
HTML;

	$templates['wikibase-descriptionview'] =
<<<HTML
<div class="wikibase-descriptionview $1" dir="auto">
	<div class="wikibase-descriptionview-container">
		<span class="wikibase-descriptionview-text">$2</span>
		<!-- wikibase-toolbar -->$3
	</div>
</div>
HTML;

	$templates['wikibase-aliasesview'] =
<<<HTML
<div class="wikibase-aliasesview $1">
	<ul class="wikibase-aliasesview-list">$2</ul>
	<!-- wikibase-toolbar -->$3
</div>
HTML;

	$templates['wikibase-aliasesview-list-item'] =
<<<HTML
<li class="wikibase-aliasesview-list-item" dir="auto">$1</li>
HTML;

	$templates['wikibase-entitytermsview'] =
<<<HTML
<div class="wikibase-entitytermsview">
	<div class="wikibase-entitytermsview-heading">
		<h1 class="wikibase-entitytermsview-heading-label $1"><!-- wikibase-entitytermsview-label -->$2</h1>
		<div class="wikibase-entitytermsview-heading-aliases $3"><!-- wikibase-entitytermsview-aliases -->$4</div>
		<div class="wikibase-entitytermsview-heading-description $5">$6</div>
	</div>
	<!-- ? wikibase-toolbar -->$9
	<div class="wikibase-entitytermsview-entitytermsforlanguagelistview $8"><!-- wikibase-entitytermsforlanguagelistview -->$7</div>
</div>
HTML;

	$templates['wikibase-entitytermsview-heading-label'] =
<<<HTML
$1<span class="wikibase-entitytermsview-heading-label-id">$2</span>
HTML;

	$templates['wikibase-entitytermsview-aliases'] =
<<<HTML
<ul class="wikibase-entitytermsview-aliases"><!-- wikibase-entitytermsview-aliases-alias -->$1</ul>
HTML;

	$templates['wikibase-entitytermsview-aliases-alias'] =
<<<HTML
<li class="wikibase-entitytermsview-aliases-alias">$1</li>
HTML;

	$templates['wikibase-entitytermsforlanguagelistview'] =
<<<HTML
<table class="wikibase-entitytermsforlanguagelistview">
	<thead class="wikibase-entitytermsforlanguagelistview-header">
		<tr class="wikibase-entitytermsforlanguagelistview-header-row">
			<td class="wikibase-entitytermsforlanguagelistview-cell wikibase-entitytermsforlanguagelistview-language">$1</td>
			<td class="wikibase-entitytermsforlanguagelistview-cell wikibase-entitytermsforlanguagelistview-label">$2</td>
			<td class="wikibase-entitytermsforlanguagelistview-cell wikibase-entitytermsforlanguagelistview-aliases">$3</td>
			<td class="wikibase-entitytermsforlanguagelistview-cell wikibase-entitytermsforlanguagelistview-description">$4</td>
		</tr>
	</thead>
	<tbody class="wikibase-entitytermsforlanguagelistview-listview"><!-- [0,*] wikibase-entitytermsforlanguageview -->$5</tbody>
</table>
HTML;

	$templates['wikibase-entitytermsforlanguageview'] =
<<<HTML
<$1 class="wikibase-entitytermsforlanguageview wikibase-entitytermsforlanguageview-$3" >
	<$2 class="wikibase-entitytermsforlanguageview-language"><!-- wikibase-entitytermsforlanguageview-language -->$4</$2>
	<$2 class="wikibase-entitytermsforlanguageview-label">$5</$2>
	<$2 class="wikibase-entitytermsforlanguageview-aliases">$6</$2>
	<$2 class="wikibase-entitytermsforlanguageview-description">$7</$2>
	<!-- ? wikibase-toolbar -->$8
</$1>
HTML;

	$templates['wikibase-entitytermsforlanguageview-language'] =
<<<HTML
<a href="$1">$2</a>
HTML;

	$templates['wikibase-sitelinkgrouplistview'] =
<<<HTML
<div class="wikibase-sitelinkgrouplistview"><!-- wikibase-listview -->$1</div>
HTML;

	$templates['wikibase-sitelinkgroupview'] =
<<<HTML
<div class="wikibase-sitelinkgroupview" data-wb-sitelinks-group="$5">
	<div class="wikibase-sitelinkgroupview-heading-section">
		<div class="wikibase-sitelinkgroupview-heading-container">
			<h2 class="wb-section-heading" dir="auto" id="$1">$2<span class="wikibase-sitelinkgroupview-counter">$3</span></h2>
			<!-- wikibase-toolbar -->$6
		</div>
	</div>
	<!-- wikibase-sitelinklistview -->$4
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
		<span class="wikibase-sitelinkview-siteid wikibase-sitelinkview-siteid-$1" title="$5">$4</span>
	</span><span class="wikibase-sitelinkview-link wikibase-sitelinkview-link-$1" lang="$2" dir="$3"><!-- wikibase-sitelinkview-pagename -->$6</span>
</li>
HTML;

	$templates['wikibase-sitelinkview-pagename'] =
<<<HTML
<span class="wikibase-sitelinkview-page"><a href="$1" hreflang="$4">$2</a></span>$3
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
<span class="wikibase-toolbar-item wikibase-toolbar-button $1"><a href="$2">$3</a></span>
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

	return $templates;
} );
