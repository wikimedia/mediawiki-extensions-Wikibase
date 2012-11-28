/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, dv, dt, $ ) {
	'use strict';

/**
 * View for displaying and editing Wikibase Snaks.
 * @since 0.3
 *
 * @option {wb.Claim|null} value The claim displayed by this view. This can only be set initially,
 *         the value function doesn't work as a setter in this view. If this is null, this view will
 *         start in edit mode, allowing the user to define the claim.
 */
$.widget( 'wikibase.claimview', {
	widgetName: 'wikibase-snakview',
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
	$mainSnakView: null,

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
		this.element.empty();

		this._claim = this.option( 'value' );
		this.element.addClass( this.widgetBaseClass );

		this.$mainSnakView = $( '<div/>', {
			'class': this.widgetBaseClass + '-mainsnak'
		} ).appendTo(
			this.element // append before initialization, so events can bubble from the beginning
		).snakview( {
			'value': this._claim === null ? null : this._claim.getMainSnak()
		} );

		// toolbar for edit group:

		this._createToolbar();
	},

	/**
	 * Inserts the toolbar for editing the main snak of the claim.
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

		if( this.$mainSnakView.snakview( 'isInEditMode' ) ) {
			toolbar.editGroup.toEditMode();
		}

		toolbar.editGroup.on( 'edit', function( e ) {
			self.$mainSnakView.snakview( 'startEditing' );
			e.preventDefault(); // don't auto-transform toolbar
		} );

		toolbar.editGroup.on( 'cancel', function( e ) {
			self.$mainSnakView.snakview( 'cancelEditing' );
			e.preventDefault(); // don't auto-transform toolbar
		} );

		toolbar.editGroup.on( 'save', function( e ) {
			self.$mainSnakView.snakview( 'stopEditing' );
			e.preventDefault(); // don't auto-transform toolbar
		} );

		this.$mainSnakView.on( 'snakviewstartediting', function( e ) {
			toolbar.editGroup.toEditMode();
		} );

		this.$mainSnakView.on( 'snakviewstopediting', function( e, cancel ) {
			// update the definition of the Claim represented by this claimview:
			var mainSnak = self.$mainSnakView.snakview( 'value' );
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

		toolbar.appendTo( this.element );
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

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
