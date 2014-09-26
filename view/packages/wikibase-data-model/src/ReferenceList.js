/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Ordered set of Reference objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.Reference[]} [references]
 */
var SELF = wb.datamodel.ReferenceList = function WbDataModelReferenceList( references ) {
	references = references || [];

	this._references = [];
	this.length = 0;

	for( var i = 0; i < references.length; i++ ) {
		this.addReference( references[i] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.datamodel.Reference[]}
	 */
	_references: null,

	/**
	 * @type {number}
	 */
	length: 0,

	/**
	 * @return {wikibase.datamodel.Reference[]}
	 */
	toArray: function() {
		return this._references.slice();
	},

	/**
	 * @param {wikibase.datamodel.Reference} reference
	 * @return {boolean}
	 */
	hasReference: function( reference ) {
		for( var i = 0; i < this._references.length; i++ ) {
			if(
				reference.equals( this._references[i] )
				&& reference.getHash() === this._references[i].getHash()
			) {
				return true;
			}
		}
		return false;
	},

	/**
	 * @param {wikibase.datamodel.Reference} reference
	 */
	addReference: function( reference ) {
		if( !( reference instanceof wb.datamodel.Reference ) ) {
			throw new Error( 'ReferenceList may contain Reference instances only' );
		}

		this._references.push( reference );
		this.length++;
	},

	/**
	 * @param {wikibase.datamodel.Reference} reference
	 */
	removeReference: function( reference ) {
		for( var i = 0; i < this._references.length; i++ ) {
			if(
				this._references[i].equals( reference )
				&& this._references[i].getHash() === reference.getHash()
			) {
				this._references.splice( i, 1 );
				this.length--;
				return;
			}
		}
		throw new Error( 'Trying to remove a non-existing reference' );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @param {*} referenceList
	 * @return {boolean}
	 */
	equals: function( referenceList ) {
		if( referenceList === this ) {
			return true;
		} else if( !( referenceList instanceof SELF ) || this.length !== referenceList.length ) {
			return false;
		}

		for( var i = 0; i < this._references.length; i++ ) {
			if( referenceList.indexOf( this._references[i] ) !== i ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {wikibase.datamodel.Reference} reference
	 * @return {number}
	 */
	indexOf: function( reference ) {
		for( var i = 0; i < this._references.length; i++ ) {
			if(
				this._references[i].equals( reference )
				&& this._references[i].getHash() === reference.getHash()
			) {
				return i;
			}
		}
		return -1;
	}

} );

}( wikibase, jQuery ) );
