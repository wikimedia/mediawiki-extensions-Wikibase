/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, util ) {
	'use strict';

	$.wikibase = $.wikibase || {};
	$.wikibase.snakview = $.wikibase.snakview || {};
	$.wikibase.snakview.variations = $.wikibase.snakview.variations || {};

	/**
	 * Abstract base for all kinds of variations within jQuery.snakview for representing different
	 * kinds of PropertySnaks. These implementations are responsible for representing parts of a
	 * certain kind of PropertyValue Snak and offer a factory method for building an instance of
	 * that kind of Snak.
	 * When a jQuery snakview for PropertyValue Snaks enters edit mode, it will offer a selector for
	 * choosing the Snak type, e.g. "No Value", "Some Value", "Value" etc. When choosing one, an
	 * instance of the related jQuery.wikibase.snakview.variations.Variation will be loaded, displaying
	 * the essential part of that kind of Snak, which makes it different from other kinds of Snaks.
	 *
	 * @constructor
	 * @abstract
	 * @since 0.4
	 *
	 * @param {$.wikibase.snakview.ViewState} viewState Information about the related
	 *        snakview. This is required for rendering according to the view's current state.
	 * @param {jQuery} $viewPort A DOM node which serves as drawing surface for this variation's
	 *        output, this is where this variation can express its current state and/or display
	 *        input elements for user interaction.
	 * @param {wb.store.EntityStore} entityStore
	 * @param {wb.ValueViewBuilder} valueViewBuilder
	 *
	 * @event afterdraw: Triggered on the Variation object after drawing the variation.
	 *        (1) {jQuery.Event}
	 */
	var SELF = $.wikibase.snakview.variations.Variation =
		function WbSnakviewVariationsVariation( viewState, $viewPort, entityStore, valueViewBuilder )
	{
		if( !( viewState instanceof $.wikibase.snakview.ViewState ) ) {
			throw new Error( 'No ViewState object was provided to the snakview variation' );
		}
		if( !( $viewPort instanceof $ ) || $viewPort.length !== 1 ) {
			throw new Error( 'No sufficient DOM node provided for the snakview variation' );
		}

		this._entityStore = entityStore;
		this._valueViewBuilder = valueViewBuilder;
		this._viewState = viewState;

		this.$viewPort = $viewPort;
		this.$viewPort.addClass( this.variationBaseClass );

		this._init();
	};
	$.extend( SELF.prototype, {
		/**
		 * A unique class for this variation. Will be set by the variations factory when creating a
		 * new variation definition.
		 * @type String
		 */
		variationBaseClass: null,

		/**
		 * The constructor of the variation's related kind of Snak. Will be set by the variations
		 * factory when creating a new variation definition.
		 * @type wb.datamodel.Snak
		 */
		variationSnakConstructor: null,

		/**
		 * The DOM node which has to be updated by the draw() function. Displays current state
		 * and/or input elements for user interaction during snakview's edit mode.
		 * @type jQuery
		 */
		$viewPort: null,

		/**
		 * @type wb.store.EntityStore
		 */
		_entityStore: null,

		/**
		 * @type {wikibase.ValueViewBuilder}
		 */
		_valueViewBuilder: null,

		/**
		 * Object representing the state of the related snakview.
		 * @type $.wikibase.snakview.ViewState
		 */
		_viewState: null,

		/**
		 * Will be called initially for new variation instances.
		 *
		 * @since 0.4
		 */
		_init: function() {
			this._viewState.notify( 'valid' );
		},

		/**
		 * Gets called when the snakview's destroy function is called.
		 *
		 * @since 0.4
		 */
		destroy: function() {
			this.$viewPort.removeClass( this.variationBaseClass );
			this.$viewPort = null;
			this._viewState = null;
		},

		/**
		 * Returns an object offering information about the related snakview's current state.
		 *
		 * @since 0.4
		 *
		 * @return $.wikibase.snakview.ViewState
		 */
		viewState: function() {
			return this._viewState;
		},

		/**
		 * Will set or return the value of the variation's part of the Snak.
		 *
		 * @since 0.4
		 *
		 * @param {Object} [value]
		 * @return {Object|undefined} Plain Object with parts of the Snak specific to the variation's
		 *         kind of Snak. Equivalent to what wb.datamodel.Snak.toMap() would return, just without the
		 *         basic fields 'snaktype' and 'property'.
		 *         undefined in case value() is called to set the value.
		 */
		value: function( value ) {
			if( value === undefined ) {
				return this._getValue();
			}
			this._setValue( value );
		},

		/**
		 * Setter for value(). Does not trigger draw() but value( value ) will trigger draw().
		 * Receives an Object which holds fields of the part of the Snak the variation is handling.
		 * The fields are the same as wb.datamodel.Snak.toMap() would provide them. The 'property' and
		 * 'snaktype' fields will not be provided, they can be received per viewState().property()
		 * and viewState().snakType() if necessary. If a field is missing, this means that the
		 * aspect of the Snak has not been defined yet, the view should then display a useful
		 * message or, in edit-mode, show empty input forms for user interaction.
		 *
		 * @since 0.4
		 *
		 * @param {Object} value
		 */
		_setValue: function( value ) {},

		/**
		 * Getter for value(). Should return the aspects of the Snak which the variation is taking
		 * care of. Should be an Object with fields as the toMap() function of the related Snak
		 * would return. For aspects of the Snak not defined yet, the related field should hold null.
		 *
		 * @since 0.4
		 *
		 * @return Object
		 */
		_getValue: function() {
			return {};
		},

		/**
		 * Will change the view to display a certain data value. If the DOM to represent a value is
		 * not yet inserted, this will take care of its insertion.
		 *
		 * @since 0.4
		 *
		 * @triggers afterdraw
		 */
		draw: util.abstractMember,

		/**
		 * Start the variation's edit mode.
		 */
		startEditing: function() {},

		/**
		 * Stops the variation's edit mode.
		 *
		 * @param {boolean} dropValue
		 */
		stopEditing: function( dropValue ) {},

		/**
		 * Will set the focus if there is some focusable input object.
		 *
		 * @since 0.4
		 */
		focus: function() {},

		/**
		 * Makes sure that the focus will be removed from any focusable input object.
		 *
		 * @since 0.4
		 */
		blur: function() {}
	} );

}( jQuery, util ) );
