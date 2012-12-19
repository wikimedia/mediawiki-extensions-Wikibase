/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

/**
 * View for displaying and editing Wikibase Claims.
 * @since 0.3
 *
 * @option value {wb.Claim|null} The claim displayed by this view. This can only be set initially,
 *         the value function doesn't work as a setter in this view. If this is null, this view will
 *         start in edit mode, allowing the user to define the claim.
 *
 * @option predefined {Object} Allows to pre-define certain aspects of the Claim to be created.
 *         Basically, when creating a new Claim, what really is created first is the Main Snak. So,
 *         this requires a field 'mainSnak' which can have all fields which can be defined in
 *         jQuery.snakview's option 'predefined'. E.g. "predefined.mainSnak.property = 'q42'"
 *         TODO: also allow pre-defining aspects of qualifiers. Implementation and whether this
 *               makes sense here might depend on whether we will have one or several edit buttons.
 *
 * @event remove: Triggered when removing the claim.
 *        (1) {jQuery.Event} event
 */
$.widget( 'wikibase.claimview', {
	widgetName: 'wikibase-claimview',
	widgetBaseClass: 'wb-claimview',

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		value: null,
		predefined: {
			mainSnak: false
		}
	},

	/**
	 * The node representing the main snak, displaying it in a jQuery.snakview
	 * @type jQuery
	 */
	$mainSnak: null,

	/**
	 * Node of the toolbar
	 * @type jQuery
	 */
	$toolbar: null,

	/**
	 * The claim represented by this view or null if this is a view for a user to enter a new claim.
	 * @type wb.Claim|null
	 */
	_claim: null,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this;
		this._claim = this.option( 'value' );
		this.element.addClass( this.widgetBaseClass );

		this.element.applyTemplate( 'wb-claim',
			'wb-last', // class: wb-first|wb-last
			this._claim // class='wb-claim-$2'
				? this._claim.getGuid()
				: 'new',
			'', // .wb-claim-mainsnak
			''  // edit section DOM
		);
		this.$mainSnak = this.element.find( '.wb-claim-mainsnak' );
		this.$toolbar = this.element.find( '.wb-claim-toolbar' );

		// set up event listeners:
		this.$mainSnak
		.on( 'snakviewstartediting', function( e ) {
			self.element.addClass( 'wb-edit' );
		} )
		.on( 'snakviewstopediting', function( e, dropValue ) {
			var mainSnak = self.$mainSnak.snakview( 'value' );

			if( dropValue || !mainSnak ) {
				// nothing to update
				self.element.removeClass( 'wb-edit' );
				return;
			}

			self.element.removeClass( 'wb-edit' );

			// update the definition of the Claim represented by this claimview  {
			if( !self._claim ){
				// claim must be newly entered, create a new claim:
				self._claim = new wb.Claim( mainSnak);
			} else {
				// existing claim changed, overwrite Main Snak:
				self._claim.setMainSnak( mainSnak);

				self._saveMainSnak()
				.done( function() {
					// transform toolbar and snak view after save complete:
					//self.$mainSnak.snakview( 'stopEditing' );
				} );
				// TODO: error handling
			}
		} );

		this.$mainSnak.snakview( {
			value: this._claim ? this._claim.getMainSnak() : null,
			predefined: this.option( 'predefined' ).mainSnak || {}
		} );

		// toolbar for edit group:
		this._createToolbar();
	},

	/**
	 * Inserts the toolbar for editing the main snak of the claim.
	 * @since 0.3
	 *
	 * TODO: depending on how we will proceed with making whole claims editable (having edit forms
	 *       for main snak and qualifiers at once or having one form per snak), we will have to use
	 *       this code in a different place.
	 * TODO: would be nice to get rid of the whole toolbar code in here, moving it into a separate
	 *       widget which can be used for interaction with editable widgets like the Snakview.
	 */
	_createToolbar: function() {
		var self = this,
			toolbar = new wb.ui.Toolbar();

		// give the toolbar an edit group with basic edit commands:
		toolbar.editGroup = new wb.ui.Toolbar.EditGroup( {
			displayRemoveButton: this._claim !== null // no remove button if not yet created
		} );
		toolbar.addElement( toolbar.editGroup );

		if( this.$mainSnak.snakview( 'isInEditMode' ) ) {
			toolbar.editGroup.toEditMode();
		}

		toolbar.editGroup.on( 'edit', function( e ) {
			self.$mainSnak.snakview( 'startEditing' );
			e.preventDefault(); // don't auto-transform toolbar
		} );

		toolbar.editGroup.on( 'cancel', function( e ) {
			self.$mainSnak.snakview( 'cancelEditing' );
			e.preventDefault(); // don't auto-transform toolbar
		} );

		toolbar.editGroup.on( 'save', function( e ) {
			self.$mainSnak.snakview( 'stopEditing' );
			e.preventDefault(); // don't auto-transform toolbar
		} );

		toolbar.editGroup.on( 'remove', function( e ) {
			var guid = self.value().getGuid(),
				api = new wb.Api(),
				revStore = wb.getRevisionStore();

			api.removeClaim(
				guid,
				revStore.getClaimRevision( guid )
			).done( function( savedClaim, pageInfo ) {
				// NOTE: we don't update rev store here! If we want uniqueness for Claims, this
				//  might be an issue at a later point and we would need a solution then

				// update model of represented Claim
				self._trigger( 'remove' );
				// TODO: not really nice because remove handling doesn't make much sense if a
				//       $.claimview would be used on its own
			} );
			// TODO: error handling
		} );

		this.$mainSnak.on( 'snakviewstartediting', function( e ) {
			toolbar.editGroup.toEditMode();
		} );

		this.$mainSnak.on( 'snakviewafterstopediting', function( e, cancel ) {
			toolbar.editGroup.toNonEditMode();
			// TODO: When adding a claim, the focus is re-set on the corresponding add button. This
			// might change in the future depending on how the interaction flow for adding a new
			// claim will be implemented. For the moment, do not focus the edit button when a new
			// value is being added since the focus will be re-set to the "add" button after the API
			// call adding the new claim has finished.
			if ( cancel || !$( e.target ).parent().hasClass( 'wb-claim-new' ) ) {
				toolbar.editGroup.btnEdit.setFocus();
			}
		} );

		// TODO: get rid of the editsection node!
		toolbar.appendTo( $( '<span/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbar ) );
	},

	/**
	 * Triggers the API call to save the current Main Snak.
	 * @since 0.4
	 *
	 * TODO: would be nice to have all API related stuff out of here to allow concentrating on
	 *       MVVM relation.
	 *
	 * @return jQuery.Promise
	 */
	_saveMainSnak: function() {
		if( !this.value() ) {
			throw new Error( 'Can\'t save Main Snak of non-existent Claim' );
		}
		// store changed value of Claim's Main Snak:
		var self = this,
			mainSnak = this.$mainSnak.snakview( 'value' ),
			guid = this.value().getGuid(),
			api = new wb.Api(),
			revStore = wb.getRevisionStore();

		return api.setClaimValue(
			guid,
			revStore.getClaimRevision( guid ),
			mainSnak
		).done( function( savedClaim, pageInfo ) {
			// update revision store
			revStore.setClaimRevision( pageInfo.lastrevid, savedClaim.getGuid() );

			// update model of represented Claim
			self._claim.setMainSnak( savedClaim.getMainSnak() );
		} );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass );

		this.$mainSnak.snakview( 'destroy' );

		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * Returns the current Claim represented by the view. If null is returned, than this is a
	 * fresh view where a new Claim is being constructed.
	 * @since 0.3
	 *
	 * @return {wb.Claim|null}
	 */
	value: function() {
		return this._claim;
	},

	/**
	 * @see jQuery.widget._setOption
	 * We are using this to disallow changing the value option afterwards
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}
		$.Widget.prototype._setOption.call( key, value );
	}
} );

}( mediaWiki, wikibase, jQuery ) );
