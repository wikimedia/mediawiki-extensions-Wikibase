/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.wikibase.snaklistview;

/**
 * View for displaying and editing Wikibase Statements.
 *
 * @since 0.4
 * @extends jQuery.wikibase.snaklistview
 */
$.widget( 'wikibase.referenceview', PARENT, {
	widgetBaseClass: 'wb-referenceview',

	/**
	 * @see jQuery.claimview._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );
		this._updateReferenceHashClass( this.value() );
	},

	/**
	 * Will update the 'wb-reference-<hash>' class on the widget's root element to a given
	 * reference's hash. If null is given or if the reference has no hash, 'wb-reference-new' will
	 * be added as class.
	 *
	 * @param {wb.Reference|null} reference
	 */
	_updateReferenceHashClass: function( reference ) {
		var refHash = reference && reference.getHash() || 'new';

		this.element.removeClassByRegex( /wb-reference-.+/ );
		this.element.addClass( 'wb-reference-' + refHash );

		this.element.removeClassByRegex( new RegExp( this.widgetBaseClass ) + '-.+' );
		this.element.addClass( this.widgetBaseClass + '-' + refHash );
	},

	/**
	 * Returns the current reference represented by the view. In case of an empty reference view,
	 * without any snak values set yet, null will be returned.
	 * @since 0.4
	 *
	 * @return {wb.Reference|null}
	 */
	value: function() {
		var self = this,
			snaks = [];

		$.each( this._listview.items(), function( i, item ) {
			var liInstance = self._listview.listItemAdapter().liInstance( $( item ) );
			if ( liInstance.snak() ) {
				snaks.push( liInstance.snak() );
			}
		} );

		if ( this._reference ) {
			return new wb.Reference( snaks, this._reference.getHash() );
		} else if ( snaks.length > 0 ) {
			return new wb.Reference( snaks );
		} else {
			return null;
		}
	},

	/**
	 * Triggers the API call to save the reference.
	 * @since 0.4
	 *
	 * @return {jQuery.promise}
	 */
	_saveReferenceApiCall: function() {
		var self = this,
			guid = this.option( 'statementGuid' ),
			api = new wb.RepoApi(),
			revStore = wb.getRevisionStore();

		return api.setReference(
			guid,
			this.value().getSnaks(),
			revStore.getClaimRevision( guid ),
			this.value().getHash()
		).done( function( savedReference, pageInfo ) {
			// update revision store
			revStore.setClaimRevision( pageInfo.lastrevid, guid );

			self._reference = savedReference;
			self._updateReferenceHashClass( savedReference );
		} );
	},

	/**
	 * Triggers the API call to remove the reference.
	 * @since 0.4
	 *
	 * TODO: same as for _saveMainSnakApiCall(), get API related stuff out of here!
	 *
	 * @return {jQuery.Promise}
	 */
	_removeReferenceApiCall: function() {
		var api = new wb.RepoApi(),
			guid = this.option( 'statementGuid' );

		return api.removeReferences(
			guid,
			this._reference.getHash(),
			wb.getRevisionStore().getClaimRevision( guid )
		).done( function( baseRevId ) {
			// update revision store
			wb.getRevisionStore().setClaimRevision( baseRevId, guid );
		} );
	}

} );

// Register toolbar:
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	widget: {
		name: 'wikibase.referenceview',
		prototype: $.wikibase.referenceview.prototype
	},
	options: {
		interactionWidgetName: $.wikibase.referenceview.prototype.widgetName,
		toolbarParentSelector: '.wb-snaklistview-toolbar'
	}
} );

}( mediaWiki, wikibase, jQuery ) );
