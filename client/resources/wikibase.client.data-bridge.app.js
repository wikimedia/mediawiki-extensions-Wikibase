/**
 * Load the correct Data Bridge application bundle for the current browser:
 * the modern bundle if it supports JavaScript modules,
 * otherwise the legacy bundle.
 * Exports a promise resolving to the exports of the real app module.
 */
( function () {
	'use strict';

	var moduleName = 'noModule' in HTMLScriptElement.prototype ?
		'wikibase.client.data-bridge.app.modern' :
		'wikibase.client.data-bridge.app.legacy';

	module.exports = mw.loader.using( moduleName ).then( function ( require ) {
		return require( moduleName );
	} );
}() );
