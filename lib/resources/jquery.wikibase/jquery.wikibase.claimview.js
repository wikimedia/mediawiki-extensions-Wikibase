/**
 * @file
 * @ingroup Wikibase
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
		} ).snakview( {
			'value': this._claim === null ? null : this._claim.getMainSnak()
		} );

		this.element.append( this.$mainSnakView );

		// toolbar for edit group:
		var toolbar = new wb.ui.Toolbar();

		// give the toolbar an edit group with basic edit commands:
		toolbar.editGroup = new wb.ui.Toolbar.EditGroup();
		toolbar.addElement( toolbar.editGroup );
		toolbar.appendTo( this.element );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass );
		$.widget.prototype.destroy.call( this );
	},

	/**
	 * Returns the current Snak represented by the view, also allows to set the view to represent a
	 * given Snak.
	 *
	 * @return {wb.Snak}
	 */
	value: function( value ) {
		if( value === undefined ) {
			return this._getValue();
		}
		if( !( value instanceof wb.Snak ) ) {
			throw new Error( 'The given value has to be an instance of wikibase.Snak' );
		}
		return this._setValue( value );
	},

	/**
	 * @see jQuery.widget._setOption
	 * We are using this to disallow changing the value option afterwards
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}
		$.widget.prototype._setOption.call( key, value );
	},

	/**
	 * Returns the current Snak represented by the view.
	 *
	 * @return wb.Snak
	 */
	_getValue: function() {
		if( ! this._getValueView() ) {
			return null;
		}
		var propertyId = this.$propertySelector.entityselector( 'selectedEntity' ).id;
		var dataValue = this._getValueView().value();
		return new wb.PropertyValueSnak( propertyId, dataValue );
	}
} );

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
