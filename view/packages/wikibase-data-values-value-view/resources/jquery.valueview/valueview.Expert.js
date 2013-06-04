/**
 * Abstract base widget for editing and representing data values and a factory for defining
 * more concrete implementations of that widget, similar to jQuery.widget.
 *
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, vp, $, vv ) {
	'use strict';

	/**
	 * Creates a new expert definition as it is required within jQuery.datatypes.valueview to allow
	 * usage of a certain data value type or data type.
	 *
	 * NOTE: Just by defining a new expert here, the expert won't be available in the valueview
	 *       widget automatically. The expert has to be registered in a jQuery.valueview.ExpertFactory
	 *       instance which has to be used as expert provider in the valueview widget's options.
	 *
	 * @since 0.1
	 *
	 * @param {string} name Should be all-lowercase and without any special characters. Will be used
	 *        in within some DOM class attributes and
	 * @param {Function} [base] Constructor of the expert the new expert should be based on.
	 *        By default this is jQuery.valueview.Expert.
	 * @param {Object} expertDefinition Definition of the expert.
	 *
	 * @return {jQuery.valueview.Expert} the new expert constructor.
	 */
	vv.expert = function( name, base, expertDefinition ) {
		if( !expertDefinition ) {
			expertDefinition = base;
			base = vv.Expert;
		}
		else if( !$.isFunction( base ) ) {
			throw new Error( 'The expert\'s base must be a constructor function' );
		}

		// do actual inheritance from base and apply custom definition:
		var Expert = dv.util.inherit(
			'ValueviewExpert_' + name,
			base,
			$.extend( expertDefinition, {
				uiBaseClass: 'valueview-expert-' + name
			} )
		);
		return Expert;
	};

	/**
	 * Abstract class for strategies used in jQuery.valueview.valueview for displaying and handling
	 * a certain type of data value or data values of a certain data type.
	 *
	 * NOTE: Consider using jQuery.valueview.expert to define a new expert instead of inheriting
	 *       from this base directly.
	 *
	 * @since 0.1
	 *
	 * @param {HTMLElement|jQuery} viewPortNode
	 * @param {jQuery.valueview.ViewState} relatedViewState
	 * @param {dv.util.Notifier} [valueViewNotifier] Required so the expert can notify the valueview
	 *        about certain events. The following notification keys can be used:
	 *        - change: will be sent when raw value displayed by the expert changes. Either by a
	 *                  user action or by calling the rawValue() method. First parameter is a
	 *                  reference to the Expert itself.
	 * @param {Object} [options={}]
	 *
	 * TODO: think about whether there should be a function to add multiple notifiers for widget
	 *       developers or whether they should rather listen to the valueview widget while the
	 *       experts can't be touched. Less performant alternative would be to use DOM events.
	 *
	 * @constructor
	 * @abstract
	 */
	vv.Expert = function( viewPortNode, relatedViewState, valueViewNotifier, options ) {
		if( !( relatedViewState instanceof vv.ViewState ) ) {
			throw new Error( 'No ViewState object was provided to the valueview expert' );
		}

		if( !valueViewNotifier ) {
			valueViewNotifier = dv.util.Notifier();
		}
		else if( !( valueViewNotifier instanceof dv.util.Notifier ) ) {
			throw new Error( 'No Notifier object was provided to the valueview expert' );
		}

		if( viewPortNode instanceof $
			&& viewPortNode.length === 1
		) {
			viewPortNode = viewPortNode[0];
		}

		if( !( viewPortNode.nodeType ) ) { // IE8 can't check for instanceof HTMLELement
			throw new Error( 'No sufficient DOM node provided for the valueview expert' );
		}

		this._viewState = relatedViewState;
		this._viewNotifier = valueViewNotifier;

		this.$viewPort = $( viewPortNode );
		this.$viewPort.addClass( this.uiBaseClass );

		this._options = $.extend( {}, options || {} );

		this._init();
	};

	vv.Expert.prototype = {
		/**
		 * A unique UI class for this Expert definition. Should be used to prefix classes on DOM
		 * nodes within the Expert's view port. If a new expert definition will be created using
		 * jQuery.valueview.Expert(), then this will be set by that function.
		 * @type String
		 */
		uiBaseClass: '',

		/**
		 * The DOM node which has to be updated by the draw() function. Displays current state
		 * and/or input elements for user interaction during valueview's edit mode.
		 * @type jQuery
		 */
		$viewPort: null,

		/**
		 * Object representing the state of the related valueview.
		 * @type jQuery.valueview.ViewState
		 */
		_viewState: null,

		/**
		 * Object for publishing changes to the outside.
		 * @type dv.util.Notifier
		 */
		_viewNotifier: null,

		/**
		 * The expert's options, received through the constructor.
		 * @type Object
		 */
		_options: null,

		/**
		 * Will be called initially for new expert instances.
		 *
		 * @since 0.1
		 */
		_init: function() {},

		/**
		 * Gets called when the valueview's destroy function is called.
		 *
		 * TODO: think about and document definition of this destroy. What is the destroy supposed
		 *  to do exactly? E.g. when having an expert responsible for displaying an input, should
		 *  the destroy leave the input or load an expert for displaying the value statically first?
		 *
		 * @since 0.1
		 */
		destroy: function() {
			this.$viewPort.removeClass( this.uiBaseClass );
			this.$viewPort = null;
			this._viewState = null;
		},

		/**
		 * Returns a parser suitable for parsing the raw value returned by rawValue().
		 *
		 * @since 0.1
		 * @abstract
		 *
		 * @return valueParsers.ValueParser
		 */
		parser: function() {
			return new vp.NullParser();
		},

		/**
		 * Returns an object offering information about the related valueview's current state.
		 * The expert reflects that state, so everything that is true for the related view, is also
		 * true for the expert (e.g. whether it is in edit mode or disabled).
		 *
		 * @since 0.1
		 *
		 * @return jQuery.valueview.ViewState
		 */
		viewState: function() {
			return this._viewState;
		},

		/**
		 * Will return the value in its most basic form. Basically what DataValue.getValue of the
		 * expert's related data value type would return. This should be the value in a way, a
		 * related value parser can process. Can return null if no value is set. Can also return a
		 * full DataValue object if the expert doesn't know or require the concept of parsing the
		 * value, so basically, the expert itself will do the job of parsing itself somehow and does
		 * not require an asynchronous job for doing so.
		 * If the first parameter is set, then the function will set the value instead of returning
		 * it. An incompatible value will be recognized as empty (same as null). If the given value
		 * is different from the current one, "change" will be notified to the change notifier.
		 *
		 * @since 0.1
		 *
		 * @param {*} [rawValue] If provided, the function will act as setter.
		 * @return {*|null|undefined} Returns null in case no or non-processable value is set.
		 *         Returns undefined if used as setter.
		 */
		rawValue: function( rawValue ) {
			var currentRawValue = this._getRawValue();

			if( rawValue === undefined ) { // GETTER:
				return currentRawValue;
			}

			// Only change value if different from current value:
			if( !this.rawValueCompare( currentRawValue, rawValue ) ) { // SETTER:
				this._setRawValue( rawValue );

				// rawValue might be a unknown value different from null which will end as null
				// nonetheless. If that is the case the value was null already, then this is not
				// a real update.
				if( currentRawValue !== null || this._getRawValue() !== null ) {
					this.draw();
					this._viewNotifier.notify( 'change', [ this ] );
				}
			}
		},

		/**
		 * Getter called by rawValue.
		 * @see jQuery.valueview.Expert.rawValue
		 *
		 * @since 0.1
		 * @abstract
		 *
		 * @return {*|null} Returns null for an empty value. Otherwise, depending on the expert, any
		 *         kind of value representing the user's input in its most basic form
		 */
		_getRawValue: dv.util.abstractMember,

		/**
		 * Setter called by rawValue. Does not have the responsibility to call draw() for actually
		 * displaying the value or doing the "change" notify. Should simply make sure that the
		 * _getRawValue function can return the value set here.
		 * @see jQuery.valueview.Expert.rawValue
		 *
		 * @param {*} rawValue
		 *
		 * @since 0.1
		 * @abstract
		 */
		_setRawValue: dv.util.abstractMember,

		/**
		 * Returns whether two given raw values can be considered equal or whether one given raw
		 * value is equal to the current one.
		 *
		 * NOTE: This should be overwritten by any expert implementation not dealing with basic JS
		 *       types or with DataValue objects.
		 *
		 * @since 0.1
		 *
		 * @param {*} value1
		 * @param {*} [value2] If not provided, this will be the expert's current value.
		 * @returns boolean
		 */
		rawValueCompare: function( value1, value2 ) {
			value2 = value2 !== undefined ? value2 : this._getRawValue();

			// If expert implementation is dealing with DataValues as raw values, use equal:
			if( value1 instanceof dv.DataValue ) {
				return value1.equals( value2 );
			}
			// Otherwise, assume we're dealing with basic types. If we're dealing with anything
			// else, the expert's implementation had to overwrite this!
			return value1 === value2;
		},

		/**
		 * Will set the raw value back to the related valueview value. Both values might be the same
		 * with the difference that the expert's value might have a change by the user which did not
		 * yet get parsed into a parser value which would then present the expert's current value in
		 * the valueview. As long as the expert's value isn't parsed by the valueview, the valueview
		 * will still return its old value. By calling this function, that current value will be
		 * displayed again by the expert. This will be done without the expert triggering a change
		 * of the current raw value to the valueview.
		 *
		 * @since 0.1
		 */
		resetValue: function() {
			var value = this._viewState.value();
			this._setRawValue( value ? value.getValue() : null );
			this.draw();
		},

		/**
		 * Will represent the current value of the valueview. The value is a DataValue of the type
		 * this Expert can handle, so only this Expert knows how to display it properly. If the
		 * valueview is in edit mode, this will also draw the user interface components for the user
		 * to interact (edit) the value.
		 *
		 * @since 0.1
		 * @abstract
		 */
		draw: dv.util.abstractMember,

		/**
		 * Will set the focus if there is some focusable input elements.
		 *
		 * @since 0.1
		 */
		focus: function() {},

		/**
		 * Makes sure that the focus will be removed from any focusable input elements.
		 *
		 * @since 0.1
		 */
		blur: function() {}
	};

}( dataValues, valueParsers, jQuery, jQuery.valueview ) );
