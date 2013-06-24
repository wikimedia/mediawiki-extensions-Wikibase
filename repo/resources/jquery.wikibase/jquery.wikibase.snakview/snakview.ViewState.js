/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Allows to receive information about a related snakview object but doesn't provide functions
	 * to actively change the view. It serves as a state object to inform subsystems of the view's
	 * current status. Those subsystems should not have full access to the entire view though since
	 * interaction in both directions would very likely mess things up.
	 *
	 * @constructor
	 * @since 0.4
	 *
	 * @param {jQuery.wikibase.snakview} snakView
	 */
	var SELF =  $.wikibase.snakview.ViewState = function WbSnakviewViewState( snakView ) {
		if( !( snakView instanceof $.wikibase.snakview ) ) {
			throw new Error( 'Can not create a snakview ViewState object without a snakview' );
		}
		this._view = snakView;
	};
	$.extend( SELF.prototype, {
		/**
		 * The widget object whose status is represented.
		 * @type jQuery.wikibase.snakview
		 */
		_view: null,

		/**
		 * Notifies the snakview of a status update.
		 * @since 0.4
		 *
		 * @param {string} status
		 */
		notify: function( status ) {
			this._view.updateStatus( status );
		},

		/**
		 * @see jQuery.wikibase.snakview.isInEditMode
		 */
		isInEditMode: function() {
			return this._view.isInEditMode();
		},

		/**
		 * @see jQuery.wikibase.snakview.propertyId
		 */
		propertyId: function() {
			return this._view.propertyId();
		},

		/**
		 * @see jQuery.wikibase.snakview.snakType
		 */
		snakType: function() {
			return this._view.snakType();
		},

		/**
		 * @see jQuery.wikibase.snakview.isDisabled
		 */
		isDisabled: function() {
			return this._view.isDisabled();
		}
	} );

}( mediaWiki, wikibase, jQuery ) );
