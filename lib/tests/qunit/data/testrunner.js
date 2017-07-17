/**
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */

( function ( $, mw, wb, QUnit ) {
	'use strict';

	/**
	 * Reset basic configuration for each test(); Makes sure that global configuration stuff and
	 * cached stuff will be reset before each test. Also allows to set additional 'setup' and
	 * 'teardown' commands.
	 *
	 * @see QUnit.newMwEnvironment
	 *
	 * @param {Object} custom Test environment variables according to QUnit.newMwEnvironment
	 *
	 * @example
	 * <code>
	 * module( ..., newWbEnvironment() );
	 *
	 * test( ..., function () {
	 *     mw.config.set( 'wbSiteDetails', ... ); // just for this test
	 *     wikibase.sites.getSites(); // will return sites set above
	 * } );
	 *
	 * test( ..., function () {
	 *     wikibase.sites.getSites(); // will return {} since wbSiteDetails global is reset now
	 * } );
	 *
	 *
	 * module( ..., newMwEnvironment( { config: { 'wbSiteDetails', ... } } ) );
	 *
	 * test( ..., function () {
	 *     wikibase.sites.getSites(); // from the set above
	 *     wikibase.sites._siteList = null // removes cached values
	 *     mw.config.set( 'wbSiteDetails', ... );
	 * } );
	 *
	 * test( ..., function () {
	 *     wikibase.sites.getSites(); // returns the ones set in module() again
	 * } );
	 *
	 *
	 * // additional setup and teardown:
	 * module( ..., newMwEnvironment( { setup: .. , teardown: ... } ) );
	 * </code>
	 */
	QUnit.newWbEnvironment = function ( custom ) {
		if ( custom === undefined ) {
			custom = {};
		}

		// init a new MW environment first, so we clean up the basic config stuff
		return QUnit.newMwEnvironment( {
			config: custom.config,
			messages: custom.messages,
			setup: function () {
				// The MediaWiki test environment does a deep extend of the mw.config map with
				// the custom config. We just want to check against our custom config, if
				// defined.
				if ( custom.config ) {
					mw.config.set( custom.config );
				}

				wb.sites._siteList = null; // empty cache of wikibases site details
				if ( custom.setup ) {
					custom.setup.apply( this, arguments );
				}
			},
			teardown: function () {
				if ( custom.teardown ) {
					custom.teardown.apply( this, arguments );
				}
			}
		} );
	};

}( jQuery, mediaWiki, wikibase, QUnit ) );
