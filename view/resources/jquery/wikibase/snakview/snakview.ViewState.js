( function ( $ ) {
	'use strict';

	$.wikibase = $.wikibase || {};
	$.wikibase.snakview = $.wikibase.snakview || {};

	/**
	 * Interface to a `jQuery.wikibase.snakview` instance that allows querying the `snakview` for
	 * information as well as updating the `snakview`. Does not provide functions to actively change
	 * the view but acts as a state object.
	 *
	 * @see jQuery.wikibase.snakview
	 * @class jQuery.wikibase.snakview.ViewState
	 * @license GPL-2.0+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 *
	 * @param {jQuery.wikibase.snakview} snakView
	 *
	 * @throws {Error} if a required parameter is not specified properly.
	 */
	var SELF = $.wikibase.snakview.ViewState = function WbSnakviewViewState( snakView ) {
		if ( !( snakView instanceof $.wikibase.snakview ) ) {
			throw new Error( 'Can not create a snakview ViewState object without a snakview' );
		}
		this._view = snakView;
	};
	$.extend( SELF.prototype, {
		/**
		 * The `snakview` the `ViewState` is interfacing to.
		 * @property {jQuery.wikibase.snakview}
		 */
		_view: null,

		/**
		 * Notifies the `snakview` of a status update.
		 *
		 * @see jQuery.wikibase.snakview.updateStatus
		 *
		 * @param {string} status
		 */
		notify: function ( status ) {
			this._view.updateStatus( status );
		},

		/**
		 * @see jQuery.wikibase.snakview.isInEditMode
		 *
		 * @return {boolean}
		 */
		isInEditMode: function () {
			return this._view.isInEditMode();
		},

		/**
		 * @see jQuery.wikibase.snakview.propertyId
		 *
		 * @return {string}
		 */
		propertyId: function () {
			return this._view.propertyId();
		},

		/**
		 * @see jQuery.wikibase.snakview.snakType
		 *
		 * @return {string}
		 */
		snakType: function () {
			return this._view.snakType();
		},

		/**
		 * @see jQuery.wikibase.snakview.isDisabled
		 *
		 * @return {boolean}
		 */
		isDisabled: function () {
			return this._view.option( 'disabled' );
		}
	} );

}( jQuery ) );
