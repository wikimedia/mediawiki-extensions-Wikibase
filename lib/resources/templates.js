/**
 * Registration of global JavaScript template function.
 *
 * @since 0.2
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( mw, $ ) {
	'use strict';

	/**
	 * Returns a template filled with the specified parameters, similar to wfTemplate()
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

	/**
	 * Fetches a template and fills it with specified parameters. The template has to have a single
	 * root DOM element. All of its child nodes will then be appended to the jQuery object's DOM
	 * nodes.
	 * @see mw.template
	 *
	 * @since 0.3
	 *
	 * @param {String} template
	 * @param {mixed} parameter_1 First argument in a list of variadic arguments,
	 *  each a parameter for $N replacement in templates.
	 * @return jQuery
	 */
	$.fn.applyTemplate = function( template, parameter_1 /* [, parameter_2] */ ) {
		//var args = Array.prototype.slice.call( arguments, 1 ),
		var rawTemplate = mw.template.apply( null, arguments ),
			$template = $( rawTemplate );

		if( $template.length !== 1 ) {
			throw new Error( 'Can not apply a template with more or less than one root node' );
		}

		// copy template's root node children and classes to given root
		this.addClass( $template.prop( 'class' ) );
		$template.children().appendTo( this.empty() );

		return this;
	};

}( mediaWiki, jQuery ) );
