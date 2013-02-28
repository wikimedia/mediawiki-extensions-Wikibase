/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, $ ) {
'use strict';

/**
 * Represents a Wikibase Claim in JavaScript.
 * @constructor
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @param {wb.Snak} mainSnak
 * @param {wb.SnakList|null} [qualifiers]
 * @param {String|null} [guid] The Global Unique Identifier of this Claim. Can be omitted or null
 *        if this is a new Claim, not yet stored in the database and associated with some entity.
 */
wb.Claim = function WbClaim( mainSnak, qualifiers, guid ) {
	this.setMainSnak( mainSnak );
	this.setQualifiers( qualifiers || new wb.SnakList() );
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
	 * @type String|null
	 */
	_guid: null,

	/**
	 * Returns the GUID (Global Unique Identifier) of the Claim. Returns null if the claim is not
	 * yet stored in the database.
	 * @since 0.3
	 *
	 * @return String|null
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
	 * Returns all qualifiers as a wb.SnakList object.
	 *
	 * @return wb.SnakList
	 */
	getQualifiers: function() {
		return this._qualifiers;
	},

	/**
	 * Overwrites the current set of qualifiers.
	 *
	 * @param {wb.SnakList} qualifiers
	 */
	setQualifiers: function( qualifiers ) {
		if( !( qualifiers instanceof wb.SnakList ) ) {
			throw new Error( 'Qualifiers have to be a wb.SnakList object' );
		}
		this._qualifiers = qualifiers;
	},

	/**
	 * Returns whether this Claim is equal to another Claim. Two Claims are considered equal
	 * if they are of the same type and have the same value. The value does not include the guid,
	 * so Claims with the same value but different guids are still considered equal.
	 *
	 * @since 0.4
	 *
	 * @param {wb.Claim|*} claim If this is not a wb.Claim, false will be returned.
	 * @return boolean
	 */
	equals: function( claim ) {
		return this === claim
			|| ( // snaks have no IDs, so we don't have to worry about comparing any
				claim instanceof wb.Claim
				&& this._mainSnak.equals( claim.getMainSnak() )
				&& this._qualifiers.equals( claim.getQualifiers() )
			);
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
		qualifiers = new wb.SnakList(),
		references = [],
		rank,
		guid,
		isStatement = json.type === 'statement';

	if ( json.qualifiers !== undefined ) {
		$.each( json.qualifiers, function( i, qualifier ) {
			qualifiers.addSnak( wb.Snak.newFromJSON( qualifier ) );
		} );
	}

	if ( isStatement && json.references !== undefined ) {
		$.each( json.references, function( i, reference ) {
			references.push( wb.Reference.newFromJSON( reference ) );
		} );
	}

	guid = json.id || null;

	if ( isStatement ) {
		rank = wb.Statement.RANK[ json.rank.toUpperCase() ];
		return new wb.Statement( mainSnak, qualifiers, references, rank, guid );
	}
	return new wb.Claim( mainSnak, qualifiers, guid );
};

}( wikibase, jQuery ) );
