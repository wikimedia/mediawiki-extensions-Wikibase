/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, dv, $, undefined ) {
	'use strict';

	/**
	 * Module for 'Wikibase' extensions utilities.
	 * @var Object
	 */
	wb.utilities = wb.utilities || {};

	/**
	 * @see dataValues.util.inherit
	 */
	wb.utilities.inherit = dv.util.inherit;

	/**
	 * @see dv.util.abstractMember
	 */
	wb.utilities.abstractMember = dv.util.abstractMember;

	/**
	 * Can be used to create an empty constructor which can be used to create a new Object and at the same time has a
	 * static function to extend an existing Object/constructor with some functionality.
	 *
	 * @param Function base (optional) another extension or object from which the new one should inherit
	 * @param Object members the prototype definition of the new constructor.
	 *
	 * @return Function the constructor for the standalone version of the extension, which also has a static 'extend'
	 *         function attached for extending Objects/constructors with the extensions functionality.
	 */
	wb.utilities.newExtension = function( base, members ) {
		if( members === undefined ) {
			members = base;
			base = Object;
		}
		// use inherit() for convenience
		var Ext = wb.utilities.inherit( base, members );

		/**
		 * This will extend a given Object or constructor with the same functionality of the extension-constructor.
		 * Since JavaScript doesn't support multiple parents in prototype chains, this will copy functions into the
		 * given object or the constructors prototype if a constructor is given.
		 *
		 * @param Function|Object target constructor or Object which should receive the extension.
		 * @param Object members allows to immediately overwrite (abstract) extension functions.
		 */
		Ext.useWith = function( target, members ) {
			// we can extend Objects or constructors (prototypes)
			var realTarget = $.isFunction( target ) ? target.prototype : target,
				hadConstructor = realTarget.hasOwnProperty( 'constructor' );

			// make all functions available in the target:
			// explicitly given members only, not other functions which are added via prototype later!
			$.extend(
				realTarget,
				Ext.prototype,
				realTarget,   // by having this overwrite Ext.prototype, we won't overwrite already declared functions
				members || {} // for passing required overrides for abstract functions
			);
			if( !hadConstructor ) { // remove constructor property if copied from Ext or members
				delete( realTarget.constructor );
			}
			// TODO: could do more with the information of abstract functions here
		};
		return Ext;
	};

}( mediaWiki, wikibase, dataValues, jQuery ) );
