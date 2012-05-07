( function ( $, mw, QUnit ) {
	"use strict";

	/**
	 * Reset basic configuration for each test(); Makes sure that global configuration stuff and cached stuff will be
	 * resetted befor each test. Also allows to set additional 'setup' and 'teardown' commands.
	 *
	 * @see QUnit.newMWEnvironment
	 *
	 * @param Object overrideConfig (optional)
	 * @param Object overrideMsgs (optional)
	 * @param Object additionalQUnitLifecycle
	 *
	 * @example:
	 * <code>
	 * module( ..., newWbEnvironment() );
	 *
	 * test( ..., function () {
	 *     mw.config.set( 'wbSiteDetails', ... ); // just for this test
	 *     wikibase.getSites(); // will return sites set above
	 * } );
	 *
	 * test( ..., function () {
	 *     wikibase.getSites(); // will return {} since wbSiteDetails global is reset now
	 * } );
	 *
	 *
	 * module( ..., newMwEnvironment( { 'wbSiteDetails', ... } ) );
	 *
	 * test( ..., function () {
	 *     wikibase.getSites(); // from the set above
	 *     wikibase._siteList = null // removes cached values
	 *     mw.config.set( 'wbSiteDetails', ... );
	 * } );
	 *
	 * test( .., function () {
	 *     wikibase.getSites(); // returns the ones set in module() again
	 * } );
	 *
	 *
	 * // additional setup and teardown:
	 * module( .., newMwEnvironment( null, null, { setup: .. , teardown: .. } ) );
	 * </code>
	 */
	QUnit.newWbEnvironment = ( function () {

		return function ( overrideConfig, overrideMsgs, additionalQUnitLifecycle ) {
			additionalQUnitLifecycle = additionalQUnitLifecycle || {};

			var additionalSetup = additionalQUnitLifecycle.setup || function() {};
			var additionalTeardown = additionalQUnitLifecycle.teardown || function() {};

			// init a new MW environment first, so we clean up the basic config stuff
			var mwEnv = new QUnit.newMwEnvironment( overrideConfig, overrideMsgs );

			return {
				setup: function () {
					mwEnv.setup();

					// empty cache of wikibases site details
					wikibase._siteList = null;

					additionalSetup.call( this );
				},

				teardown: function () {
					mwEnv.teardown();
					additionalTeardown.call( this );
				}
			};
		};
	}() );

})( jQuery, mediaWiki, QUnit );
