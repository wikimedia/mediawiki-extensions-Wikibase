/**
 * JavaScript storing revision ids about different sections.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher
 */
( function( mw, wb, $, undefined ) {
'use strict';

/**
 * Offers access to stored revision ids
 * @constructor
 * @since 0.1
 */
wb.RevisionStore = function( baseRev ) {
	this._revisions = {
		baseRevision: baseRev,
		labelRevision: baseRev,
		descriptionRevision: baseRev,
		aliasesRevision: baseRev,
		sitelinksRevision: [],
		lastSitelinksRevision: baseRev
	};
};

wb.RevisionStore.prototype = {
	printRevisionStore: function() {
		console.log(this._revisions);
	},

	/**
	 * Returns the base revision id.
	 */
	getBaseRevision: function() {
		return this._revisions.baseRevision;
	},

	/**
	 * Returns the label revision id.
	 */
	getLabelRevision: function() {
		console.log("request label using revid " + this._revisions.labelRevision);
		return this._revisions.labelRevision;
	},

	/**
	 * Returns the description revision id.
	 */
	getDescriptionRevision: function() {
		console.log("request description using revid " + this._revisions.descriptionRevision);
		return this._revisions.descriptionRevision;
	},

	/**
	 * Returns the aliases revision id.
	 */
	getAliasesRevision: function() {
		console.log("request aliases using revid " + this._revisions.aliasesRevision);
		return this._revisions.aliasesRevision;
	},

	/**
	 * Returns the sitelinks revision id.
	 */
	getSitelinksRevision: function( lang ) {
		if( this._revisions.sitelinksRevision[lang] === undefined ) {
			console.log("request " + lang + " sitelink using revid " + this._revisions.lastSitelinksRevision);
			return this._revisions.lastSitelinksRevision;
		}
		console.log("request " + lang + " sitelink using revid " + this._revisions.sitelinksRevision[lang]);
		return this._revisions.sitelinksRevision[lang];
	},

	/**
	 * Saves the label revision id.
	 */
	setLabelRevision: function( rev ) {
		console.log("got back label revid " + rev);
		this._revisions.labelRevision = rev;
	},

	/**
	 * Saves the description revision id.
	 */
	setDescriptionRevision: function( rev ) {
		console.log("got back description revid " + rev);
		this._revisions.descriptionRevision = rev;
	},

	/**
	 * Saves the aliases revision id.
	 */
	setAliasesRevision: function( rev ) {
		console.log("got back aliases revid " + rev);
		this._revisions.aliasesRevision = rev;
	},

	/**
	 * Saves the sitelinks revision id.
	 */
	setSitelinksRevision: function( rev, lang ) {
		console.log("got back " + lang + " sitelink revid " + rev);
		this._revisions.sitelinksRevision[lang] = rev;
		this._revisions.lastSitelinksRevision = rev;
	}

};
} )( mediaWiki, wikibase, jQuery );
