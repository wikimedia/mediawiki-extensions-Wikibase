/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
	'use strict';

	/**
	 * Module for 'Wikibase' extensions utilities.
	 * @var Object
	 */
	wb.utilities = wb.utilities || {};

	/**
	 * Helper for prototypal inheritance.
	 *
	 * @param Function base will be used for the prototype chain
	 * @param Function constructor (optional) for overwriting base constructor. Can be omitted.
	 * @param Object members (optional) properties overwriting or extending those of the base
	 * @return Function the constructor for the new, extended type
	 */
	wb.utilities.inherit = function( base, constructor, members ) {
		// allow to omit constructor since it can be inherited directly. But if given, require it as second parameter
		// for readability. If no constructor, second parameter is the prototype extension object.
		if( members === undefined ) {
			if( $.isFunction( constructor ) ) {
				members = {};
			} else {
				members = constructor || {}; // also support case where no parameters but base are given
				constructor = false;
			}
		}
		var ext = constructor || function() { base.apply( this, arguments ); };

		var extProto = function(){}; // new constructor for avoiding base constructor and with it any side-effects
		extProto.prototype = base.prototype;

		// make sure constructor property is set properly:
		members.constructor = members.constructor || ext;

		ext.prototype = $.extend( new extProto(), members );
		return ext;
	};

} )( mediaWiki, wikibase, jQuery );
