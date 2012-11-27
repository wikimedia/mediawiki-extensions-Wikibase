/**
 * Registration of global JavaScript template function.
 *
 * @since 0.2
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

( function( mw ) {
	'use strict';

	/**
	 * Resturns a template filled with the specified parameters, similar to wfTemplate()
	 * @see mw.message()
	 *
	 * @param {string} key Key of the template to get.
	 * @param {mixed} parameter_1 First argument in a list of variadic arguments,
	 *  each a parameter for $N replacement in templates.
	 * @return {string}
	 */
	mw.template = function( key, parameter_1 /* [, parameter_2] */ ) {
		var params, template;
		// Support variadic arguments
		if ( parameter_1 !== undefined ) {
			params = Array.prototype.slice.call( arguments );
			params.shift();
		} else {
			params = [];
		}
		template = new mw.Message( mw.templates.store, key, params );
		return template.plain();
	};

}( mediaWiki ) );
