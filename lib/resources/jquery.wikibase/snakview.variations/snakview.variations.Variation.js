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
	 * @param {jQuery} $viewPort A DOM node which serves as drawing surface for this variation's
	 *        output, this is where this variation can express its current state and/or display
	 *        input elements for user interaction.
	 */
	var SELF = jQuery.wikibase.snakview.variations.Variation = function( $viewPort ) {
		if( !( $viewPort instanceof $ ) || $viewPort.length !== 1 ) {
			throw new Error( 'No sufficient DOM node provided for the snakview variation' );
		}
		this.$viewPort = $viewPort;
	};
	SELF.prototype = {
		/**
		 * The DOM node which has to be updated by the draw() function. Displays current state
		 * and/or input elements for user interaction during snakview's edit mode.
		 * @type jQuery
		 */
		$viewPort: null,

		/**
		 * Gets called when the snakview's destroy function is called.
		 *
		 * @since 0.4
		 */
		destroy: function() {
			this.$viewPort = null;
		},

		/**
		 * Creates a Snak object with the necessary information from the snakview as well as the
		 * individual, specific information encapsulated in this object.
		 * Returns null if there is no sufficient information for building a Snak object.
		 *
		 * @since 0.4
		 *
		 * @param {String} propertyId
		 * @return wb.Snak|null
		 */
		newSnak: wb.utilities.abstractFunction,

		/**
		 * Will change the view to display a certain data value. If the DOM to represent a value is
		 * not yet inserted, this will take care of its insertion.
		 *
		 * @since 0.4
		 *
		 * @param {Boolean} inEditMode
		 * @param {String} propertyId The property ID selected in the snakview.
		 * @param {wb.Snak|null} snak Can be null if the Snak is not yet fully constructed by the
		 *        Snakview. This can happen since this is part of the Snakview, only the property
		 *        can be guaranteed at this point. If the full Snak is given already, then also this
		 *        part of the view has to display the respective information properly in edit as
		 *        well as in non-edit mode.
		 */
		draw: wb.utilities.abstractFunction,

		/**
		 * Will set the focus if there is some focusable input object.
		 *
		 * @since 0.4
		 */
		focus: wb.utilities.abstractFunction,

		/**
		 * Makes sure that the focus will be removed from any focusable input object.
		 *
		 * @since 0.4
		 */
		blur: wb.utilities.abstractFunction
	};

}( mediaWiki, wikibase, jQuery ) );
