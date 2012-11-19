/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( wb, $, undefined ) {
	'use strict';

	/**
	 * Represents a Wikibase Reference in JavaScript.
	 * @constructor
	 * @since 0.3
	 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#ReferenceRecords
	 *
	 * @param {wb.Snak[]} snaks
	 */
	wb.Reference = function( snaks ) {
		this._snaks = snaks;
	};

	wb.Reference.prototype = {

		/**
		 * @type wb.Snak[]
		 * @todo think about implementing a SnakList rather than having an Array here
		 */
		_snaks: null,

		/**
		 * Returns all the snaks.
		 *
		 * @return wb.Snak[]
		 */
		getSnaks: function() {
			return this._snaks;
		},

		/**
		 * Overwrites the current set of snaks.
		 *
		 * @param {wb.Snak[]} snaks
		 */
		setSnaks: function( snaks ) {
			this._snaks = snaks;
		}
	};

}( wikibase, jQuery ) );
