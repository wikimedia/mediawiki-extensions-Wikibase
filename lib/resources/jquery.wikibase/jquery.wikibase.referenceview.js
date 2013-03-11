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
	 * Reference object represented by this view.
	 * @type {wb.Reference}
	 */
	_reference: null,

	/**
	 * @see jQuery.wikibase.snaklistview._create
	 */
	_create: function() {
		if ( this.option( 'value' ) ) {
			this._reference = this.option( 'value' );
			// Overwrite the value since the parent snaklistview widget require a wb.SnakList
			// object:
			this.options.value = this._reference.getSnaks();
		}
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
	 * Sets/Returns the current reference represented by the view. In case of an empty reference
	 * view, without any snak values set yet, null will be returned.
	 * @see jQuery.wikibase.snaklistview.value
	 * @since 0.4
	 *
	 * @param {wb.Reference} [reference] New reference to be set
	 * @return {wb.Reference|null}
	 */
	value: function( reference ) {
		if ( reference ) {
			if ( !( value instanceof wb.Reference ) ) {
				throw new Error( 'Value has to be an instance of wikibase.Reference' );
			}
			this._reference = reference;
			return this._reference;
		} else {
			var snakList = PARENT.prototype.value.call( this );

			if ( this._reference ) {
				return new wb.Reference( snakList || [], this._reference.getHash() );
			} else if ( snakList ) {
				return new wb.Reference( snakList );
			} else {
				return null;
			}
		}
	},

	/**
	 * Forwards to _saveReferenceApiCall implementing the abstract method of
	 * jquery.wikibase.snaklistview.
	 * @see jQuery.wikibase.snaklistview._saveSnakList
	 *
	 * @return {jQuery.promise}
	 */
	_saveSnakList: function() {
		return this._saveReferenceApiCall();
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
			self._snakList = self._reference.getSnaks();
			self._updateReferenceHashClass( savedReference );
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
