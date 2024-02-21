/**
 * Object to wrap the results of API Edit calls that modify values in the backend.
 * Edits may create a new TempUser as a side-effect, and the editing code needs to
 * handle the resulting redirect.
 *
 * @license GPL-2.0-or-later
 * @author Arthur Taylor
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers;

	/**
	 * Encapsulates the result of an Api Edit call. Object includes the new
	 * value and the TempUserWatcher to track if a login is necessary
	 *
	 * @constructor
	 */
	var SELF = MODULE.ValueChangeResult = function WbValueChangeResult(
		savedValue,
		tempUserWatcher
	) {
		this._savedValue = savedValue;
		this._tempUserWatcher = tempUserWatcher;
	};

	$.extend( SELF.prototype, {

		getSavedValue: function () {
			return this._savedValue;
		},

		getTempUserWatcher: function () {
			return this._tempUserWatcher;
		}

	} );

	module.exports = SELF;
}( wikibase ) );
