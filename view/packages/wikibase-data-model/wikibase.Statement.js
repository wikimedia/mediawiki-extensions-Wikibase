/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, $, undefined ) {
'use strict';

var PARENT = wb.Claim,
	constructor = function( mainSnak, qualifiers, references, rank, guid ) {
		PARENT.call( this, mainSnak, qualifiers, guid );
		this.setReferences( references || [] );
		this.setRank( rank === undefined ? wb.Statement.RANK.NORMAL : rank );
	};

/**
 * Represents a Wikibase Statement in JavaScript.
 * @constructor
 * @extends wb.Claim
 * @since 0.3
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @param {wb.Snak} mainSnak
 * @param {wb.Snak[]} [qualifiers]
 * @param {wb.Reference[]} [references] An array of references or an empty array
 * @param {Number} [rank]
 * @param {String|null} [guid] The Global Unique Identifier of this Statement. Can be omitted or null
 *        if this is a new Statement, not yet stored in the database and associated with some entity.
 */
wb.Statement = wb.utilities.inherit( 'WbStatement', PARENT, constructor, {
	/**
	 * @type {wb.Reference[]}
	 * @todo think about implementing a ReferenceList/ClaimList rather than having an Array here
	 */
	_references: null,

	/**
	 * @see wb.Statement.RANK
	 * @type Number
	 */
	_rank: null,

	/**
	 * Returns all of the statement's references.
	 *
	 * sufficient
	 * @return {wb.Reference[]|null} An array of references or an empty array.
	 */
	getReferences: function() {
		return this._references;
	},

	/**
	 * Overwrites the current set of the statements references.
	 *
	 * @param {wb.Reference[]} references An array of references or an empty array.
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
	 * @return {Number} one of the wb.Statement.RANK enum
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
		for( var i in wb.Statement.RANK ) {
			if( wb.Statement.RANK[i] === rank ) {
				this._rank = rank;
				return;
			}
		}
		throw new Error( 'Can not set unknown Statement rank "' + rank + '"' );
	},

	/**
	 * Returns whether this statement is equal to another statement.
	 * @see wb.Claim.equals
	 * @since 0.4
	 *
	 * @param {wb.Statement} statement
	 * @return {boolean}
	 */
	equals: function( statement ) {
		if ( this === statement ) {
			return true;
		} else if (
			!( statement instanceof wb.Statement )
			|| !PARENT.prototype.equals.call( this, statement )
			|| this._references.length !== statement.getReferences().length
			|| this._rank !== statement.getRank()
		) {
			return false;
		}

		// Check if references are equal:
		var equals = true;
		$.each( this._references, function( myI, myReference ) {
			var found = false;
			$.each( statement.getReferences(), function( yourI, yourReference ) {
				if ( myReference.equals( yourReference ) ) {
					found = true;
					return false;
				}
			} );
			if ( !found ) {
				equals = false;
				return false;
			}
		} );

		return equals;
	},

	/**
	 * Returns a JSON structure representing this statement.
	 * @since 0.4
	 *
	 * @return {Object}
	 */
	toJSON: function() {
		var self = this,
			json = PARENT.prototype.toJSON.call( this );

		json.type = wb.Statement.TYPE;

		if ( this._references && this._references.length > 0 ) {
			json.references = [];
			$.each( this._references, function( i, reference ) {
				json.references.push( reference.toJSON() );
			} );
		}

		if ( this._rank ) {
			$.each( wb.Statement.RANK, function ( rank, i ) {
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
wb.Statement.RANK = {
	PREFERRED: 2,
	NORMAL: 1,
	DEPRECATED: 0
};

/**
 * @see wb.Claim.TYPE
 */
wb.Statement.TYPE = 'statement';

}( wikibase, jQuery ) );
