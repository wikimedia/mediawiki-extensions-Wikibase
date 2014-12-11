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


	$templates['wb-entity-header-separator'] =
<<<HTML
<hr class="wb-hr" />
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

	$templates['wb-claimgrouplistview'] =
<<<HTML
<div class="wb-claimgrouplistview">
	<div class="wb-claimlists">$1<!-- [0,*] wb-claimlist--></div>
	$2<!-- {1} wb-toolbar -->
</div>
HTML;

	$templates['wb-claimgrouplistview-groupname'] =
<<<HTML
<div class="wb-claimgrouplistview-groupname">
	<div class="wb-claim-name" dir="auto">$1</div>
</div>
HTML;

	$templates['wb-claimlistview'] =
<<<HTML
<div class="wb-claimlistview">
	<div class="wb-claims" id="$3">
		$1 <!-- [0,*] wb-claim|wb-statement -->
	</div>
	$2 <!-- [0,*] wb-toolbar -->
</div>
HTML;

	// TODO: .wb-snakview should not be part of the template; check uses of that class and move them
	// to .wb-snak
	$templates['wb-snak'] =
// This template is not only used for PropertyValueSnak Snaks but also for other Snaks without a
// value which may display some message in the value node.
<<<HTML
<div class="wb-snak wb-snakview">
	<div class="wb-snak-property-container">
		<div class="wb-snak-property" dir="auto">$1</div>
	</div>
	<div class="wb-snak-value-container" dir="auto">
		<div class="wb-snak-typeselector"></div>
		<div class="wb-snak-value $2">$3</div>
	</div>
</div>
HTML;

	$templates['wikibase-statementview'] =
<<<HTML
<div class="wikibase-statementview wikibase-statement-$1">
	<div class="wikibase-statementview-rankselector">$2</div>
	<div class="wikibase-statementview-mainsnak-container">
		<div class="wikibase-statementview-mainsnak" dir="auto"><!-- wb-snak -->$3</div>
		<div class="wikibase-statementview-qualifiers"><!-- wb-listview -->$4</div>
	</div>
	<!-- wikibase-toolbar -->$5
	<div class="wikibase-statementview-references-container">
		<div class="wikibase-statementview-references-heading">$6</div>
		<div class="wikibase-statementview-references"><!-- wb-listview -->$7</div>
	</div>
</div>
HTML;

	$templates['wikibase-rankselector'] =
<<<HTML
<div class="wikibase-rankselector $1">
	<span class="ui-icon ui-icon-rankselector $2" title="$3"></span>
</div>
HTML;

	$templates['wb-referenceview'] =
<<<HTML
<div class="wb-referenceview $1">
	<div class="wb-referenceview-heading"></div>
	<div class="wb-referenceview-listview">$2<!-- [0,*] wb-snaklistview --></div>
</div>
HTML;


	$templates['wb-listview'] =
<<<HTML
<div class="wb-listview">$1</div>
HTML;

	$templates['wb-snaklistview'] =
<<<HTML
<div class="wb-snaklistview">
	<div class="wb-snaklistview-listview">$1<!-- wb-listview --></div>
</div>
HTML;

	$templates['wikibase-firstHeading'] =
// add an h1 for displaying the entity's label; the actual firstHeading is being hidden by
// css since the original MediaWiki DOM does not represent a Wikidata entity's structure
// where the combination of label and description is the unique "title" of an entity which
// should not be semantically disconnected by having elements in between, like siteSub,
// contentSub and jump-to-nav
<<<HTML
<h1 id="wb-firstHeading-$1" class="wb-firstHeading">
	<!-- wikibase-labelview -->$2
</h1>
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
	<div class="wikibase-aliasesview-container">
		<span class="wikibase-aliasesview-label">$2</span>
		<ul class="wikibase-aliasesview-list">$3</ul>
		<!-- wb-toolbar -->$4
	</div>
</div>
HTML;

	$templates['wikibase-aliasesview-list-item'] =
<<<HTML
<li class="wikibase-aliasesview-list-item" dir="auto">$1</li>
HTML;

	$templates['wikibase-entitytermsview'] =
<<<HTML
<div class="wikibase-entitytermsview">
	<div class="wikibase-entitytermsview-heading-container">
		<h2 id="wb-terms" class="wb-section-heading wikibase-entitytermsview-heading">$1</h2>
		<!-- wikibase-toolbar -->$3
	</div>
	<!-- wikibase-entitytermsforlanguagelistview -->$2
</div>
HTML;

	$templates['wikibase-entitytermsforlanguagelistview'] =
<<<HTML
<table class="wikibase-entitytermsforlanguagelistview">
	<colgroup>
		<col class="wikibase-entitytermsforlanguagelistview-language" />
		<col class="wikibase-entitytermsforlanguagelistview-label wikibase-entitytermsforlanguagelistview-description wikibase-entitytermsforlanguagelistview-aliases" />
	</colgroup>
	<!-- [0,*] wikibase-entitytermsforlanguageview -->$1
</table>
HTML;

	$templates['wikibase-entitytermsforlanguageview'] =
<<<HTML
<tbody class="wikibase-entitytermsforlanguageview wikibase-entitytermsforlanguageview-$1" >
	<tr>
		<td class="wikibase-entitytermsforlanguageview-language" rowspan="3"><a href="$2">$3</a></td>
		<td class="wikibase-entitytermsforlanguageview-label">$4</td>
	</tr>
	<tr>
		<td class="wikibase-entitytermsforlanguageview-description">$5</td>
	</tr>
	<tr>
		<td class="wikibase-entitytermsforlanguageview-aliases">$6</td>
	</tr>
</tbody>
HTML;

	$templates['wikibase-sitelinkgrouplistview'] =
<<<HTML
<div class="wikibase-sitelinkgrouplistview"><!-- wb-listview -->$1</div>
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
