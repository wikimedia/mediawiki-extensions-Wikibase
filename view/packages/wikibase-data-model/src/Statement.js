/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, util, $ ) {
'use strict';

var PARENT = wb.datamodel.Claim,
	constructor = function( mainSnak, qualifiers, references, rank, guid ) {
		PARENT.call( this, mainSnak, qualifiers, guid );
		this.setReferences( references || [] );
		this.setRank( rank === undefined ? wb.datamodel.Statement.RANK.NORMAL : rank );
	};

/**
 * Represents a Wikibase Statement in JavaScript.
 * @constructor
 * @extends wb.datamodel.Claim
 * @since 0.3
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @param {wb.datamodel.Snak} mainSnak
 * @param {wb.datamodel.Snak[]} [qualifiers]
 * @param {wb.datamodel.Reference[]} [references] An array of references or an empty array
 * @param {Number} [rank]
 * @param {String|null} [guid] The Global Unique Identifier of this Statement. Can be omitted or null
 *        if this is a new Statement, not yet stored in the database and associated with some entity.
 */
var SELF = wb.datamodel.Statement = util.inherit( 'WbStatement', PARENT, constructor, {
	/**
	 * @type {wb.datamodel.Reference[]}
	 * @todo think about implementing a ReferenceList/ClaimList rather than having an Array here
	 */
	_references: null,

	/**
	 * @see wb.datamodel.Statement.RANK
	 * @type Number
	 */
	_rank: null,

	/**
	 * Returns all of the statement's references.
	 *
	 * sufficient
	 * @return {wb.datamodel.Reference[]|null} An array of references or an empty array.
	 */
	getReferences: function() {
		return this._references;
	},

	/**
	 * Overwrites the current set of the statements references.
	 *
	 * @param {wb.datamodel.Reference[]} references An array of references or an empty array.
	 */
	setReferences: function( references ) {
		if( !$.isArray( references ) ) {
			throw new Error( 'References have to be an array' );
		}
		this._references = references;
	},

	/**
	 * Returns the rank of the statement.
	 *
	 * @return {Number} one of the wb.datamodel.Statement.RANK enum
	 */
	getRank: function() {
		return this._rank;
	},

	/**
	 * Allows to set the statements rank.
	 *
	 * @param {Number} rank One of the RANK enum
	 */
	setRank: function( rank ) {
		// check if given rank is a known rank, then set it. Otherwise, throw error!
		for( var i in SELF.RANK ) {
			if( SELF.RANK[i] === rank ) {
				this._rank = rank;
				return;
			}
		}
		throw new Error( 'Can not set unknown Statement rank "' + rank + '"' );
	},

	/**
	 * Returns whether this statement is equal to another statement.
	 * @see wb.datamodel.Claim.equals
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Statement|*} other
	 * @return {boolean}
	 */
	equals: function( other ) {
		if(
			!PARENT.prototype.equals.call( this, other )
			|| this._references.length !== other.getReferences().length
			|| this._rank !== other.getRank()
		) {
			return false;
		}

		// Check whether references are equal:
		var ownRefs = this._references,
			otherRefs = other.getReferences();

		checkOwnRefs: for( var i in ownRefs ) {
			var ownRef = ownRefs[i];

			for( var j in otherRefs ) {
				if( ownRef.equals( otherRefs[j] ) ) {
					continue checkOwnRefs;
				}
			}
			return false;
		}
		return true;
	},

	/**
	 * Returns a JSON structure representing this statement.
	 * @since 0.4
	 *
	 * TODO: implement this as a wb.datamodel.serialization.Serializer
	 *
	 * @return {Object}
	 */
	toJSON: function() {
		var self = this,
			json = PARENT.prototype.toJSON.call( this );

		if ( this._references && this._references.length > 0 ) {
			json.references = [];
			$.each( this._references, function( i, reference ) {
				json.references.push( reference.toJSON() );
			} );
		}

		if ( this._rank !== undefined ) {
			$.each( SELF.RANK, function( rank, i ) {
				if ( self._rank === i ) {
					json.rank = rank.toLowerCase();
					return false;
				}
			} );
		}

		return json;
	}

} );

/**
 * Rank enum. Higher values are more preferred.
 * @type Object
 */
SELF.RANK = {
	PREFERRED: 2,
	NORMAL: 1,
	DEPRECATED: 0
};

/**
 * @see wb.datamodel.Claim.TYPE
 */
SELF.TYPE = 'statement';

}( wikibase, util, jQuery ) );
