/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */

/**
 * Global 'Wikibase' extension singleton.
 * @since 0.1
 */
this.wikibase = this.wb = new ( function Wb( mw, $ ) {
	'use strict';

	/**
	 * Holds a RevisionStore object to have access to stored revision ids.
	 *
	 * TODO: Should go with the implementation of proper store interface (see fetchedEntities todo)
	 *
	 * @var wikibase.RevisionStore
	 */
	this._revisionStore = null;

	/**
	 * Returns a revision store
	 *
	 * @return wikibase.RevisionStore
	 */
	this.getRevisionStore = function() {
		if( this._revisionStore === null ) {
			this._revisionStore = new this.RevisionStore( mw.config.get( 'wgCurRevisionId' ) );
		}
		return this._revisionStore;
	};

	/**
	 * Tries to retrieve Universal Language Selector's set of languages.
	 *
	 * TODO: Further decouple this from ULS. Make the languages known to Wikibase a config thing
	 *  and use ULS as source for that language information, then inject it into Wikibase upon
	 *  initialization. This way, everything beyond extension initialization doesn't have to know
	 *  about ULS.
	 *
	 * @return {Object} Set of languages (empty object when ULS is not available)
	 */
	this.getLanguages = function() {
		return ( $.uls !== undefined ) ? $.uls.data.languages : {};
	};

	/**
	 * Returns the name of a language by its language code. If the language code is unknown or ULS
	 * can not provide sufficient language information, the language code is returned.
	 *
	 * @param {string} langCode
	 * @return string
	 */
	this.getLanguageNameByCode = function( langCode ) {
		var language = this.getLanguages()[ langCode ];
		if( language && language[2] ) {
			return language[2];
		}
		return langCode;
	};

	/**
	 * Same getLanguageNameByCode but on user UI native language instead, fallbacks
	 * to getLanguageNameByCode in cases native translation wasn't available
	 */
	this.getNativeLanguageNameByCode = function( langCode ) {
		var ulsLanguages = mw.config.get( 'wgULSLanguages' );
		return ( ulsLanguages && ulsLanguages[langCode] ) ?
			ulsLanguages[langCode] :
			this.getLanguageNameByCode( langCode );
	};

	this._proxyToWbSites = function( fnName ) {
		// Late binding to this.sites because it's not there when this is executed
		mw.log.deprecate(
			this,
			fnName,
			function() {
				return this.sites[fnName].apply( this.sites, arguments );
			},
			'Use wikibase.sites.*Site*() instead.'
		);
	};

	this._proxyToWbSites( 'getSite' );
	this._proxyToWbSites( 'getSiteByGlobalId' );
	this._proxyToWbSites( 'getSiteGroup' );
	this._proxyToWbSites( 'getSites' );
	this._proxyToWbSites( 'getSitesOfGroup' );
	this._proxyToWbSites( 'hasSite' );

} )( mediaWiki, jQuery );
