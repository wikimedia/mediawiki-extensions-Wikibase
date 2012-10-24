/**
 * Registration of global JavaScript template function.
 *
 * @since 0.2
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

( function( mw ) {
	'use strict';

	/**
	 * Resturns a template filled with the specified parameters.
	 *
	 * @param {String} key
	 * @param {Array} [params]
	 * @return {String}
	 */
	mw.template = function( key, params ) {
		// mw.templates object is constructed within TemplateModule right before TemplateModule
		// loads this file's content.
		var template = new mw.Message( mw.templates.store, key, params );
		return template.plain();
	};

}( mediaWiki ) );
