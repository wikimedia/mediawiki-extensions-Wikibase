/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	/**
	 * Represents a Wikibase Reference in JavaScript.
	 * @constructor
	 * @since 0.3
	 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#ReferenceRecords
	 *
	 * @param {wb.Snak[]|wb.Snak|wb.SnakList} [snaks]
	 * @param {string} [hash] The hash of the Reference, required when API is used to change or
	 *        remove a certain Reference. In the PHP object model, the hash of a Reference changes
	 *        always when the Reference changes. In the JavaScript Reference object the hash will
	 *        not change but remain the same.
	 *
	 * TODO: get rid of 'hash' parameter and introduce a method to generate the hash, but make sure
	 *       it will be the same as it would be for a Reference in PHP.
	 */
	var SELF = wb.Reference = function WbReference( snaks, hash ) {
		this.setSnaks( snaks );
		this._hash = hash;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type string|null
		 */
		_hash: null,

		/**
		 * @type wb.SnakList
		 */
		_snaks: null,

		/**
		 * Will return the hash set for the reference initially in the constructor. This is required
		 * when changing a reference via the API and differs from the PHP's Reference's getHash.
		 * @since 0.4
		 *
		 * @return string|null
		 */
		getHash: function() {
			return this._hash;
		},

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
		},

		/**
		 * Will return whether a given reference equals this one. This will compare the reference's
		 * snaks only and not involve checking the hash.
		 *
		 * @param {wb.Reference} reference
		 * @return {boolean}
		 */
		equals: function( reference ) {
			return ( this._snaks.equals( reference.getSnaks() ) );
		},

		/**
		 * Returns a JSON structure representing this reference.
		 * @since 0.4
		 *
		 * TODO: implement this as a wb.serialization.Serializer
		 *
		 * @return {Object}
		 */
		toJSON: function() {
			var json = {
				snaks: this._snaks.toJSON()
			};

			if ( this._hash ) {
				json.hash = this._hash;
			}

			return json;
		}
	} );

	SELF.newFromJSON = function( json ) {
		return new SELF(
			wb.SnakList.newFromJSON( json.snaks ),
			json.hash
		);
	};

}( wikibase, jQuery ) );
