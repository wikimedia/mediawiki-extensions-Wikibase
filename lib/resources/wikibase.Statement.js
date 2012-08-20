/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';

var PARENT = wb.Claim,
	constructor = function( mainSnak, qualifiers, references ) {
		PARENT.call( this, mainSnak, qualifiers );
		this._references = references;
	};

/**
 * Represents a Wikibase Statement in JavaScript.
 * @constructor
 * @extends wb.Claim
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyNoValueSnak
 *
 * @param {Number} propertyId
 */
wb.Statement = wb.utilities.inherit( PARENT, constructor, {
	/**
	 * @type array
	 * @todo determine whether we should rather model a Reference object for this
	 * @todo think about implementing a ReferenceList/ClaimList rather than having an Array here
	 */
	_references: null,

	/**
	 * Returns all of the statements references.
	 *
	 * sufficient
	 * @return Claim[]
	 */
	getReferences: function() {
		return this._references.slice();
	},

	/**
	 * Overwrites the current set of the statements references.
	 *
	 * @param Claim[] references
	 */
	setReferences: function( references ) {
		this._references = references;
	}
} );

}( mediaWiki, wikibase, jQuery ) );
