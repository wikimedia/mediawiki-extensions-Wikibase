/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
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
var SELF = wb.Claim = function WbClaim( mainSnak, qualifiers, guid ) {
	this.setMainSnak( mainSnak );
	this.setQualifiers( qualifiers || new wb.SnakList() );
	this._guid = guid || null;
};

/**
 * String to identify if the object is a statement or a claim.
 * @since 0.4
 * @type {string}
 */
SELF.TYPE = 'claim';

$.extend( SELF.prototype, {
	/**
	 * @type wb.Snak
	 */
	_mainSnak: null,

	/**
	 * @type {wb.SnakList}
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
	 * @param {wb.Claim|*} other If this is not a wb.Claim, false will be returned.
	 * @return boolean
	 */
	equals: function( other ) {
		return this === other
			|| ( // snaks have no IDs, so we don't have to worry about comparing any
				other instanceof this.constructor
				&& this._mainSnak.equals( other.getMainSnak() )
				&& this._qualifiers.equals( other.getQualifiers() )
			);
	},

	/**
	 * Returns a JSON structure representing this claim.
	 * @since 0.4
	 *
	 * TODO: implement this as a wb.serialization.Serializer
	 *
	 * @return {Object}
	 */
	toJSON: function() {
		var json = {
			type: this.constructor.TYPE,
			mainsnak: this._mainSnak.toJSON()
		};

		if ( this._guid ) {
			json.id = this._guid;
		}

		if ( this._qualifiers ) {
			json.qualifiers = this._qualifiers.toJSON();
		}

		return json;
	}
} );

/**
 * Creates a new Claim object from a given JSON structure.
 *
 * TODO: implement this as a wb.serialization.Unserializer
 *
 * @param {Object} json
 * @return {wb.Claim}
 */
SELF.newFromJSON = function( json ) {
	var mainSnak = wb.Snak.newFromJSON( json.mainsnak ),
		qualifiers = new wb.SnakList(),
		references = [],
		rank,
		guid,
		isStatement = json.type === 'statement';

	if ( json.qualifiers !== undefined ) {
		qualifiers = wb.SnakList.newFromJSON( json.qualifiers );
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
