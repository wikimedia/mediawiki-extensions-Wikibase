/**
 * JavasScript for creating and managing states (disabled/enabled) within the 'Wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
( function( mw, wb ) {
	'use strict';

	// make sure wb.utilities.ui exists, move this into its own file as soon as there are more than this utility!
	wb.utilities.ui = wb.utilities.ui || {};

	/**
	 * Allows to extend random elements with enable/disable functionality.
	 * @constructor
	 * @extension
	 *
	 * @example wb.utilities.ui.StatableObject.useWith( SomeConstructor, {
	 *   getState: function() { ... },
	 *   _setState: function( state ) { ... }
	 * } );
	 * SomeConstructor.disable();
	 * SomeConstructor.isEnabled();
	 *
	 * @since 0.2 (moved from wb.ui.StateExtension which was available in 0.1)
	 */
	wb.utilities.ui.StatableObject = wb.utilities.newExtension( {
		/**
		 * @const states of elements / element groups
		 * @enum Number
		 */
		STATE: {
			ENABLED: 1, // enabled / all elements are enabled
			DISABLED: 2, // disabled / all elements are disabled
			MIXED: 3 // some are dis- an some are enabled
		},

		/**
		 * Determines the object's state.
		 * @see wb.utilities.abstractMember
		 *
		 * @return Number state
		 */
		getState: wb.utilities.abstractMember,

		/**
		 * Sets the object's state.
		 * @see wb.utilities.abstractMember
		 *
		 * @param Number state
		 *
		 * @return Boolean whether the operation was successful
		 */
		_setState: wb.utilities.abstractMember,

		/**
		 * Sets the object's state.
		 *
		 * @param Number state one of wb.ui.EditableValue.STATE
		 * @return Boolean whether the desired state has been applied (or had been applied already)
		 */
		setState: function( state ) {
			if( state === this.getState() ) {
				return true; // already has the desired state
			}
			return this._setState.apply( this, arguments );
		},

		/**
		 * Convenience method to disable this object.
		 *
		 * @return Boolean whether the operation was successful
		 */
		disable: function() {
			return this.setState( this.STATE.DISABLED );
		},

		/**
		 * Convenience method to enable this object.
		 *
		 * @return Boolean whether the operation was successful
		 */
		enable: function() {
			return this.setState( this.STATE.ENABLED );
		},

		/**
		 * Returns whether this object is disabled.
		 *
		 * @return Boolean true if disabled
		 */
		isDisabled: function() {
			return ( this.getState() === this.STATE.DISABLED );
		},

		/**
		 * Returns whether this object is enabled.
		 *
		 * @return Boolean true if enabled
		 */
		isEnabled: function() {
			return ( this.getState() === this.STATE.ENABLED );
		}

	} );

} )( mediaWiki, wikibase );
