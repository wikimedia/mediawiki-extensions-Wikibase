( function () {
	'use strict';

	/**
	 * Interface to a `jQuery.wikibase.snakview` instance that allows querying the `snakview` for
	 * information as well as updating the `snakview`. Does not provide functions to actively change
	 * the view but acts as a state object.
	 *
	 * @see jQuery.wikibase.snakview
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	class WbSnakviewViewState {
		/**
		 * @param {jQuery.wikibase.snakview} snakView
		 *
		 * @throws {Error} if a required parameter is not specified properly.
		 */
		constructor( snakView ) {
			/**
			 * The `snakview` the `ViewState` is interfacing to.
			 *
			 * @property {jQuery.wikibase.snakview}
			 */
			this._view = snakView;
		}

		/**
		 * Notifies the `snakview` of a status update.
		 *
		 * @see jQuery.wikibase.snakview.updateStatus
		 *
		 * @param {string} status
		 */
		notify( status ) {
			this._view.updateStatus( status );
		}

		/**
		 * @see jQuery.wikibase.snakview.isInEditMode
		 *
		 * @return {boolean}
		 */
		isInEditMode() {
			return this._view.isInEditMode();
		}

		/**
		 * @see jQuery.wikibase.snakview.propertyId
		 *
		 * @return {string}
		 */
		propertyId() {
			return this._view.propertyId();
		}

		/**
		 * @see jQuery.wikibase.snakview.snakType
		 *
		 * @return {string}
		 */
		snakType() {
			return this._view.snakType();
		}

		/**
		 * @see jQuery.wikibase.snakview.isDisabled
		 *
		 * @return {boolean}
		 */
		isDisabled() {
			return this._view.option( 'disabled' );
		}
	}

	module.exports = WbSnakviewViewState;

}() );
