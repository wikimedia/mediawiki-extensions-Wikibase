/**
 * @file
 * @ingroup WikibaseLib
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
 * @param {wb.Snak[]} [qualifiers]
 * @param {String} [guid] The Global Unique Identifier of this Claim. This can be omitted if this
 *        is a new claim, not yet stored in the database and associated with some entity.
 */
wb.Claim = function( mainSnak, qualifiers, guid ) {
	this.setMainSnak( mainSnak );
	this.setQualifiers( qualifiers || [] );
	this._guid = guid || null;
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
	 * @type String
	 */
	_guid: null,

	/**
	 * Returns the GUID (Global Unique Identifier) of the Claim. Returns null if the claim is not
	 * yet stored in the database.
	 * @since 0.3
	 *
	 * @return String
	 */
	getGuid: function() {
		return this._guid;
	},

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
		if( !( mainSnak instanceof wb.Snak ) ) {
			throw new Error( 'For creating a new claim, at least a Main Snak is required' );
		}
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
		if( !$.isArray( qualifiers ) ) {
			throw new Error( 'Qualifiers have to be an array of snaks' );
		}
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
		rank,
		guid,
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

	guid = json.id || null;

	if ( isStatement ) {
		rank = wb.Statement.RANK[ json.rank.toUpperCase() ];
		return new wb.Statement( mainSnak, qualifiers, references, rank, guid );
	}
	else {
		return new wb.Claim( mainSnak, qualifiers, guid );
	}
};

}( wikibase, jQuery ) );
