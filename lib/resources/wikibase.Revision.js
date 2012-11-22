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
wb.Revision = function( baseRev ) {
	this._revisionStore = {
		baseRevision: baseRev,
		labelRevision: baseRev,
		descriptionRevision: baseRev,
		aliasesRevision: baseRev,
		sitelinksRevision: [],
		lastSitelinksRevision: baseRev
	};
};

wb.Revision.prototype = {
	printRevisionStore: function() {
		console.log(this._revisionStore);
	},

	/**
	 * Returns the baserevid.
	 */
	getBaseRevision: function() {
		return this._revisionStore.baseRevision;
	},

	/**
	 * Returns the label revid.
	 */
	getLabelRevision: function() {
		console.log("request label using revid " + this._revisionStore.labelRevision);
		return this._revisionStore.labelRevision;
	},
	
	/**
	 * Returns the description revid.
	 */
	getDescriptionRevision: function() {
		console.log("request description using revid " + this._revisionStore.descriptionRevision);
		return this._revisionStore.descriptionRevision;
	},
	
	/**
	 * Returns the aliases revid.
	 */
	getAliasesRevision: function() {
		console.log("request aliases using revid " + this._revisionStore.aliasesRevision);
		return this._revisionStore.aliasesRevision;
	},
	
	/**
	 * Returns the sitelinks revid.
	 */
	getSitelinksRevision: function( lang ) {
		if( this._revisionStore.sitelinksRevision[lang] === undefined ) {
			console.log("request " + lang + " sitelink using revid " + this._revisionStore.lastSitelinksRevision);
			return this._revisionStore.lastSitelinksRevision;
		}
		console.log("request " + lang + " sitelink using revid " + this._revisionStore.sitelinksRevision[lang]);
		return this._revisionStore.sitelinksRevision[lang];
	},

	/**
	 * Saves the label revision
	 */
	setLabelRevision: function( rev ) {
		console.log("got back label revid " + rev);
		this._revisionStore.labelRevision = rev;
	},

	/**
	 * Saves the description revision
	 */
	setDescriptionRevision: function( rev ) {
		console.log("got back description revid " + rev);
		this._revisionStore.descriptionRevision = rev;
	},

	/**
	 * Saves the aliases revision
	 */
	setAliasesRevision: function( rev ) {
		console.log("got back aliases revid " + rev);
		this._revisionStore.aliasesRevision = rev;
	},

	/**
	 * Saves the sitelinks revision
	 */
	setSitelinksRevision: function( rev, lang ) {
		console.log("got back " + lang + " sitelink revid " + rev);
		this._revisionStore.sitelinksRevision[lang] = rev;
		this._revisionStore.lastSitelinksRevision = rev;
	}

};
} )( mediaWiki, wikibase, jQuery );
