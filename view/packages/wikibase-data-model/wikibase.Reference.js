/**
 * @file
 * @ingroup WikibaseLib
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
	 * @param {wb.Snak[]|wb.Snak|wb.SnakList} [snaks]
	 */
	wb.Reference = function( snaks ) {
		this.setSnaks( snaks );
	};

	wb.Reference.prototype = {
		/**
		 * @type wb.Snak[]
		 * @todo think about implementing a SnakList rather than having an Array here
		 */
		_snaks: null,

		/**
		 * Returns a list of the Snaks.
		 *
		 * @return wb.SnakList
		 */
		getSnaks: function() {
			return this._snaks;
		},

		/**
		 * Overwrites the current set of snaks.
		 *
		  * @param {wb.Snak[]|wb.Snak|wb.SnakList} snaks
		 */
		setSnaks: function( snaks ) {
			this._snaks = new wb.SnakList( snaks );
		}
	};

	wb.Reference.newFromJSON = function( json ) {
		return new wb.Reference(
			wb.SnakList.newFromJSON( json )
		);
	}

}( wikibase, jQuery ) );
