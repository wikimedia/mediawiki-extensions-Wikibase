( function ( wb, dv ) {
	'use strict';

	// Back-up components already initialized in the namespace to re-apply them after initializing
	// the snakview widget.
	$.wikibase = $.wikibase || {};
	var existingSnakview = $.wikibase.snakview || {};

	// Erase existing object to prevent jQuery.Widget detecting an existing constructor:
	delete $.wikibase.snakview;

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' ),
		ViewState = require( './snakview.ViewState.js' ),
		variations = require( './snakview.variations.js' ),
		wbserialization = require( 'wikibase.serialization' );

	/**
	 * View for displaying and editing `datamodel.Snak` objects.
	 *
	 * @see datamodel.Snak
	 * @class jQuery.wikibase.snakview
	 * @extends jQuery.ui.EditableTemplatedWidget
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {Object|datamodel.Snak|null} [options.value]
	 *        The `Snak` this `snakview` should represent initially. If omitted, an empty view will be
	 *        served, ready to take some input by the user. The value may be overwritten later, by using
	 *        the `value()` or the `snak()` function.
	 *        Default: `{ snaktype: datamodel.PropertyValueSnak.TYPE }`
	 * @param {Object|boolean} [options.locked=false]
	 *        Key-value pairs determining which `snakview` elements to lock from being edited by the
	 *        user. May also be a boolean value enabling/disabling all elements. If `false`, no elements
	 *        will be locked.
	 * @param {boolean} [options.autoStartEditing=true]
	 *        Whether the `snakview` should switch to edit mode automatically upon initialization if its
	 *        initial value is empty.
	 * @param {wikibase.entityIdFormatter.EntityIdHtmlFormatter} options.entityIdHtmlFormatter
	 *        Required for dynamically rendering links to `Entity`s.
	 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} options.entityIdPlainFormatter
	 *        Required for dynamically rendering plain text references to `Entity`s.
	 * @param {PropertyDataTypeStore} options.propertyDataTypeStore
	 *        Required for looking up the Snak's property's data type.
	 * @param {wikibase.ValueViewBuilder} options.valueViewBuilder
	 *        Required to interfacing a `snakview` "value" `Variation` to `jQuery.valueview`.
	 * @param {wikibase.dataTypes.DataTypeStore} options.dataTypeStore
	 *        Required to retrieve and evaluate a proper `wikibase.dataTypes.DataType` object when interacting on
	 *        a "value" `Variation`.
	 * @param {boolean} [options.drawProperty=true]
	 *        The `Property` part of the `snakview` is not rendered when `drawProperty` is false.
	 */
	/**
	 * @event afterstartediting
	 * Triggered after having started the widget's edit mode.
	 * @param {jQuery.Event} event
	 */
	/**
	 * @event afterstopediting
	 * Triggered after having stopped the widget's edit mode.
	 * @param {jQuery.Event} event
	 * @param {boolean} dropValue
	 */
	/**
	 * @event change
	 * Triggered whenever the widget's content or status is changed.
	 * @param {jQuery.Event} event
	 */
	$.widget( 'wikibase.snakview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			template: 'wikibase-snakview',
			templateParams: [ '', '', '', '' ],
			templateShortCuts: {
				$property: '.wikibase-snakview-property',
				$snakValue: '.wikibase-snakview-value',
				$snakTypeSelector: '.wikibase-snakview-typeselector'
			},
			value: {
				snaktype: datamodel.PropertyValueSnak.TYPE
			},
			locked: {
				property: false,
				snaktype: false
			},
			autoStartEditing: true,
			entityIdPlainFormatter: null,
			entityIdHtmlFormatter: null,
			valueViewBuilder: null,
			dataTypeStore: null,
			drawProperty: true,
			getSnakRemover: null,
			propertyDataTypeStore: null
		},

		/**
		 * `Variation` object responsible for presenting the essential parts of a certain kind of
		 * `Snak`. May be `null` if an unsupported `Snak` type is represented by the `snakview`. In this
		 * case, the `snakview` won't be able to display the `Snak` but displays an appropriate message
		 * instead.
		 *
		 * @property {Variation|null}
		 * @private
		 */
		_variation: null,

		/**
		 * Cache for the values of specific `variation`s used to have those
		 * values restored when toggling the `Snak` type.
		 *
		 * @property {Object}
		 * @private
		 */
		_cachedValues: null,

		/**
		 * Whether then `snakview`'s value is regarded "valid" at the moment.
		 *
		 * @property {boolean}
		 * @private
		 */
		_isValid: false,

		/**
		 * @inheritdoc
		 * @protected
		 */
		_create: function () {
			if ( this.options.locked === true || this.options.locked.property === true ) {
				if ( !(
					this.options.value instanceof datamodel.Snak || ( this.options.value && this.options.value.property )
				) ) {
					mw.log.warn( 'You cannot lock the property without specifying a property' );
				}
			}

			PARENT.prototype._create.call( this );

			this._cachedValues = {};

			this.updateVariation();
			this.updateHash();

			// Re-render on previously generated DOM should be avoided. However, when regenerating the
			// whole snakview, every component needs to be drawn.
			var propertyIsEmpty = !this.$property.contents().length,
				snakValueIsEmpty = !this.$snakValue.contents().length;

			if ( propertyIsEmpty && this.options.drawProperty ) {
				this.drawProperty();
			}

			if ( snakValueIsEmpty ) {
				this.drawVariation();
			}

			if ( this.option( 'autoStartEditing' ) && !this.snak() ) {
				// If no Snak is represented, offer UI to build one.
				// This clearly implies draw() since it requires visual changes!
				this.startEditing();
			}
		},

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} when trying to set an invalid value.
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value' ) {
				if ( value !== null
					&& !$.isPlainObject( value )
					&& !( value instanceof datamodel.Snak )
				) {
					throw new Error( 'The given value has to be a plain object, an instance of '
						+ 'datamodel.Snak, or null' );
				}
			} else if ( key === 'locked' && typeof value === 'boolean' ) {
				var locked = value;
				value = $.extend( {}, $.wikibase.snakview.prototype.options.locked );
				Object.keys( $.wikibase.snakview.prototype.options.locked ).forEach( function ( k ) {
					value[ k ] = locked;
				} );
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'value' ) {
				this.updateVariation();
				this.draw();
			} else if ( key === 'disabled' ) {
				var propertySelector = this._getPropertySelector(),
					snakTypeSelector = this._getSnakTypeSelector();

				if ( propertySelector ) {
					propertySelector.option( 'disabled', key );
				}

				if ( snakTypeSelector ) {
					snakTypeSelector.option( 'disabled', key );
				}

				if ( this._snakRemover ) {
					this._snakRemover[ value ? 'disable' : 'enable' ]();
				}

				if ( this._variation ) {
					this._variation[ value ? 'disable' : 'enable' ]();
				}
			}

			return response;
		},

		/**
		 * Returns an input element with initialized `entityselector` for selecting entities.
		 *
		 * @private
		 *
		 * @return {jQuery}
		 */
		_buildPropertySelector: function () {
			var self = this,
				repoConfig = mw.config.get( 'wbRepo' ),
				repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';

			return $( '<input>' ).entityselector( {
				url: repoApiUrl,
				type: 'property',
				responseErrorFactory: wb.api.RepoApiError.newFromApiResponse
			} )
			.prop( 'placeholder', mw.msg( 'wikibase-snakview-property-input-placeholder' ) )
			.on( 'eachchange', function ( event, oldValue ) {
				// remove out-dated variations
				if ( self._variation ) {
					self.drawSnakTypeSelector();
					self.updateVariation();
					self.drawVariation();
					self._trigger( 'change' );
				}
			} )
			.on( 'entityselectorselected', function ( event, entityId ) {
				self._selectProperty();
			} );
		},

		/**
		 * @private
		 */
		_selectProperty: function () {
			var self = this;

			// Display spinner as long as the ValueView is loading:
			this.$snakValue.empty().append(
				$.createSpinner( 'small' )
			);

			// The "value" variation contains experts that depend on the property and value type. Must
			// be recreated when the property changes. Would be better to do this in updateVariation,
			// and only when the value type changes, but at this point we can't find out any more.
			if ( this._variation ) {
				this._variation.destroy();
				this._variation = null;
			}

			this.updateVariation();
			this.drawSnakTypeSelector();

			// Since it might take a while for the value view to gather its data from the API,
			// the property might not be valid anymore aborting the rendering of the value
			// view.
			if ( this._variation ) {
				$( this._variation ).one( 'afterstartediting', function () {
					self._variation.focus();
				} );
			}

			this.drawVariation();

			this._trigger( 'change' );
		},

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			if ( this._snakRemover ) {
				this._snakRemover.destroy();
				this._snakRemover = null;
			}
			var snakTypeSelector = this._getSnakTypeSelector();
			if ( snakTypeSelector ) {
				snakTypeSelector.destroy();
				snakTypeSelector.element.remove();
			}
			$.Widget.prototype.destroy.call( this );
		},

		_startEditing: function () {
			var deferred = $.Deferred();
			if ( this.options.getSnakRemover ) {
				this._snakRemover = this.options.getSnakRemover( this.element );
			}

			if ( this._variation ) {
				$( this._variation ).one( 'afterstartediting', function () {
					deferred.resolve();
				} );
				this.draw();
				this._variation.startEditing();
			} else {
				this.draw();
				deferred.resolve();
			}
			return deferred.promise();
		},

		/**
		 * @inheritdoc
		 */
		focus: function () {
			if ( this._variation && this._variation.isFocusable() ) {
				this._variation.focus();
			} else {
				var propertySelector = this._getPropertySelector();
				if ( propertySelector ) {
					propertySelector.element.trigger( 'focus' );
				} else {
					this.element.trigger( 'focus' );
				}
			}
		},

		/**
		 * Stops the widget's edit mode.
		 *
		 * @param {boolean} [dropValue=false] If `true`, the widget's value will be reset to the one
		 *        from before edit mode was started.
		 */
		_stopEditing: function ( dropValue ) {
			if ( this._snakRemover ) {
				this._snakRemover.destroy();
				this._snakRemover = null;
			}

			if ( this._variation ) {
				this._variation.stopEditing( dropValue );
			}
			this.drawSnakTypeSelector();

			this.element.off( 'keydown.' + this.widgetName );

			return $.Deferred().resolve().promise();
		},

		/**
		 * Updates this `snakview`'s status.
		 *
		 * @param {string} status May either be 'valid' or 'invalid'
		 */
		updateStatus: function ( status ) {
			if ( status === 'valid' ) {
				this._isValid = true;
			} else if ( status === 'invalid' ) {
				this._isValid = false;
			}
			if ( this._variation ) {
				this._trigger( 'change' );
			}
		},

		/**
		 * Returns the `entityselector` for choosing the `Snak`'s `Property`. Returns `null` if the
		 * `Snak` is created and has a `Property` already. (Once created, the `Property` is immutable.)
		 *
		 * @private
		 *
		 * @return {jQuery.wikibase.entityselector|null}
		 */
		_getPropertySelector: function () {
			if ( this.$property ) {
				return this.$property.children().first().data( 'entityselector' ) || null;
			}
			return null;
		},

		/**
		 * Returns the `snaktypeselector` for choosing the `Snak`'s type. Returns `null` if the `Snak`
		 * is created and has a `Property` already. (Once created, the `Property` is immutable.)
		 *
		 * @private
		 *
		 * @return {jQuery.wikibase.snakview.SnakTypeSelector|null}
		 */
		_getSnakTypeSelector: function () {
			if ( this.$snakTypeSelector ) {
				return this.$snakTypeSelector.children().first().data( 'snaktypeselector' ) || null;
			}
			return null;
		},

		/**
		 * Returns an object representing the currently displayed `Snak`. This is equivalent to the JSON
		 * structure of a `Snak`, except that it does not have to be complete. For example, for a
		 * `PropertyValueSnak` where only the `Property` and `Snak` type are specified, but the value
		 * has not yet been supplied, the returned object would not have a field for the value either.
		 *
		 * @param {Object|datamodel.Snak|null} [value]
		 * @return {datamodel.Snak|Object|undefined} `undefined` in case `value()` is called to
		 *         set the value.
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				this.option( 'value', value );
				return;
			}

			var snakSerializer = new wbserialization.SnakSerializer(),
				serialization = this.options.value instanceof datamodel.Snak
					? snakSerializer.serialize( this.options.value )
					: this.options.value;

			if ( !this.isInEditMode() ) {
				return serialization;
			}

			value = {};

			if ( this.options.locked.property && serialization.property !== undefined ) {
				value.property = serialization.property;
			} else if ( !this.options.locked.property ) {
				var propertyStub = this._getSelectedProperty();

				if ( propertyStub && propertyStub.id !== undefined ) {
					value.property = propertyStub.id;
				}
			}

			if ( this.options.locked.snaktype && serialization.snaktype !== undefined ) {
				value.snaktype = serialization.snaktype;
			} else if ( !this.options.locked.snaktype ) {
				var snakTypeSelector = this._getSnakTypeSelector(),
					snakType = snakTypeSelector && snakTypeSelector.snakType();
				if ( snakType ) {
					value.snaktype = snakType;
				}
			}

			if ( serialization.hash ) {
				value.hash = serialization.hash;
			}

			return this._variation ? $.extend( this._variation.value(), value ) : value;
		},

		/**
		 * If a `datamodel.Snak` instance is passed, the `snakview` is updated to represent the
		 * `Snak`. If no parameter is supplied, the current `Snak` represented by the `snakview` is
		 * returned.
		 *
		 * @param {datamodel.Snak|null} [snak]
		 * @return {datamodel.Snak|null|undefined}
		 */
		snak: function ( snak ) {
			if ( snak !== undefined ) {
				this.value( snak || {} );
				return;
			}

			if ( !this._isValid ) {
				return null;
			}

			var value = this.value();
			if ( value.datavalue instanceof dv.DataValue ) {
				value.datavalue = {
					type: value.datavalue.getType(),
					value: value.datavalue.toJSON()
				};
			}

			var snakDeserializer = new wbserialization.SnakDeserializer();
			try {
				return snakDeserializer.deserialize( value );
			} catch ( e ) {
				return null;
			}
		},

		/**
		 * Sets/Gets the ID of the `Property` for the `Snak` represented by the `snakview`. If no
		 * `Property` is set, `null` is returned.
		 *
		 * @since 0.3 (setter since 0.4)
		 *
		 * @param {string|null} [propertyId]
		 * @return {string|null|undefined}
		 */
		propertyId: function ( propertyId ) {
			if ( propertyId === undefined ) {
				return this.value().property || null;
			} else {
				var value = this.value();

				if ( propertyId !== value.property ) {
					if ( propertyId === null ) {
						delete value.property;
					} else {
						value.property = propertyId;
					}
					this.option( 'value', value );
				}
			}
		},

		/**
		 * Sets/Gets the ID of the `Snak` type for the `Snak` represented by the `snakview`. If no
		 * `Snak` type is set, `null` is returned.
		 *
		 * @see datamodel.Snak.TYPE
		 *
		 * @param {string|null} [snakType]
		 * @return {string|null|undefined}
		 */
		snakType: function ( snakType ) {
			var value = this.value();

			if ( snakType === undefined ) {
				return value.snaktype || null;
			} else if ( snakType === value.snaktype ) {
				return;
			}

			if ( snakType === null ) {
				delete value.snaktype;
			} else {
				// TODO: check whether given snak type is actually valid!
				value.snaktype = snakType;
			}

			this.option( 'value', value );
		},

		/**
		 * Returns the `snakview`'s `Variation` object required for presenting the current `Snak` type.
		 * If a `Snak` type has not been defined yet, `null` is returned.
		 *
		 * @return {Variation|null}
		 */
		variation: function () {
			return this._variation;
		},

		/**
		 * Updates the `Variation` according to the widget's current value.
		 */
		updateVariation: function () {
			var value = this.value(),
				propertyId = value ? value.property : null,
				snakType = value ? value.snaktype : null,
				VariationConstructor = snakType ? variations.getVariation( snakType ) : null;

			this._setDataTypeForSelectedProperty();

			if ( this._variation
				&& ( !propertyId || this._variation.constructor !== VariationConstructor )
			) {
				var variationValue = this._variation.value();

				if ( variationValue.datavalue ) {
					variationValue.datavalue = {
						type: variationValue.datavalue.getType(),
						value: variationValue.datavalue.toJSON()
					};
				}

				this._cachedValues[ this._variation.variationSnakConstructor.TYPE ] = variationValue;

				this.$snakValue.empty();

				// clean destruction of old variation in case variation will change or property not set
				this._variation.destroy();
				this._variation = null;
			}

			if ( !this._variation && propertyId && VariationConstructor ) {
				// Snak type has changed so we need another variation Object!
				this._variation = new VariationConstructor(
					new ViewState( this ),
					this.$snakValue,
					this.options.propertyDataTypeStore,
					this.options.valueViewBuilder,
					this.options.dataTypeStore
				);

				if ( !value.datavalue
					&& this._cachedValues[ snakType ] && this._cachedValues[ snakType ].datavalue
				) {
					value.datavalue = $.extend( {}, this._cachedValues[ snakType ].datavalue );
				}

				// Update Variation with fields not directly managed by the snakview. If necessary
				// within the Variation, those fields should be accessed via the Variation's
				// ViewState object.
				var serializationCopy = $.extend( {}, value );
				delete serializationCopy.property;
				delete serializationCopy.snaktype;
				this._variation.value( serializationCopy );
			}
		},

		/**
		 * (Re-)renders the widget.
		 */
		draw: function () {
			this.updateHash();
			this.drawProperty();
			this.drawSnakTypeSelector();
			this.drawVariation();
		},

		/**
		 * Updates the class list of the DOM element
		 * to contain the right wikibase-snakview-{hash} class if a hash is configured,
		 * and no other wikibase-snakview-{otherHash} classes.
		 */
		updateHash: function () {
			var hash;
			this.element.removeClass( function ( index, className ) {
				var matches = className.match( /\bwikibase-snakview-([0-9a-fA-F]{40})?(\s|$)/g );
				return matches ? matches.join( ' ' ) : '';
			} );
			hash = this.snak() && this.snak().getHash();
			if ( hash ) {
				this.element.addClass( 'wikibase-snakview-' + hash );
			}
		},

		/**
		 * (Re-)renders the Property DOM structure according to the current value. The `Property` DOM
		 * is not (re-)rendered if changing the `Property` is locked via the `locked` option and
		 * previously generated HTML is detected.
		 *
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {Function} return.fail
		 */
		drawProperty: function () {
			var self = this,
				deferred = $.Deferred(),
				propertyId = this.value().property;

			if ( this.options.locked.property
				&& ( this.$property.contents().length || !this.options.drawProperty )
			) {
				return deferred.resolve().promise();
			}

			this._getPropertyDOM( propertyId )
			.done( function ( $property ) {
				self.$property.empty().append( $property );
				deferred.resolve();
			} )
			.fail( function () {
				self.$property.empty().text( propertyId );
				deferred.reject();
			} );

			return deferred.promise();
		},

		/**
		 * Retrieves the DOM structure representing the `Property` of the `Snak` represented by the
		 * `snakview`.
		 *
		 * @private
		 *
		 * @param {string} [propertyId]
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {jQuery} return.$property
		 * @return {Function} return.fail
		 */
		_getPropertyDOM: function ( propertyId ) {
			var self = this,
				deferred = $.Deferred(),
				editable = !this.options.locked.property && this.isInEditMode();

			if ( !propertyId ) {
				if ( editable ) {
					deferred.resolve( this._createPropertyDOM( '' ) );
				} else {
					deferred.resolve( '' );
				}
			} else {
				if ( editable ) {
					this.options.entityIdPlainFormatter.format( propertyId ).done( function ( propertyLabel ) {
						deferred.resolve( self._createPropertyDOM( propertyLabel ) );
					} );
				} else {
					// Property is set already and cannot be changed, display label only:
					return this.options.entityIdHtmlFormatter.format( propertyId );
				}
			}
			return deferred.promise();
		},

		/**
		 * Creates the DOM structure specific for a `Property`, a generic DOM
		 * structure or an input element.
		 *
		 * @private
		 *
		 * @param {string} propertyLabel Rendered label for the `Property`
		 * @return {jQuery|null}
		 */
		_createPropertyDOM: function ( propertyLabel ) {
			var $propertyDom;

			// No Property set for this Snak, serve edit view to specify it:
			var propertySelector = this._getPropertySelector();

			// TODO: use selectedEntity() or other command to set selected entity in both cases!
			if ( propertySelector ) {
				// property selector in DOM already, just replace current value
				var currentValue = propertySelector.widget().val();
				// Impose case-insensitivity:
				if ( propertyLabel.toLowerCase() !== currentValue.toLocaleLowerCase() ) {
					propertySelector.widget().val( propertyLabel );
				}
			} else {
				$propertyDom = this._buildPropertySelector().val( propertyLabel );

				// propagate snakview state:
				$propertyDom.data( 'entityselector' ).option( 'disabled', this.options.disabled );
			}
			return $propertyDom;
		},

		/**
		 * Updates the `SnakTypeSelector` for choosing the `Snak` type. The `SnakTypeSelector` DOM
		 * is not (re-)rendered if changing the `Snak` type is locked via the `locked` option and
		 * previously generated HTML is detected.
		 */
		drawSnakTypeSelector: function () {
			if ( this.options.locked.snaktype && this.$snakTypeSelector.contents().length ) {
				return;
			}

			var snakTypes = variations.getCoveredSnakTypes(),
				selector = this._getSnakTypeSelector();

			if ( !this.isInEditMode()
				|| snakTypes.length <= 1
				|| this.options.locked.snaktype
			) {
				if ( selector ) {
					selector.destroy();
				}
				this.$snakTypeSelector.empty();
				return; // No type selector required!
			}

			var snakType = this.options.value instanceof datamodel.Snak
				? this.options.value.getType()
				: this.options.value.snaktype;

			if ( selector ) {
				// mark current Snak type as chosen one in the menu:
				selector.snakType( snakType );
			} else {
				var $selector = this._buildSnakTypeSelector( snakType );
				this.$snakTypeSelector.empty().append( $selector );
				selector = $selector.data( 'snaktypeselector' );
			}

			// only show selector if a property is chosen:
			this.$snakTypeSelector[ ( this.value().property ? 'show' : 'hide' ) ]();

			// propagate snakview state:
			selector.option( 'disabled', this.options.disabled );
		},

		/**
		 * Renders the `Variation` or placeholder text if no proper `Variation` is available.
		 */
		drawVariation: function () {
			// property ID will be null if not in edit mode and no Snak set or if in edit mode and user
			// didn't choose property yet.
			var self = this,
				value = this.value(),
				propertyId = value ? value.property : null;

			if ( propertyId && this._variation ) {
				$( this._variation ).one( 'afterdraw', function () {
					if ( self.isInEditMode() ) {
						self.variation().startEditing();
					}
				} );
				this.variation().draw();
			} else {
				// remove any remains from previous rendering or initial template (e.g. '$4')
				this.$snakValue.empty();

				if ( propertyId ) {
					// property ID selected but apparently no variation available to handle it
					$( '<span>' ).text( mw.msg( 'wikibase-snakview-choosesnaktype' ) )
					.addClass( this.widgetBaseClass + '-unsupportedsnaktype' )
					.appendTo( this.$snakValue );
					// NOTE: instead of doing this here and checking everywhere whether this._variation
					//  is set, we could as well use variations for displaying system messages like
					//  this, e.g. having a UnsupportedSnakType variation which is not registered for a
					//  specific snak type but is known to updateVariation().
				}
			}
		},

		/**
		 * @private
		 *
		 * @param {string|null} snakType
		 * @return {jQuery}
		 */
		_buildSnakTypeSelector: function ( snakType ) {
			var self = this,
				$anchor = $( '<span>' ),
				// initiate snak type selector widget which is a normal widget just without a
				// jQuery.widget.bridge...
				selector = new $.wikibase.snakview.SnakTypeSelector( {}, $anchor );

			// ...add the data information nevertheless:
			$anchor.data( 'snaktypeselector', selector );

			// Set value before binding the change event handler to avoid handling first
			// useless change event
			selector.snakType( snakType );

			var changeEvent = ( selector.widgetEventPrefix + 'change' ).toLowerCase();

			// bind user interaction on selector to snakview's state:
			$anchor.on( changeEvent + '.' + this.widgetName, function ( event ) {
				self.updateVariation();
				self.drawVariation();
				if ( self._variation ) {
					self._variation.focus();
				}
				self._trigger( 'change' );
			} );

			return $anchor;
		},

		hidePropertyLabel: function () {
			this.$property.hide();
		},

		showPropertyLabel: function () {
			this.$property.show();
		},

		_getSelectedProperty: function () {
			var propertySelector = this._getPropertySelector();

			return propertySelector && propertySelector.selectedEntity();
		},

		_setDataTypeForSelectedProperty: function () {
			var property = this._getSelectedProperty();

			if ( property && property.datatype ) {
				this.options.propertyDataTypeStore.setDataTypeForProperty( property.id, property.datatype );
			}
		}
	} );

	$.extend( $.wikibase.snakview, existingSnakview );

}( wikibase, dataValues ) );
