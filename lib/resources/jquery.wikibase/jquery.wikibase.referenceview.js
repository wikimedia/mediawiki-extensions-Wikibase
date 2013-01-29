/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.wikibase.claimview;

/**
 * View for displaying and editing Wikibase Statements.
 *
 * @since 0.4
 * @extends jQuery.wikibase.claimview
 */
$.widget( 'wikibase.referenceview', PARENT, {
	widgetName: 'wikibase-referenceview',
	widgetBaseClass: 'wb-referenceview',

	options: {
		template: 'wb-reference',
		templateParams: [
			'.', // snaks
			''  // edit section DOM
		],
		templateShortCuts: {
			'$mainSnak': '.wb-reference-snaks',
			'$toolbar': '.wb-claim-toolbar'
		}
	},

	/**
	 * @see jQuery.claimview._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );
	},

	/**
	 * Returns the current Reference represented by the view. In case of an empty reference view,
	 * without any snak values set yet, null will be returned.
	 * @since 0.4
	 *
	 * @return {wb.Reference|null}
	 */
	value: function() {
		// since we inherit from claimview, internal _claim will hold what we got in 'value' option.
		var reference = this._claim;

		if( !reference || reference.getSnaks().length === 0 ) {
			return null;
		}
		return reference;
	},

	/**
	 * Will return one Snak of the reference. Right now we only support one Snak per reference. As
	 * soon as we change this, the whole widget structure should change anyhow, not inheriting from
	 * the claimview widget anymore.
	 *
	 * @see jQuery.wikibase.claimview.mainSnak
	 */
	mainSnak: function() {
		return this.value() && this.value().getSnaks().toArray()[0] || null;
	},

	/**
	 * We abuse (overwrite) this function to save the reference (which does only consist out of one
	 * Snak currently)
	 *
	 * @see jQuery.wikibase.claimview._saveMainSnak
	 */
	_saveMainSnak: function() {
		if( !this.value() ) {
			throw new Error( 'Can\'t save a new Reference' );
		}
		// store changed value of Claim's Main Snak:
		var self = this,
			reference = this.value(),
			statementGuid = this.option( 'statementGuid' ),
			api = new wb.RepoApi(),
			revStore = wb.getRevisionStore(),
			snakList;

		if( this.$mainSnak.snakview( 'snak' ) ) {
			snakList = new wb.SnakList( this.$mainSnak.snakview( 'snak' ) );
		}

		return api.setReference(
			statementGuid,
			snakList,
			revStore.getClaimRevision( statementGuid ),
			reference.getHash()
		).done( function( savedReference, pageInfo ) {
			// update revision store
			revStore.setClaimRevision( pageInfo.lastrevid, statementGuid );

			// update Reference so we have the new hash for next edit!
			self._claim = savedReference;
		} );
	}
} );

}( mediaWiki, wikibase, jQuery ) );
