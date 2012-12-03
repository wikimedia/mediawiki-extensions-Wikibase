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
 * @option {wb.Claim|null} value The claim displayed by this view. This can only be set initially,
 *         the value function doesn't work as a setter in this view. If this is null, this view will
 *         start in edit mode, allowing the user to define the claim.
 */
$.widget( 'wikibase.claimview', {
	widgetName: 'wikibase-claimview',
	widgetBaseClass: 'wb-claimview',

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		value: null
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
		this._claim = this.option( 'value' );
		this.element.addClass( this.widgetBaseClass );

		this.element.applyTemplate( 'wb-claim',
			'wb-last', // class: wb-first|wb-last
			'', // claim-id
			'', // .wb-claim-mainsnak
			''  // edit section DOM
		);
		this.$mainSnak = this.element.find( '.wb-claim-mainsnak' );
		this.$toolbar = this.element.find( '.wb-claim-toolbar' );

		this.$mainSnak.snakview( {
			'value': this._claim === null ? null : this._claim.getMainSnak()
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
	 */
	_createToolbar: function() {
		var self = this,
			toolbar = new wb.ui.Toolbar();

		// give the toolbar an edit group with basic edit commands:
		toolbar.editGroup = new wb.ui.Toolbar.EditGroup();
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
			e.preventDefault(); // don't auto-transform toolbar

			if( self._claim === null ) {
				// no claim for which we can store changes
				self.$mainSnak.snakview( 'stopEditing' );
				return;
			}

			// store changed value of Claim's Main Snak:
			// TODO: this should probably go out of here, depending on the _createToolbar() TODO!
			var mainSnak = self.$mainSnak.snakview( 'value' ),
				api = new wb.Api();

			api.setClaimValue(
				self.value().getGuid(),
				0, // TODO: use a proper revision ID
				mainSnak
			).done( function( savedClaim ) {
				self._claim.setMainSnak( savedClaim.getMainSnak() );

				// transform toolbar and snak view after save complete:
				self.$mainSnak.snakview( 'stopEditing' );
				toolbar.editGroup.toNonEditMode();
			} );
			// TODO: error handling
		} );

		this.$mainSnak.on( 'snakviewstartediting', function( e ) {
			toolbar.editGroup.toEditMode();
		} );

		this.$mainSnak.on( 'snakviewstopediting', function( e, cancel ) {
			// update the definition of the Claim represented by this claimview:
			var mainSnak = self.$mainSnak.snakview( 'value' );
			if( mainSnak !== null ) {
				if( self._claim === null ) {
					// claim must be newly entered, create a new claim:
					self._claim = new wb.Claim( mainSnak );
				} else {
					// existing claim changed, overwrite main snak:
					self._claim.setMainSnak( mainSnak );
				}
			}
			toolbar.editGroup.toNonEditMode();
		} );

		// TODO: get rid of the editsection node!
		toolbar.appendTo( $( '<span/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbar ) );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass );
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
