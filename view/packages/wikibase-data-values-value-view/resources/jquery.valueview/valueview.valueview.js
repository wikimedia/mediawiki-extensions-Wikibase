/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, dt, vp, $ ) {
	'use strict';

	var PARENT = $.Widget;

	/**
	 * Helper for defining a valueview member function which will just call a valueview's Expert's
	 * member function and return the value received from that function. If the valueview does not have
	 * an expert currently, nothing will be done.
	 *
	 * @param {string} fnName Name of the function in jQuery.valueview.Expert
	 * @returns {Function}
	 */
	function expertProxy( fnName ) {
		return function() {
			if( this._expert ) {
				return this._expert[ fnName ].apply( this._expert, arguments );
			}
		};
	}

	/**
	 * valueview widget which is responsible for displaying and serving input for creating/changing
	 * data value objects (dataValue.DataValue). Depending on the data value type, the widget will
	 * choose a different strategy for handing interaction with a specific instance of that data value.
	 *
	 * @extends jQuery.Widget
	 * @since 0.1
	 *
	 * @option expertProvider {jQuery.valueview.ExpertFactory} (required) Used to determine an expert
	 *         strategy depending on the data value type or the data type the valueview should handle.
	 *         The valueview will be able to handle all data value types and data types the given
	 *         provider has experts registered for.
	 *
	 * @option valueParserProvider {valueParsers.valueParserFactory} (required) Factory providing the
	 *         parsers that values may be parsed with.
	 *
	 * @option valueFormatterProvider {valueFormatters.valueFormatterFactory} (required) Factory
	 *         providing the formatters which value may be formatted with.
	 *
	 * @option on {dataTypes.DataType|Function|null} If this option is not null, then the widget will
	 *         choose an expert (jQuery.valueview.Expert), a parser (valueParsers.ValueParser) and a
	 *         formatter (valueFormatters.ValueFormatter) based on the provided purpose. The purpose may
	 *         be a data type (dataTypes.DataType instance) or a data value (dataValues.DataValue
	 *         constructor).
	 *         When setting the valueview's value to a data value that is not valid against the given
	 *         data (value) type, a note that it is not suitable for the widget's current definition
	 *         will be displayed.
	 *         If the "on" option is null, expert, parser and formatter will be chosen on the widget's
	 *         current value. Consequently, if the value itself is null, the widget will not be able to
	 *         offer any input for new values.
	 *         Default: null
	 *
	 * @option value {dataValues.DataValue|null} The data value this view should represent initially.
	 *         If omitted, an empty view will be served, ready to take some input by the user. The value
	 *         can also be overwritten later, by using the value() function.
	 *         Default: null
	 *
	 * @option autoStartEditing {boolean} Whether or not view should go into edit mode by its own upon
	 *         initialization if its initial value is empty.
	 *         Default: true
	 *
	 * @option parseDelay {number} Time milliseconds that the parser should wait before parsing. A delay
	 *         is useful to limit the number of API request that are outdated when returning because the
	 *         input has changed in the meantime.
	 *         Default: 300
	 *
	 * @option mediaWiki {Object} mediaWiki JavaScript object that may be used in MediaWiki environment.
	 *         Default: null
	 *
	 * @event change: Triggered when the widget's value is updated.
	 *        (1) {jQuery.event} event
	 *
	 * @event parse: Triggered before the value gets parsed.
	 *       (1) {jQuery.event} event
	 *
	 * @event afterparse: Triggered after the value has been parsed.
	 *       (1) {jQuery.event} event
	 */
	$.widget( 'valueview.valueview', PARENT, {
		/**
		 * @see jQuery.Widget
		 */
		widgetBaseClass: 'valueview', // jQuery.widget would set this to 'valueview-valueview'

		/**
		 * Current, accepted value. Might be "behind" the expert's raw value until the raw value gets
		 * parsed and the parsed result set as the new accepted value.
		 * @type dv.DataValue|null
		 */
		_value: null,

		/**
		 * Most current formatted value. Might be "behind" the expert's raw value as well as the
		 * valueview's parsed DataValue since formatting might involve an asynchronous request.
		 * @type {string|jQuery|null}
		 */
		_formattedValue: null,

		/**
		 * The DOM node containing the actual value representation. This is the expert's viewport.
		 * @type jQuery
		 */
		$value: null,

		/**
		 * Value from before edit mode.
		 * @type dv.DataValue|null
		 */
		_initialValue: null,

		/**
		 * @type boolean
		 */
		_isInEditMode: false,

		/**
		 * Expert object responsible for serving the DOM to display the current value.
		 * Can be null if the current value has a has data value type unknown to the expert factory
		 * given in the "expertProvider" option. In this case the valueview will still work but only
		 * display a message instead of displaying the value and allowing the user to change it.
		 * @type jQuery.valueview.Expert|null
		 */
		_expert: null,

		/**
		 * Timeout id of the currently running setTimeout function that delays the parser API request.
		 * @type {number}
		 */
		_parseTimer: null,

		/**
		 * Default options
		 * @see jQuery.Widget.options
		 */
		options: {
			expertProvider: null,
			valueParserProvider: null,
			valueFormatterProvider: null,
			on: null,
			value: null,
			autoStartEditing: false,
			parseDelay: 300,
			mediaWiki: null
		},

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			// Build widget's basic dom:
			this.element.addClass( this.widgetBaseClass );
			this.$value = $( '<div/>', {
				'class': this.widgetBaseClass + '-value'
			} );
			this.element.append( this.$value );

			// Set initial value if provided in options:
			this.value( this.option( 'value' ) || null );

			if( this.option( 'autoStartEditing' ) && this.isEmpty() ) {
				// If no data value is represented, offer UI to build one.
				this.startEditing();
			}
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			// remove classes we added in this._createWidget() as well as others
			this.element.removeClass(
				this.widgetBaseClass + ' '
					+ this.widgetName + ' '
					+ this.widgetBaseClass + '-instaticmode '
					+ this.widgetBaseClass + '-ineditmode '
			);

			if( this._expert ) {
				this._expert.destroy();
				this._expert = null;
			}

			// TODO: destroying the expert will leave no plain text of the last value. Once we
			//  implemented formatters, think about leaving a formatted text here. On the other hand,
			//  formatting will be assynchronous which would be a problem in this situation.

			return PARENT.prototype.destroy.call( this );
		},


		/**
		 * @see jQuery.widget._setOption
		 * @triggers {Error} when trying to set an option that cannot be set after initialization.
		 */
		_setOption: function( key, value ) {
			switch( key ) {
				case 'autoStartEditing':
					// doesn't make sense to change this after initialization
					throw new Error( 'Can not change jQuery.valueview option "' + key
						+ '" after widget initialization' );
			}

			PARENT.prototype._setOption.call( this, key, value );

			switch( key ) {
				case 'expertProvider':
				case 'on': // TODO: make this work properly and test
					this._updateExpert();
					break;
				case 'value':
					// TODO
					break;
				default:
					break;
			}
		},

		/**
		 * When calling this, the view will transform into a form with input fields or advanced widgets
		 * for editing the related data value.
		 *
		 * @since 0.1
		 */
		startEditing: function() {
			if( this.isInEditMode() ) {
				return; // return nothing to allow chaining
			}
			this._initialValue = this.value();
			this._isInEditMode = true;

			this.draw();
		},

		/**
		 * Will close the view where editing of the related data value is possible and display a static
		 * version of the value instead. This is similar to the disabled state but will be visually
		 * different since the input interface will not be visible anymore.
		 * By default the current value will be adopted if it is valid. If not valid or if the first
		 * parameter is false, the value from before the edit mode will be restored.
		 *
		 * @since 0.1
		 *
		 * @param {Boolean} [dropValue] If true, the value from before edit mode has been started will
		 *        be reinstated. false by default. Consider using cancelEditing() instead.
		 */
		stopEditing: function( dropValue ) {
			if( !this.isInEditMode() ) {
				return;
			}
			if( dropValue ) {
				// reinstate initial value from before edit mode
				this.value( this.initialValue() );
			}
			this._initialValue = null;
			this._isInEditMode = false;

			this.draw();
		},

		/**
		 * short-cut for stopEditing( false ). Closes the edit view and restores the value from before
		 * the edit mode has been started.
		 * @since 0.1
		 */
		cancelEditing: function () {
			return this.stopEditing( true );
		},

		/**
		 * Returns whether the view is in its editable state currently.
		 * @since 0.1
		 *
		 * @return Boolean
		 */
		isInEditMode: function() {
			return this._isInEditMode;
		},

		/**
		 * Returns the value from before the edit mode has been started.
		 * If its not in edit mode, the current value will be returned.
		 * @since 0.1
		 */
		initialValue: function() {
			if( !this.isInEditMode() ) {
				return this.value();
			}
			return this._initialValue;
		},

		/**
		 * Returns the value of the view. If the view is in edit mode, this will return the current
		 * value the user is typing. There is no guarantee that the returned value is valid.
		 *
		 * If the first parameter is given, this will change the value represented to that value. This
		 * will trigger validation of the value.
		 *
		 * If null is given or returned, this means that the view is or should be empty.
		 *
		 * @since 0.1
		 *
		 * @param {dv.DataValue|null} [value]
		 * @return {dv.DataValue|null|undefined} null if no value is set currently
		 *
		 * TODO: think about another function which should rather use some kind of "ValidatedDataValue",
		 *       holding a reference to the used data type and the info that it is valid against it.
		 *       As soon as we have validations we have to consider that the given value is invalid,
		 *       this would require the following considerations:
		 *       1) allow setting invalid values (wouldn't be that bad, invalid values should probably
		 *          be displayed anyhow in some cases where we have old values for a property but the
		 *          property definition has changed (e.g. allowed range from 0-1,000 changed to 0-100).
		 *       2) Trigger a validation after the value is set. If invalid, warning in UI
		 *       Probably we want both, a ValidatedDataValue AND the ability to set an invalid value as
		 *       described.
		 *       A ValidatedDataValue could always be returned by another function and be an indicator
		 *       for whether the value is valid or not.
		 */
		value: function( value ) {
			if( value === undefined ) {
				return this._value;
			}
			if( value !== null && !( value instanceof dv.DataValue ) ) {
				throw new Error( 'The given value has to be an instance of dataValue.DataValue or null' );
			}
			this._setValue( value );
		},

		/**
		 * Sets the value internally and triggers the validation process on the new value, will also
		 * make sure that the new value will be displayed.
		 * @since 0.1
		 *
		 * @param {dv.DataValue|null} value
		 */
		_setValue: function( value ) {
			// Check whether given value is actually suitable for the widget:
			if( value !== null // null represents empty value
				&& ( !( value instanceof dv.DataValue ) )
				) {
				throw new Error( 'Instance of dataValues.DataValue required for setting a value' );
			}
			this._value = value;
			this._updateExpert(); // New value, new expert might be required!

			// Display value in expert:
			if( this._expert ) {
				this._setValueIsOngoing = true;
				this._expert.rawValue( value ? value.getValue() : null );
				this._setValueIsOngoing = false;
			}

			// TODO: trigger validation. Value will still be set independent from whether value is valid
			//  to ultimately set a value without triggering validation, some kind of ValidatedDataValue,
			//  as mentioned in the 'value' function's todo, would be required.

			var self = this;

			if( this._value === null ) {
				this._formattedValue = null;
				this.draw();
			} else {
				// TODO: Cache the initial formatted value in order to not have to trigger an API
				// request when resetting.
				this._formatValue( this._value )
					.done( function( formattedValue ) {
						self._formattedValue = formattedValue;
						self.draw();
					} );
			}
		},

		/**
		 * Returns the most current formatted value featured by this valueview.
		 * @since 0.1
		 *
		 * @return {string|jQuery|null}
		 */
		getFormattedValue: function() {
			return this._formattedValue;
		},

		/**
		 * Whether there is currently any value in the view. Basically, whether value() returns null.
		 * @since 0.1
		 *
		 * @returns {boolean}
		 */
		isEmpty: function() {
			return this.value() === null;
		},

		/**
		 * Returns the valueview's expert object required for handling the desired value type.
		 * If there is no expert available for handling that value type, then null will be returned.
		 * @since 0.1
		 *
		 * @return jQuery.valueview.Expert|null
		 */
		expert: function() {
			return this._expert;
		},

		/**
		 * Will update the expert responsible for handling the value type of the current value. If there
		 * is no value set currently (empty value) then the expert will be chosen based on the "on"
		 * option of the valueview widget. This will be an expert suitable for creating a data value
		 * as desired by the "on" option.
		 * @since 0.1
		 */
		_updateExpert: function() {
			var expertProvider = this.options.expertProvider;

			if( !( expertProvider instanceof $.valueview.ExpertFactory ) ) {
				throw new Error( 'No ExpertProvider set in valueview\'s "expertProvider" option' );
			}

			// Get an expert suitable for widget's definition or the current value:
			var expertHint = this.options.on || this._value,
				NewExpert;

			if( expertHint ) {
				NewExpert = expertProvider.getExpert( expertHint );
			} else {
				// no hint, so no value is set, so just empty
				NewExpert = $.valueview.experts.EmptyValue;
			}

			if( !NewExpert ) {
				NewExpert = $.valueview.experts.UnsupportedValue;
			}

			if( this._expert && NewExpert
				&& this._expert.constructor === NewExpert.prototype.constructor
				) {
				return; // fully compatible expert
			}

			// Previous expert not suitable for the new task!
			// Destroy old expert, create new one suitable for value:
			if( this._expert ) {
				this._expert.destroy();
				this._expert = null;
			}

			if( NewExpert ) {
				this._expert = new NewExpert(
					this.$value,
					this.viewState(),
					this.viewNotifier(),
					{
						mediaWiki: this.options.mediaWiki
					}
				);
			}
		},

		/**
		 * Will render the valueview's current state (does consider edit mode, current value, etc.).
		 * @since 0.1
		 */
		draw: function() {
			// have native $.Widget functionality add/remove state css classes
			// (see jQuery.Widget._setOption)
			PARENT.prototype.option.call( this, 'disabled', this.isDisabled() );

			if( !this._expert ) {
				// remove any remains from previous expert
				this.$value.empty();
				// TODO: Display message that data value type is unsupported or no expert indicator and
				//  no value at the same time.
			} else {
				this._expert.draw();
			}

			// add/remove edit mode ui class:
			var staticModeClass = this.widgetBaseClass + '-instaticmode',
				editModeClass = this.widgetBaseClass + '-ineditmode';

			if( this.isInEditMode() ) {
				this.element.addClass( editModeClass ).removeClass( staticModeClass );
			} else {
				this.element.addClass( staticModeClass ).removeClass( editModeClass );
			}
		},

		/**
		 * Marks the valueview disabled and triggers re-drawing it.
		 * Since the visual state should be managed completely by the draw method, toggling the css
		 * classes is done in draw() by issuing a call to $.Widget.option().
		 * @see jQuery.Widget.disable
		 *
		 * @since 0.1
		 */
		disable: function() {
			this.options.disabled = true;
			this.draw();
		},

		/**
		 * Marks the valueview enabled and triggers re-drawing the valueview.
		 * Since the visual state should be managed completely by the draw method, toggling the css
		 * classes is done in draw() by issuing a call to $.Widget.option().
		 * @see jQuery.Widget.enable
		 *
		 * @since 0.1
		 */
		enable: function() {
			this.options.disabled = false;
			this.draw();
		},

		/**
		 * Returns whether the valueview is disabled.
		 * @since 0.1
		 *
		 * @return {boolean}
		 */
		isDisabled: function() {
			return this.option( 'disabled' );
		},

		/**
		 * Focuses the widget.
		 *
		 * @since 0.1
		 */
		focus: expertProxy( 'focus' ),

		/**
		 * Removes focus from the widget.
		 *
		 * @since 0.1
		 */
		blur: expertProxy( 'blur' ),

// TODO: Implement the following for introducing validation feature.
//	/**
//	 * Returns a $.Deferred resolving as soon as the validation for the current value is done.
//	 * This is necessary since validation might need API request and is happening whenever the
//	 * user types something in edit mode. By the point this function is called, the validation
//	 * might not be done.
//	 *
//	 * @return $.Deferred
//	 */
//	validatedValue: function() {},

		/**
		 * Will take the current raw value of the valueview's expert and parse it by taking the value
		 * parser provided by the expert.
		 *
		 * @since 0.1
		 */
		_updateValue: function() {
			var self = this;

			this._parseValue()
				.done( function( parsedValue ) {
					self._value = parsedValue;

					if( self._value === null ) {
						self._formattedValue = null;
						self._expert.draw();
						return;
					}

					self._formatValue( parsedValue )
						.done( function( formattedValue ) {
							self._formattedValue = formattedValue;
							self._expert.draw();
						} )
						.fail( function( error, details ) {
							if( error !== undefined ) {
								// TODO: display some message if parsing failed due to bad API connection etc.
								self._formattedValue = null;
							}
						} );

				} )
				.fail( function( error, details ) {
					if( error !== undefined ) {
						// TODO: display some message if parsing failed due to bad API connection etc.
						self._value = null;
					}
				} );
		},

		/**
		 * Parses the current raw value.
		 *
		 * @return {jQuery.Promise}
		 *
		 * @throws {Error} if the parser result is neither a DataValue instance nor null.
		 * @triggers afterparse
		 */
		_parseValue: function() {
			var self = this,
				expert = this._expert,
				rawValue = expert.rawValue(),
				deferred = $.Deferred();

			this._trigger( 'parse' );

			if( rawValue === null || rawValue instanceof dv.DataValue ) {
				this.__lastUpdateValue = undefined;
				this._value = rawValue;
				self._trigger( 'afterparse' );
				deferred.resolve();
				return deferred.promise();
			}

			if( this._parseTimer ) {
				clearTimeout( this._parseTimer );
			}

			var valueParser = self._instantiateParser( expert.valueCharacteristics() ),
				delay = ( valueParser instanceof vp.ApiBasedValueParser ) ? this.options.parseDelay : 0;

			this._parseTimer = setTimeout( function() {
				self.__lastUpdateValue = rawValue;

				// TODO: Hacky preview spinner activation. Necessary until we move the responsibility
				//  for previews out of the experts. The preview should be handled in the same place for
				//  all value types, could perhaps move into its own widget, listening to valueview
				//  events.
				if( expert._currentExpert && expert._currentExpert.preview ) {
					expert._currentExpert.preview.showSpinner();
				}

				valueParser.parse(
						rawValue
					)
					.done( function( parsedValue ) {
						// Paranoia check against ValueParser interface:
						if( parsedValue !== null && !( parsedValue instanceof dv.DataValue ) ) {
							throw new Error( 'Unexpected value parser result' );
						}

						if( self.__lastUpdateValue === undefined ) {
							// latest update job is done, this one must be a late response for some weird
							// reason
							deferred.reject();
						}

						if( expert.rawValueCompare( self.__lastUpdateValue, rawValue ) ) {
							// this is the response for the latest update! by setting this to undefined, we
							// will ignore all responses which might come back late.
							// Another reason for this could be something like "a", "ab", "a", where the
							// first response comes back and the following two can be ignored.
							self.__lastUpdateValue = undefined;
						}

						deferred.resolve( parsedValue );
					} )
					.fail( function( error, details ) {
						deferred.reject( error, details );
					} )
					.always( function() {
						self._trigger( 'afterparse' );
					} );
			} , delay );

			return deferred.promise();
		},

		/**
		 * Instantiates a parser adequate to the "on" option or the current value.
		 *
		 * @param {Object} [additionalParserOptions]
		 * @return {valueParsers.ValueParser}
		 */
		_instantiateParser: function( additionalParserOptions ) {
			var Parser = this.options.valueParserProvider.getParser( this._getPurpose() ),
				parserOptions = $.extend(
					{},
					Parser.prototype.getOptions(),
					additionalParserOptions || {}
				);

			return new Parser( parserOptions );
		},

		/**
		 * Formats a specific data value.
		 *
		 * @param {dataValues.DataValue} dataValue
		 * @return {jQuery.Promise}
		 *
		 * @triggers afterformat
		 */
		_formatValue: function( dataValue ) {
			var self = this,
				deferred = $.Deferred(),
				valueFormatter = this._instantiateFormatter( this._expert.valueCharacteristics() );

			valueFormatter.format( dataValue )
				.done( function( formattedValue, formattedDataValue ) {
					if( dataValue === formattedDataValue ) {
						deferred.resolve( formattedValue );
					} else {
						// Late response that should be ignored.
						deferred.reject();
					}
				} )
				.fail( function( error, details ) {
					deferred.reject( error, details );
				} )
				.always( function() {
					self._trigger( 'afterformat' );
				} );

			return deferred.promise();
		},

		/**
		 * Instantiates a formatter adequate to the "on" option or the current value.
		 *
		 * @param {Object} [additionalFormatterOptions]
		 * @return {valueFormatters.ValueFormatter}
		 */
		_instantiateFormatter: function( additionalFormatterOptions ) {
			var Formatter = this.options.valueFormatterProvider.getFormatter( this._getPurpose() ),
				formatterOptions = $.extend(
					{},
					Formatter.prototype.getOptions(),
					additionalFormatterOptions || {}
				);

			return new Formatter( formatterOptions );
		},

		/**
		 * Tries to figure out the purpose of this valueview. The purpose - either a DataType instance
		 * or a DataValue type string - may be used to retrieve a parser or a formatter.
		 *
		 * @return {dataTypes.DataType|string}
		 *
		 * @throws {Error} if no proper purpose could be determined.
		 */
		_getPurpose: function() {
			var purpose = this.options.on;

			if( purpose instanceof dv.DataValue ) {
				purpose = purpose.getType();
			} else if( !purpose && this._value instanceof dv.DataValue ) {
				purpose = this._value.getType();
			}

			if( !purpose ) {
				throw new Error( 'No adequate information to figure out a purpose' );
			}

			return purpose;
		},

		/**
		 * Returns an object with information about the view. This is a immutable object which can be
		 * passed if information about the view should be revealed to some function or constructor
		 * without making the whole view object available.
		 *
		 * @since 0.1
		 *
		 * @return jQuery.valueview.ViewState
		 */
		viewState: function() {
			return new $.valueview.ViewState( this );
		},

		/**
		 * Returns an object which allows to notify the view about certain events. This is required in
		 * the view's expert and should only be used with caution if used from other places.
		 *
		 * @since 0.1
		 *
		 * @return dataValues.util.Notifier
		 */
		viewNotifier: function() {
			var self = this;

			return new dv.util.Notifier( {
				change: function() {
					if( !self._expert ) {
						// someone notified about change while there couldn't have been one since there
						// is no expert which allows for any change currently...
						return;
					}
					// explicitly check whether the raw value has actually changed compared to the value
					// we have currently. This is not the case when _setValue() sets a new value because
					// the expert will get that new value's raw value while we already have the parsed
					// version of the value.
					var value = self.value(),
						rawValue = value ? value.getValue() : null,
						differentValueCharacteristics = false,
						newValueCharacteristics = self._expert.valueCharacteristics(),
						lastValueCharacteristics = self.__lastValueCharacteristics || {};

					for( var i in newValueCharacteristics ) {
						differentValueCharacteristics = differentValueCharacteristics ||
							newValueCharacteristics[i] !== lastValueCharacteristics[i];
					}
					for( var i in lastValueCharacteristics ) {
						differentValueCharacteristics = differentValueCharacteristics ||
							newValueCharacteristics[i] !== lastValueCharacteristics[i];
					}

					if( !self._setValueIsOngoing
						&& (
						differentValueCharacteristics
							|| !self._expert.rawValueCompare( rawValue )
						)
						) {
						self.__lastValueCharacteristics = newValueCharacteristics;
						self._trigger( 'change' );
						self._updateValue();
					}
				}
			} );
		}
	} );

}( dataValues, dataTypes, valueParsers, jQuery ) );