/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, $ ) {
'use strict';

/**
 * Offers information about a site known to the local Wikibase installation.
 * @constructor
 * @since 0.3
 *
 * @param {Object} siteDetails
 */
var SELF = wb.datamodel.Site = function WbSite( siteDetails ) {
	// TODO: Validate site details, throw error if invalid.
	this._siteDetails = $.extend( {}, siteDetails );
};

$.extend( SELF.prototype, {
	/**
	 * @type {Object}
	 */
	_siteDetails: null,

	/**
	 * @return {string}
	 */
	getId: function() {
		return this._siteDetails.id;
	},

	/**
	 * @return {string}
	 */
	getName: function() {
		return this._siteDetails.name;
	},

	/**
	 * @return string
	 */
	getShortName: function() {
		return this._siteDetails.shortName;
	},

	/**
	 * @return {string}
	 */
	getGroup: function() {
		return this._siteDetails.group;
	},

	/**
	 * @return {string}
	 */
	getPageUrl: function() {
		return this._siteDetails.pageUrl;
	},

	/**
	 * @return {string}
	 */
	getApi: function() {
		return this._siteDetails.apiUrl;
	},

	/**
	 * @return {string}
	 */
	getLanguageCode: function() {
		return this._siteDetails.languageCode;
	}

} );
}( wikibase, jQuery ) );
