/**
 * JavasScript for creating and managing states (disabled/enabled) within the 'Wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
( function( mw, wb, $, undefined ) {
	'use strict';

	/**
	 * Allows to extend random elements with enable/disable functionality.
	 * @constructor
	 * @extension
	 *
	 * @example wb.ui.StateExtension.useWith( SomeConstructor, {
	 *   getState: function() { ... },
	 *   setDisabled: function( disabled ) { ... }
	 * } );
	 * SomeConstructor.disable();
	 * SomeConstructor.isEnabled();
	 *
	 * @since 0.1
	 */
	wb.ui.StateExtension = wb.utilities.newExtension( {
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
		 * @see wb.utilities.abstractFunction
		 *
		 * @return Number state
		 */
		getState: wb.utilities.abstractFunction,

		/**
		 * Sets the object's state.
		 * @see wb.utilities.abstractFunction
		 *
		 * @param Boolean true to disable or false to enable
		 *
		 * @return Boolean whether the operation was successful
		 */
		setDisabled: wb.utilities.abstractFunction,

		/**
		 * Convenience method to disable this editable value.
		 *
		 * @return Boolean whether the operation was successful
		 */
		disable: function() {
			return this.setDisabled( true );
		},

		/**
		 * Convenience method to enable this editable value.
		 *
		 * @return Boolean whether the operation was successful
		 */
		enable: function() {
			return this.setDisabled( false );
		},

		/**
		 * Returns whether this editable value is disabled.
		 *
		 * @return Boolean true if disabled
		 */
		isDisabled: function() {
			return ( this.getState() === this.STATE.DISABLED );
		},

		/**
		 * Returns whether this editable value is enabled.
		 *
		 * @return Boolean true if enabled
		 */
		isEnabled: function() {
			return ( this.getState() === this.STATE.ENABLED );
		}

	} );

} )( mediaWiki, wikibase, jQuery );
