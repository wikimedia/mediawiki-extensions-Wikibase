module.exports = ( function( dv, vf, vp ) {
'use strict';

var ViewState = require( './jquery.valueview.ViewState.js' );

var PARENT = $.Widget;

/**
 * Helper for defining a valueview member function which will just call a valueview's Expert's
 * member function and return the value received from that function. If the valueview does not have
 * an expert currently, nothing will be done.
 *
 * @ignore
 *
 * @param {string} fnName Name of the function in jQuery.valueview.Expert
 * @return {Function}
 */
function expertProxy( fnName ) {
	return function() {
		if ( this._expert && this.isInEditMode() ) {
			return this._expert[ fnName ].apply( this._expert, arguments );
		}
	};
}

/**
 * `valueview` widget which is responsible for displaying and serving input for creating/changing
 * data value objects (`dataValue.DataValue`). Depending on the data value type, the widget will
 * choose a different strategy for handing interaction with a specific instance of that data value.
 *
 * @class jQuery.valueview
 * @alternateClassName jQuery.valueview.valueview
 * @extends jQuery.Widget
 * @since 0.1
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {jQuery.valueview.ExpertStore} options.expertStore
 *        Used to determine an `Expert` depending on the data value type or the data type the
 *        `valueview` should handle. The `valueview` will be able to handle all data value types and
 *        data types the given store has `Experts` registered for.
 * @param {valueParsers.ValueParserStore} options.parserStore
 *        Store providing the parsers values may be parsed with.
 * @param {valueFormatters.ValueFormatter} options.plaintextFormatter
 *        A ValueFormatter instance returning plain text
 * @param {valueFormatters.ValueFormatter} options.htmlFormatter
 *        A ValueFormatter instance returning html
 * @param {string} options.language
 *        Language code of the language the `valueview` shall interact with parsers
 * @param {string|null} [options.vocabularyLookupApiUrl=null]
 * @param {string} [options.commonsApiUrl='https://commons.wikimedia.org/w/api.php']
 * @param {string|null} [options.dataTypeId=null]
 *        If set, an expert (`jQuery.valueview.Expert`) and a parser (`valueParsers.ValueParser`)
 *        will be determined from the provided stores according to the specified data type id.
 *        When setting the `valueview`'s value to a data value that is not valid against the data
 *        type referenced by the data type id, a note that the value is not suitable for the
 *        widget's current definition will be displayed.
 *        If the `dataTypeId` option is `null`, expert and parser will be determined
 *        using the `dataValueType` option.
 * @param {string|null} [options.dataValueType=null]
 *        If set while the `dataTypeId` option is `null`, a parser (`valueParsers.ValueParser`)
 *        will be determined from the provided store according to the specified data value type.
 *        When setting the `valueview`'s value to a data value that is not valid against the data
 *        value referenced by the data value type, a note that the value is not suitable for the
 *        widget's current definition will be displayed.
 *        If the `dataValueType` option as well as the `dataTypeId` option is `null`, expert and parser
 *        will be determined using the widget's current value.
 *        Consequently, if the value itself is `null`, the widget will not be able to offer any
 *        input for new values.
 * @param {dataValues.DataValue|null} [options.value=null]
 *        The data value this view should represent initially.
 *        If omitted, an empty view will be served, ready to take some input by the user. The value
 *        can also be overwritten later, by using the `value()` function.
 * @param {boolean} [options.autoStartEditing=true]
 *        Whether or not view should go into edit mode by its own upon initialization if its initial
 *        value is empty.
 * @param {number} [options.parseDelay=300]
 *        Time milliseconds that the parser should wait before parsing. A delay is useful to limit
 *        the number of API request that are outdated when returning because the input has changed
 *        in the meantime.
 * @param {util.MessageProvider|null} [options.messageProvider=null]
 *        Allows to customize the messages used by `ValueView`, `Expert`s and used widgets.
 * @param {util.ContentLanguages|null} [options.contentLanguages=null]
 *        Enables `Expert`s to provide language selection (i. e. the `MonolingualText` `Expert`).
 */
/**
 * @event change
 * Triggered when the widget's value is updated.
 * @param {jQuery.Event} event
 */
/**
 * @event parse
 * Triggered before the value gets parsed.
 * @param {jQuery.Event} event
 */
/**
 * @event afterparse
 * Triggered after the value has been parsed.
 * @param {jQuery.Event} event
 */
/**
 * @event afterstartediting
 * Triggered after edit mode has been started and rendered.
 * @param {jQuery.Event} event
 */
/**
 * @event afterstopediting
 * Triggered after edit mode has been stopped and the widget has been redrawn.
 * @param {jQuery.Event} event
 * @param {boolean} dropValue
 */
/**
 * @event afterdraw
 * Triggered after the widget has been redrawn.
 * @param {jQuery.Event} event
 */
$.widget( 'valueview.valueview', PARENT, {
	/**
	 * Current, accepted value. Might be "behind" the `Expert`'s raw value until the raw value gets
	 * parsed and the parsed result set as the new accepted value.
	 *
	 * @property {dataValues.DataValue|null}
	 * @private
	 */
	_value: null,

	/**
	 * Most current formatted value. Might be "behind" the `Expert`'s raw value as well as the
	 * `valueview`'s parsed `DataValue` since formatting might involve an asynchronous
	 * request.
	 *
	 * @property {string} HTML
	 * @private
	 */
	_formattedValue: '',

	/**
	 * Plain text version of the value to be shown when the user starts editing.
	 *
	 * @property {string} Plain text
	 * @private
	 */
	_textValue: '',

	/**
	 * The DOM node containing the actual value representation. This is the `Expert`'s viewport.
	 *
	 * @property {jQuery}
	 * @readonly
	 */
	$value: null,

	/**
	 * Value from before edit mode.
	 *
	 * @property {dataValues.DataValue|null}
	 * @private
	 */
	_initialValue: null,

	/**
	 * @property {boolean} [_isInEditMode=false]
	 * @private
	 */
	_isInEditMode: false,

	/**
	 * `Expert` object responsible for serving the DOM to edit the current value. This is only
	 * available when in edit mode, otherwise it is `null`.
	 * Can also be `null` if the current value has a data value type unknown to the expert store
	 * given in the `expertStore` option.
	 *
	 * @property {jQuery.valueview.Expert|null}
	 * @private
	 */
	_expert: null,

	/**
	 * Timeout id of the currently running `setTimeout` function that delays the parser API request.
	 *
	 * @property {number}
	 * @private
	 */
	_parseTimer: null,

	/**
	 * @see jQuery.Widget.options
	 * @protected
	 * @readonly
	 */
	options: {
		expertStore: null,
		parserStore: null,
		htmlFormatter: null,
		plaintextFormatter: null,
		dataTypeId: null,
		dataValueType: null,
		value: null,
		language: null,
		vocabularyLookupApiUrl: null,
		commonsApiUrl: 'https://commons.wikimedia.org/w/api.php',
		autoStartEditing: false,
		parseDelay: 300,
		messageProvider: null,
		contentLanguages: null
	},

	/**
	 * @see jQuery.Widget._create
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if ( !this.options.expertStore
			|| !this.options.parserStore
			|| !this.options.htmlFormatter
			|| !this.options.plaintextFormatter
			|| typeof this.options.language !== 'string'
		) {
			throw new Error( 'Required option(s) not defined properly' );
		}

		// Build widget's basic dom:
		this.element.addClass( this.widgetBaseClass );
		this.$value = $( '<div/>', {
			class: this.widgetBaseClass + '-value'
		} );

		// Set initial value if provided in options:
		this._initValue( this.option( 'value' ) || null );

		if ( this.option( 'autoStartEditing' ) && this.isEmpty() ) {
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

		if ( this._expert ) {
			this._destroyExpert();
		}

		return PARENT.prototype.destroy.call( this );
	},

	/**
	 * @param key
	 * @param value
	 * @see jQuery.Widget._setOption
	 * @protected
	 * @throws {Error} when trying to set an option that cannot be set after initialization.
	 */
	_setOption: function( key, value ) {
		switch ( key ) {
			case 'autoStartEditing':
				// doesn't make sense to change this after initialization
				throw new Error( 'Can not change jQuery.valueview option "' + key
					+ '" after widget initialization' );
		}

		PARENT.prototype._setOption.call( this, key, value );

		switch ( key ) {
			case 'expertStore':
			case 'dataTypeId': // TODO: make this work properly and test
			case 'dataValueType':
				this._updateExpertConstructor();
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
	 */
	startEditing: function() {
		var self = this;

		if ( this.isInEditMode() ) {
			return; // return nothing to allow chaining
		}

		this._initialValue = this.value();
		this._isInEditMode = true;

		this.element.html( this.$value );

		// XXX: This component shouldn't need to know about this :/
		// The html initially present (from the static html) does not necessarily
		// work as expected when moved around. Immediately re-render for
		// displaying Kartographer maps in entities.
		if ( this._value && this.options.dataValueType === 'globecoordinate' ) {
			this._formatValue( this._value )
			.done( function( formattedValue ) {
				self._formattedValue = formattedValue;
				self.draw();
			} )
			.fail( function( message ) {
				if ( message ) {
					self._renderError( message );
				}
			} );
		}

		this.draw()
		.done( function() {
			self._trigger( 'afterstartediting' );
		} );
	},

	/**
	 * Will close the view where editing of the related data value is possible and display a static
	 * version of the value instead. This is similar to the disabled state but will be visually
	 * different since the input interface will not be visible anymore.
	 * By default the current value will be adopted if it is valid. If not valid or if the first
	 * parameter is false, the value from before the edit mode will be restored.
	 *
	 * @param {boolean} [dropValue=false] If `true`, the value from before edit mode has been
	 *        started will be reinstated.
	 */
	stopEditing: function( dropValue ) {
		if ( !this.isInEditMode() ) {
			return;
		}

		dropValue = !!dropValue;

		var self = this;

		if ( dropValue ) {
			// reinstate initial value from before edit mode
			this.value( this.initialValue() );
		}

		this._initialValue = null;
		this._isInEditMode = false;
		delete this.__lastValueCharacteristics;
		if ( this._expert ) {
			this._destroyExpert();
		}

		this.$value.detach();

		this.draw()
		.done( function() {
			self._trigger( 'afterstopediting', null, [dropValue] );
		} );
	},

	/**
	 * Short-cut for `stopEditing( true )`. Closes the edit view and restores the value from
	 * before the edit mode has been started.
	 */
	cancelEditing: function() {
		this.stopEditing( true );
	},

	/**
	 * Returns whether the view is in its editable state currently.
	 *
	 * @return {boolean}
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Returns the value from before the edit mode has been started.
	 * If its not in edit mode, the current value will be returned.
	 */
	initialValue: function() {
		if ( !this.isInEditMode() ) {
			return this.value();
		}
		return this._initialValue;
	},

	// TODO: think about another function which should rather use some kind of "ValidatedDataValue",
	//       holding a reference to the used data type and the info that it is valid against it.
	//       As soon as we have validations we have to consider that the given value is invalid,
	//       this would require the following considerations:
	//       1) allow setting invalid values (wouldn't be that bad, invalid values should probably
	//          be displayed anyhow in some cases where we have old values for a property but the
	//          property definition has changed (e.g. allowed range from 0-1,000 changed to 0-100).
	//       2) Trigger a validation after the value is set. If invalid, warning in UI
	//       Probably we want both, a ValidatedDataValue AND the ability to set an invalid value as
	//       described.
	//       A ValidatedDataValue could always be returned by another function and be an indicator
	//       for whether the value is valid or not.
	/**
	 * Returns the value of the view. If the view is in edit mode, this will return the current
	 * value the user is typing. There is no guarantee that the returned value is valid.
	 *
	 * If the first parameter is given, this will change the value represented to that value. This
	 * will trigger validation of the value.
	 *
	 * If `null` is given or returned, this means that the view is or should be empty.
	 *
	 * @param {dataValues.DataValue|null} [value]
	 * @return {dataValues.DataValue|null|undefined} `null` if no value is set currently
	 *
	 * @throws {Error} if value provided is not a `dataValues.DataValue` instance.
	 */
	value: function( value ) {
		if ( value === undefined ) {
			return this._value;
		}
		if ( value !== null && !( value instanceof dv.DataValue ) ) {
			throw new Error( 'The given value has to be an instance of dataValues.DataValue or '
				+ 'null' );
		}
		this._setValue( value );
	},

	/**
	 * @private
	 *
	 * @param {dataValues.DataValue|null} value
	 * @return {dataValues.DataValue|null|undefined}
	 */
	_initValue: function( value ) {
		var formattedValue = this.element.html();
		if ( !formattedValue ) {
			return this.value( value );
		} else {
			this._value = value;
			this._formattedValue = formattedValue;
			this._updateExpertConstructor();
			this.draw();
		}
	},

	/**
	 * Sets the value internally and triggers the validation process on the new value, will also
	 * make sure that the new value will be displayed.
	 *
	 * @param {dataValues.DataValue|null} value
	 *
	 * @throws {Error} if value provided is not a `dataValues.DataValue` instance.
	 */
	_setValue: function( value ) {
		// Check whether given value is actually suitable for the widget:
		if ( value !== null // null represents empty value
			&& !( value instanceof dv.DataValue )
		) {
			throw new Error( 'Instance of dataValues.DataValue required for setting a value' );
		}

		if ( this._value && value && JSON.stringify( value.toJSON() ) === JSON.stringify( this._value.toJSON() ) ) {
			return;
		}

		this._value = value;
		this._updateExpertConstructor(); // new value, new expert might be needed

		// TODO: trigger validation. Value will still be set independent from whether value is valid
		//  to ultimately set a value without triggering validation, some kind of ValidatedDataValue,
		//  as mentioned in the 'value' function's todo, would be required.

		var self = this;

		if ( this._value === null ) {
			this.draw();
		} else {
			// TODO: Cache the initial formatted value in order to not have to trigger an API
			// request when resetting.
			this._formatValue( this._value )
				.done( function( formattedValue ) {
					self._formattedValue = formattedValue;
					self.draw();
				} )
				.fail( function( message ) {
					if ( message ) {
						self._renderError( message );
					}
				} );
		}
	},

	/**
	 * Returns the most current formatted value featured by this `valueview`.
	 *
	 * @return {string}
	 */
	getFormattedValue: function() {
		return this._formattedValue;
	},

	/**
	 * Returns the current value formatted as plain text.
	 *
	 * @since 0.4
	 *
	 * @return {string}
	 */
	getTextValue: function() {
		return this._textValue;
	},

	/**
	 * Whether there is currently any value in the view. Basically, whether `value()` returns
	 * `null`.
	 *
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.value() === null;
	},

	/**
	 * Returns the `valueview`'s `Expert` object required for handling the desired value type. If
	 * there is no `Expert` available for handling that value type, then null will be returned.
	 *
	 * @return {jQuery.valueview.Expert|null}
	 */
	expert: function() {
		return this._expert;
	},

	/**
	 * Will update the constructor currently used for creating an `Expert`, if one is needed.
	 *
	 * @private
	 *
	 * @throws {Error} if no `Expert` store being an instance of `jQuery.valueview.ExpertStore` is
	 *         set in the options.
	 */
	_updateExpertConstructor: function() {
		if ( !( this.options.expertStore instanceof $.valueview.ExpertStore ) ) {
			throw new Error( 'No ExpertStore set in valueview\'s "expertStore" option' );
		}

		var dataValueType = this._determineDataValueType();

		this._expertConstructor = $.valueview.experts.EmptyValue;

		if ( dataValueType || this.options.dataTypeId ) {
			this._expertConstructor = this.options.expertStore.getExpert(
				dataValueType,
				this.options.dataTypeId
			) || $.valueview.experts.UnsupportedValue;
		}
	},

	/**
	 * Will update the `Expert` responsible for handling the value type of the current value. If
	 * there is no value set currently (empty value), the expert will be chosen based on the
	 * `dataTypeId` or `dataValueType` option of the `valueview` widget.
	 *
	 * @private
	 */
	_updateExpert: function() {
		if ( this._expert && this._expertConstructor
			&& this._expert.constructor === this._expertConstructor.prototype.constructor
		) {
			return; // fully compatible expert
		}

		// Previous expert not suitable for the new task!
		// Destroy old expert, create new one suitable for value:
		if ( this._expert ) {
			this._destroyExpert();
		}

		if ( this._expertConstructor ) {
			this._expert = new this._expertConstructor(
				this.$value,
				this.viewState(),
				this.viewNotifier(),
				{
					language: this.options.language,
					vocabularyLookupApiUrl: this.options.vocabularyLookupApiUrl || null,
					commonsApiUrl: this.options.commonsApiUrl,
					contentLanguages: this.options.contentLanguages,
					messageProvider: this.options.messageProvider
				}
			);
			this._expert.init();
		}
	},

	/**
	 * @private
	 */
	_destroyExpert: function() {
		this._expert.destroy();
		this._expert = null;
	},

	/**
	 * Will render the `valueview`'s current state (does consider edit mode, current value, etc.).
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {Function} return.fail
	 */
	draw: function() {
		var self = this;

		this.element
			.toggleClass( this.widgetBaseClass + '-instaticmode', !this._isInEditMode )
			.toggleClass( this.widgetBaseClass + '-ineditmode', this._isInEditMode );

		return this.drawContent()
			.done( function() {
				self._trigger( 'afterdraw' );
			} );
	},

	/**
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {Function} return.fail
	 */
	drawContent: function() {
		var self = this,
			deferred = $.Deferred();

		if ( this.isInEditMode() ) {
			this._updateTextValue().then( function () {
				if ( !self.isInEditMode() ) {
					// edit mode was left while formatting text value
					return;
				}

				self._updateExpert();

				// TODO: Display message that data value type is unsupported or no expert indicator
				//  and no value at the same time:
				// if ( !self._expert ) { ... }

				self._expert.draw()
				.done( function() {
					deferred.resolve();
				} )
				.fail( function() {
					deferred.reject();
				} );
			} );
		} else {
			this.drawStaticContent();
			deferred.resolve();
		}

		return deferred.promise();
	},

	/**
	 * Draws static content.
	 */
	drawStaticContent: function() {
		this.element.html( this.getFormattedValue() );
	},

	/**
	 * Focuses the widget.
	 */
	focus: expertProxy( 'focus' ),

	/**
	 * Removes focus from the widget.
	 */
	blur: expertProxy( 'blur' ),

	/**
	 * Will take the current raw value of the `valueview`'s `Expert` and parse and format it using
	 * the `valueParserStore`, `plaintextFormatter` and `htmlFormatter` injected via the options.
	 *
	 * @private
	 */
	_updateValue: function() {
		var self = this;

		this._value = null;
		this._formattedValue = '';
		this._textValue = '';

		return this._parseValue()
			.done( function( parsedValue ) {
				self._value = parsedValue;

				if ( self._value === null ) {
					self.drawContent();
					return;
				}

				self._formatValue( parsedValue )
					.done( function( formattedValue ) {
						self._formattedValue = formattedValue;
						self.drawContent();
					} )
					.fail( function( message ) {
						if ( message ) {
							self._renderError( message );
						}
					} );

			} )
			.fail( function( message ) {
				if ( message ) {
					self._renderError( message );
				}
			} );
	},

	/**
	 * Renders an error message.
	 *
	 * @private
	 *
	 * @param {string} message HTML error message.
	 */
	_renderError: function( message ) {
		if ( this._expert && this._expert.preview ) {
			this._expert.preview.update( message );
		}
	},

	/**
	 * Parses the current raw value.
	 *
	 * @private
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {dataValues.DataValue|null} return.done.value The parse result.
	 * @return {Function} return.fail
	 * @return {string|undefined} return.fail.message HTML error message or `undefined` if the
	 *         result shall be ignored.
	 *
	 * @throws {Error} if the parser result is neither a `DataValue` instance nor null.
	 */
	_parseValue: function() {
		var self = this,
			expert = this._expert,
			rawValue = expert.rawValue(),
			deferred = $.Deferred();

		this._trigger( 'parse' );

		if ( rawValue === null || rawValue instanceof dv.DataValue ) {
			this.__lastUpdateValue = undefined;
			self._trigger( 'afterparse' );
			deferred.resolve( rawValue );
			return deferred.promise();
		}

		if ( this._parseTimer ) {
			clearTimeout( this._parseTimer );
		}

		var valueParser = this._instantiateParser( expert.valueCharacteristics() );

		self.__lastUpdateValue = rawValue;
		this._parseTimer = setTimeout( function() {
			// TODO: Hacky preview spinner activation. Necessary until we move the responsibility
			//  for previews out of the experts. The preview should be handled in the same place for
			//  all value types, could perhaps move into its own widget, listening to valueview
			//  events.
			if ( expert && expert.preview ) {
				expert.preview.showSpinner();
			}

			valueParser.parse( rawValue )
				.done( function( parsedValue ) {
					// Paranoia check against ValueParser interface:
					if ( parsedValue !== null && !( parsedValue instanceof dv.DataValue ) ) {
						throw new Error( 'Unexpected value parser result' );
					}

					if ( self.__lastUpdateValue === undefined || self.__lastUpdateValue !== rawValue ) {
						// latest update job is done, this one must be a late response for some weird
						// reason, or the value has since been updated, so should be re-parsed
						// and this result be rejected and ignored.
						deferred.reject();
					} else {
						// this is the response for the latest update! by setting this to undefined, we
						// will ignore all responses which might come back late.
						// Another reason for this could be something like "a", "ab", "a", where the
						// first response comes back and the following two can be ignored.
						self.__lastUpdateValue = undefined;
					}

					deferred.resolve( parsedValue );
				} )
				.fail( function( message ) {
					deferred.reject( message );
				} )
				.always( function() {
					self._trigger( 'afterparse' );
				} );
		}, this.options.parseDelay );

		return deferred.promise();
	},

	/**
	 * @private
	 *
	 * @param {Object} [additionalParserOptions]
	 * @return {valueParsers.ValueParser}
	 *
	 * @throws {Error} if no parser store being an instance of `valueParsers.ValueParserStore` is
	 *         set in the options.
	 */
	_instantiateParser: function( additionalParserOptions ) {
		if ( !( this.options.parserStore instanceof vp.ValueParserStore ) ) {
			throw new Error( 'No value parser store in valueview\'s options specified' );
		}

		var Parser = this.options.parserStore.getParser(
			this._determineDataValueType(),
			this.options.dataTypeId
		);

		var parserOptions = $.extend(
			{
				lang: this.options.language
			},
			Parser.prototype.getOptions(),
			additionalParserOptions || {}
		);

		return new Parser( parserOptions );
	},

	/**
	 * Formats a specific data value.
	 *
	 * @private
	 *
	 * @param {dataValues.DataValue} dataValue
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {string} return.done.formattedValue
	 * @return {Function} return.fail
	 * @return {string|undefined} return.fail.message HTML error message or `undefined` if the
	 *         result shall be ignored.
	 */
	_formatValue: function( dataValue ) {
		var self = this,
			deferred = $.Deferred();

		this.options.htmlFormatter.format( dataValue )
			.done( function( formattedValue, formattedDataValue ) {
				if ( dataValue === formattedDataValue ) {
					deferred.resolve( formattedValue );
				} else {
					// Late response that should be ignored.
					deferred.reject();
				}
			} )
			.fail( function( message ) {
				deferred.reject( message );
			} )
			.always( function() {
				self._trigger( 'afterformat' );
			} );

		return deferred.promise();
	},

	/**
	 * @private
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {string|null} return.done.formatted Formatted `DataValue`.
	 * @return {dataValues.DataValue|null} return.done.dataValue `DataValue` object that has been
	 *         formatted.
	 * @return {Function} return.fail
	 * @return {string} return.fail.message HTML error message.
	 */
	_updateTextValue: function() {
		var self = this,
			deferred = $.Deferred(),
			dataValue = this._value;

		if ( !dataValue ) {
			deferred.resolve();
			return deferred.promise();
		}

		this.options.plaintextFormatter.format( dataValue )
			.done( function( formattedValue, formattedDataValue ) {
				if ( dataValue === formattedDataValue ) {
					self._textValue = formattedValue;
					deferred.resolve();
				} else {
					// Late response that should be ignored.
					deferred.reject();
				}
			} )
			.fail( function( message ) {
				deferred.reject( message );
			} );

		return deferred.promise();
	},

	/**
	 * @private
	 *
	 * @return {string|null}
	 */
	_determineDataValueType: function() {
		var value = this.value();
		return ( !this.options.dataValueType && value )
			? value.getType()
			: this.options.dataValueType;
	},

	/**
	 * Returns an object with information about the view. This is a immutable object which can be
	 * passed if information about the view should be revealed to some function or constructor
	 * without making the whole view object available.
	 *
	 * @return {ViewState}
	 */
	viewState: function() {
		return new ViewState( this );
	},

	/**
	 * Returns an object which allows to notify the view about certain events. This is required in
	 * the `valueview`'s `Expert` and should only be used with caution if used from other places.
	 *
	 * @return {util.Notifier}
	 */
	viewNotifier: function() {
		var self = this;

		return new util.Notifier( {
			change: function() {
				var i;

				if ( !self._expert ) {
					// someone notified about change while there couldn't have been one since there
					// is no expert which allows for any change currently...
					return;
				}
				// explicitly check whether the raw value has actually changed compared to the value
				// we have currently. This is not the case when _setValue() sets a new value because
				// the expert will get that new value's raw value while we already have the parsed
				// version of the value.
				var differentValueCharacteristics = false,
					newValueCharacteristics = self._expert.valueCharacteristics(),
					lastValueCharacteristics = self.__lastValueCharacteristics || {};

				for ( i in newValueCharacteristics ) {
					differentValueCharacteristics = differentValueCharacteristics
					|| newValueCharacteristics[i] !== lastValueCharacteristics[i];
				}
				for ( i in lastValueCharacteristics ) {
					differentValueCharacteristics = differentValueCharacteristics
					|| newValueCharacteristics[i] !== lastValueCharacteristics[i];
				}

				var changeDetected = differentValueCharacteristics ||
					self.getTextValue() !== self._expert.rawValue();

				if ( changeDetected ) {
					self.__lastValueCharacteristics = newValueCharacteristics;
					self._updateValue().done( function() {
						self._trigger( 'change' );
					} );
				}
			}
		} );
	}

} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.valueview.valueview.prototype.widgetBaseClass = 'valueview';

}( dataValues, valueFormatters, valueParsers ) );
