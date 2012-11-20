/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, $, undefined ) {
'use strict';

/**
 * Represents a Wikibase Claim in JavaScript.
 * @constructor
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @param {wb.Snak} mainSnak
 * @param {wb.Snak[]} qualifiers
 */
wb.Claim = function( mainSnak, qualifiers ) {
	this._mainSnak = mainSnak;
	this._qualifiers = qualifiers;
};

wb.Claim.prototype = {
	/**
	 * @type wb.Snak
	 */
	_mainSnak: null,

	/**
	 * @type wb.Snak[]
	 * @todo think about implementing a SnakList rather than having an Array here
	 */
	_qualifiers: null,

	/**
	 * Returns the main Snak.
	 *
	 * @return {wb.Snak}
	 */
	getMainSnak: function() {
		return this._mainSnak;
	},

	/**
	 * Overwrites the current main Snak.
	 *
	 * @param {wb.Snak} mainSnak
	 */
	setMainSnak: function( mainSnak ) {
		this._mainSnak = mainSnak;
	},

	/**
	 * Returns all qualifiers as an array of Snaks.
	 *
	 * @return wb.Snak[]
	 */
	getQualifiers: function() {
		return this._qualifiers;
	},

	/**
	 * Overwrites the current set of qualifiers.
	 *
	 * @param {wb.Snak[]} qualifiers
	 */
	setQualifiers: function( qualifiers ) {
		this._qualifiers = qualifiers;
	}
};

/**
 * Creates a new Claim object from a given JSON structure.
 *
 * @param {String} json
 * @return {wb.Claim}
 */
wb.Claim.newFromJSON = function( json ) {
	var mainSnak = wb.Snak.newFromJSON( json.mainsnak ),
		qualifiers = [],
		references = [],
		isStatement = json.type !== undefined && json.type === 'statement';

	if ( json.qualifiers !== undefined ) {
		$.each( json.qualifiers, function( i, qualifier ) {
			qualifiers.push( wb.Snak.newFromJSON( qualifier ) );
		} );
	}

	if ( isStatement && json.references !== undefined ) {
		$.each( json.references, function( i, reference ) {
			references.push( wb.Snak.newFromJSON( reference ) );
		} );
	}

	if ( isStatement ) {
		return new wb.Statement( mainSnak, qualifiers, references );
	}
	else {
		return new wb.Claim( mainSnak, qualifiers );
	}
};

}( wikibase, jQuery ) );
