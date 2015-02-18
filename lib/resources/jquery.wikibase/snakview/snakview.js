( function( mw, wb, $, dv ) {
	'use strict';

	// Back-up components already initialized in the namespace to re-apply them after initializing
	// the snakview widget.
	$.wikibase = $.wikibase || {};
	var existingSnakview = $.wikibase.snakview || {};

	// Erase existing object to prevent jQuery.Widget detecting an existing constructor:
	delete $.wikibase.snakview;

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying and editing `wikibase.datamodel.Snak` objects.
 * @see wikibase.datamodel.Snak
 * @class jQuery.wikibase.snakview
 * @extends jQuery.ui.TemplatedWidget
 * @since 0.3
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {Object|wikibase.datamodel.Snak|null} [options.value]
 *        The `Snak` this `snakview` should represent initially. If omitted, an empty view will be
 *        served, ready to take some input by the user. The value may be overwritten later, by using
 *        the `value()` or the `snak()` function.
 *        Default: `{ snaktype: wikibase.datamodel.PropertyValueSnak.TYPE }`
 * @param {Object|boolean} [options.locked=false]
 *        Key-value pairs determining which `snakview` elements to lock from being edited by the
 *        user. May also be a boolean value enabling/disabling all elements. If `false`, no elements
 *        will be locked.
 * @param {boolean} [options.autoStartEditing=true]
 *        Whether the `snakview` should switch to edit mode automatically upon initialization if its
 *        initial value is empty.
 * @param {wikibase.store.EntityStore} options.entityStore
 *        Required for dynamically gathering `Entity`/`Property` information.
 * @param {wikibase.ValueViewBuilder} options.valueViewBuilder
 *        Required to interfacing a `snakview` "value" `Variation` to `jQuery.valueview`.
 * @param {dataTypes.DataTypeStore} options.dataTypeStore
 *        Required to retrieve and evaluate a proper `dataTypes.DataType` object when interacting on
 *        a "value" `Variation`.
 */
/**
 * @event afterstartediting
 * Triggered after having started the widget's edit mode.
 * @param {jQuery.Event} event
 */
/**
 * @event stopediting
 * Triggered when stopping the widget's edit mode.
 * @param {jQuery.Event} event
 * @param {boolean} dropValue
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
		templateParams: [ '', '', '' ],
		templateShortCuts: {
			'$property': '.wikibase-snakview-property',
			'$snakValue': '.wikibase-snakview-value',
			'$snakTypeSelector': '.wikibase-snakview-typeselector'
		},
		value: {
			snaktype: wb.datamodel.PropertyValueSnak.TYPE
		},
		locked: {
			property: false,
			snaktype: false
		},
		autoStartEditing: true,
		entityStore: null,
		valueViewBuilder: null,
		dataTypeStore: null
	},

	/**
	 * The DOM node of the `entityselector` for choosing a `Property` or the node with plain text of
	 * the `Property`'s label.
	 * @property {jQuery}
	 * @readonly
	 */
	$property: null,

	/**
	 * The DOM node of the `Snak`'s value, some message if the value is not supported or "no value"/
	 * "some value" message.
	 * @property {jQuery}
	 * @readonly
	 */
	$snakValue: null,

	/**
	 * The DOM node of the `snaktypeselector` widget.
	 * @type {jQuery}
	 * @readonly
	 */
	$snakTypeSelector: null,

	/**
	 * `Variation` object responsible for presenting the essential parts of a certain kind of
	 * `Snak`. May be `null` if an unsupported `Snak` type is represented by the `snakview`. In this
	 * case, the `snakview` won't be able to display the `Snak` but displays an appropriate message
	 * instead.
	 * @property {jQuery.wikibase.snakview.variations.Variation|null}
	 * @private
	 */
	_variation: null,

	/**
	 * Cache for the values of specific `jQuery.wikibase.snakview.variation`s used to have those
	 * values restored when toggling the `Snak` type.
	 * @property {Object}
	 * @private
	 */
	_cachedValues: null,

	/**
	 * @property {boolean}
	 * @private
	 */
	_isInEditMode: false,

	/**
	 * Caching whether to move the focus from the `Property` input to the value input after pressing
	 * the TAB key.
	 * @property {boolean}
	 * @private
	 */
	_tabToValueView: false,

	/**
	 * Whether then `snakview`'s value is regarded "valid" at the moment.
	 * @property {boolean}
	 * @private
	 */
	_isValid: false,

	/**
	 * @inheritdoc
	 * @protected
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		this._cachedValues = {};

		this.value( this.options.value );

		if( this.option( 'autoStartEditing' ) && !this.snak() ) {
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
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			if(
				value !== null
				&& !$.isPlainObject( value ) && !( value instanceof wb.datamodel.Snak )
			) {
				throw new Error( 'The given value has to be a plain object, an instance of '
					+ 'wikibase.datamodel.Snak, or null' );
			}
		} else if( key === 'locked' && typeof value === 'boolean' ) {
			var locked = value;
			value = $.extend( {}, $.wikibase.snakview.prototype.options.locked );
			$.each( $.wikibase.snakview.prototype.options.locked, function( k, v ) {
				value[k] = locked;
			} );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'value' ) {
			value = this.value();

			this._updateVariation( value );

			this.draw();
		} else if( key === 'disabled' ) {
			var propertySelector = this._getPropertySelector(),
				snakTypeSelector = this._getSnakTypeSelector();

			if( propertySelector ) {
				propertySelector.option( 'disabled', key );
			}

			if( snakTypeSelector ) {
				snakTypeSelector.option( 'disabled', key );
			}

			if( this._variation ) {
				this._variation[value ? 'disable' : 'enable']();
			}
		}

		return response;
	},

	/**
	 * Returns an input element with initialized `entityselector` for selecting entities.
	 * @private
	 *
	 * @return {jQuery}
	 */
	_buildPropertySelector: function() {
		var self = this,
			repoConfig = mw.config.get( 'wbRepo' ),
			repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';

		return $( '<input />' ).entityselector( {
			url: repoApiUrl,
			type: 'property'
		} )
		.prop( 'placeholder', mw.msg( 'wikibase-snakview-property-input-placeholder' ) )
		.on( 'blur', function( event ) {
			self._tabToValueView = false;
		} )
		.on( 'eachchange', function( event, oldValue ) {
			// remove out-dated variations
			if( self._variation ) {
				self.drawSnakTypeSelector();
				self._updateVariation( self.value() );
				self.drawVariation();
				self._trigger( 'change' );
			}
		} )
		.on( 'entityselectorselected', function( e, entityId ) {
			// Display spinner as long as the ValueView is loading:
			self.$snakValue.empty().append(
				$( '<div/>' ).append( $( '<span/>' ).addClass( 'mw-small-spinner' ) )
			);

			self.options.entityStore.get( entityId ).done( function( entity ) {
				self._updateVariation( self.value() );
				self.drawSnakTypeSelector();
				self.drawVariation();

				self._trigger( 'change' );

				// Since it takes a while for the value view to gather its data from the API,
				// the property might not be valid anymore aborting the rendering of the value
				// view.
				if( self._tabToValueView && self._variation ) {
					$( self._variation ).one( 'afterdraw', function() {
						self._variation.focus();
					} );
				}
			} );
		} );
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		var snakTypeSelector = this._getSnakTypeSelector();
		if( snakTypeSelector ) {
			snakTypeSelector.destroy();
			snakTypeSelector.element.remove();
		}
		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * Starts the widget's edit mode.
	 */
	startEditing: function() {
		if( this.isInEditMode() ) {
			return;
		}

		var self = this;

		this._isInEditMode = true;

		this.element.on( 'keydown.' + this.widgetName, function( event ) {
			if ( self.options.disabled ) {
				return;
			}

			var propertySelector = self._getPropertySelector();

			if ( event.keyCode === $.ui.keyCode.TAB && !self._variation ) {
				event.stopPropagation();
				// When pressing TAB in the property input element while the value input element
				// does not yet exist, we assume that the user wants to auto-complete/select the
				// currently suggested property and tab into the value element. Since the API needs
				// to be queried to construct the correct value input, the intended action needs to
				// be cached and triggered as soon as the value input has been created.
				if ( propertySelector && event.target === propertySelector.element[0] ) {
					if( self._getPropertySelector().selectedEntity() ) {
						self._tabToValueView = true;
						event.preventDefault();
					}
				}
			}
		} );

		if( this._variation ) {
			$( this._variation ).one( 'afterstartediting', function() {
				self._trigger( 'afterstartediting' );
			} );
			this.draw();
			this._variation.startEditing();
		} else {
			this.draw();
			this._trigger( 'afterstartediting' );
		}
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		if( this._variation && this._variation.isFocusable() ) {
			this._variation.focus();
		} else {
			var propertySelector = this._getPropertySelector();
			if( propertySelector ) {
				propertySelector.element.focus();
			} else {
				this.element.focus();
			}
		}
	},

	/**
	 * Stops the widget's edit mode.
	 *
	 * @param {boolean} [dropValue=false] If `true`, the widget's value will be reset to the one
	 *        from before edit mode was started.
	 */
	stopEditing: function( dropValue ) {
		if( !this.isInEditMode() ) {
			return;
		}

		var snak = this.snak();

		this._trigger( 'stopediting', null, [dropValue] );

		this._isInEditMode = false;

		if( this._variation ) {
			this._variation.stopEditing( dropValue );

			if( !dropValue ) {
				// TODO: "this.snak( this.snak() )" is supposed to work to update the Snak. However,
				// the Variation asking the ValueView returns null as soon as edit mode is left.
				this.snak( snak );
			}
		}

		if( !this._variation || dropValue ) {
			this.value( this.options.value );
		}

		// TODO: Should throw an error somewhere when trying to leave edit mode while this.snak()
		//  still returns null.

		this.element.off( 'keydown.' + this.widgetName );

		this._trigger( 'afterStopEditing', null, [dropValue] );
	},

	/**
	 * Cancels editing. (Short-cut for `stopEditing( true )`.)
	 */
	cancelEditing: function() {
		return this.stopEditing( true );
	},

	/**
	 * Updates this `snakview`'s status.
	 * @since 0.4
	 *
	 * @param {string} status May either be 'valid' or 'invalid'
	 */
	updateStatus: function( status ) {
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
	 * Returns whether the `snakview`'s `Snak` is valid in its current state.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isValid: function() {
		return this._isValid;
	},

	/**
	 * Returns whether the current value matches the one the `snakview` was initialized with by
	 * comparing the (deserialized) `Snak` objects of that stages.
	 * @since 0.5
	 *
	 * @return {boolean}
	 */
	isInitialValue: function() {
		var currentSnak = this.snak(),
			initialSnak;

		if( this.options.value instanceof wb.datamodel.Snak ) {
			initialSnak = this.options.value;
		} else {
			var snakDeserializer = new wb.serialization.SnakDeserializer();
			try {
				initialSnak = snakDeserializer.deserialize( this.options.value );
			} catch( e ) {
				initialSnak = null;
			}
		}

		if( !initialSnak && !currentSnak ) {
			return true;
		}
		return currentSnak && currentSnak.equals( initialSnak );
	},

	/**
	 * @return {boolean}
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Returns the `entityselector` for choosing the `Snak`'s `Property`. Returns `null` if the
	 * `Snak` is created and has a `Property` already. (Once created, the `Property` is immutable.)
	 * @private
	 *
	 * @return {jQuery.wikibase.entityselector|null}
	 */
	_getPropertySelector: function() {
		if( this.$property ) {
			return this.$property.children().first().data( 'entityselector' ) || null;
		}
		return null;
	},

	/**
	 * Returns the `snaktypeselector` for choosing the `Snak`'s type. Returns `null` if the `Snak`
	 * is created and has a `Property` already. (Once created, the `Property` is immutable.)
	 * @private
	 *
	 * @return {jQuery.wikibase.snakview.SnakTypeSelector|null}
	 */
	_getSnakTypeSelector: function() {
		if( this.$snakTypeSelector ) {
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
	 * @param {Object|wikibase.datamodel.Snak|null} [value]
	 * @return {wikibase.datamodel.Snak|Object|undefined} `undefined` in case `value()` is called to
	 *         set the value.
	 */
	value: function( value ) {
		if( value !== undefined ) {
			this.option( 'value', value );
			return;
		}

		var snakSerializer = new wikibase.serialization.SnakSerializer(),
			serialization = this.options.value instanceof wb.datamodel.Snak
				? snakSerializer.serialize( this.options.value )
				: this.options.value;

		if( !this.isInEditMode() ) {
			return serialization;
		}

		value = {};

		if( this.options.locked.property && serialization.property !== undefined ) {
			value.property = serialization.property;
		} else if( !this.options.locked.property ) {
			var propertySelector = this._getPropertySelector(),
				propertyStub = propertySelector && propertySelector.selectedEntity();
			if( propertyStub && propertyStub.id !== undefined ) {
				value.property = propertyStub.id;
			}
		}

		if( this.options.locked.snaktype && serialization.snaktype !== undefined ) {
			value.snaktype = serialization.snaktype;
		} else if( !this.options.locked.snaktype ) {
			var snakTypeSelector = this._getSnakTypeSelector(),
				snakType = snakTypeSelector && snakTypeSelector.snakType();
			if( snakType ) {
				value.snaktype = snakType;
			}
		}

		return this._variation ? $.extend( this._variation.value(), value ) : value;
	},

	/**
	 * If a `wikibase.datamodel.Snak` instance is passed, the `snakview` is updated to represent the
	 * `Snak`. If no parameter is supplied, the current `Snak` represented by the `snakview` is
	 * returned.
	 * @since 0.4
	 *
	 * @param {wikibase.datamodel.Snak|null} [snak]
	 * @return {wikibase.datamodel.Snak|null|undefined}
	 */
	snak: function( snak ) {
		if( snak !== undefined ) {
			this.value( snak || {} );
			return;
		}

		var value = this.value();
		if( value.datavalue instanceof dv.DataValue ) {
			value.datavalue = {
				type: value.datavalue.getType(),
				value: value.datavalue.toJSON()
			};
		}

		var snakDeserializer =  new wb.serialization.SnakDeserializer();
		try {
			return snakDeserializer.deserialize( value );
		} catch( e ) {
			return null;
		}
	},

	/**
	 * Sets/Gets the ID of the `Property` for the `Snak` represented by the `snakview`. If no
	 * `Property` is set, `null` is returned.
	 * @since 0.3 (setter since 0.4)
	 *
	 * @param {string|null} [propertyId]
	 * @return {string|null|undefined}
	 */
	propertyId: function( propertyId ) {
		if( propertyId === undefined ) {
			return this.value().property || null;
		} else {
			var value = this.value();

			if( propertyId !== value.property ) {
				if( propertyId === null ) {
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
	 * @see wikibase.datamodel.Snak.TYPE
	 * @since 0.4
	 *
	 * @param {string|null} [snakType]
	 * @return {string|null|undefined}
	 */
	snakType: function( snakType ) {
		var value = this.value();

		if( snakType === undefined ) {
			return value.snaktype || null;
		} else if( snakType === value.snaktype ) {
			return;
		}

		if( snakType === null ) {
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
	 * @since 0.4
	 *
	 * @return {jQuery.wikibase.snakview.variations.Variation|null}
	 */
	variation: function() {
		return this._variation;
	},

	/**
	 * Checks whether the `Snak` type has been changed by the user and will build a new `Variation`
	 * object for that type if necessary.
	 * @private
	 * @since 0.4
	 *
	 * @param {Object} value (In)complete `Snak` serialization.
	 */
	_updateVariation: function( value ) {
		var variationsFactory = $.wikibase.snakview.variations,
			snakType = value ? value.snaktype : null,
			VariationConstructor = snakType ? variationsFactory.getVariation( snakType ) : null,
			propertyId = value ? value.property : null;

		if( this._variation
			&& ( !propertyId || this._variation.constructor !== VariationConstructor )
		) {
			var variationValue = this._variation.value();

			if( variationValue.datavalue ) {
				variationValue.datavalue = {
					type: variationValue.datavalue.getType(),
					value: variationValue.datavalue.toJSON()
				};
			}

			this._cachedValues[this._variation.variationSnakConstructor.TYPE] = variationValue;

			this.$snakValue.empty();

			// clean destruction of old variation in case variation will change or property not set
			this._variation.destroy();
			this._variation = null;
		}

		if( !this._variation && propertyId && VariationConstructor ) {
			// Snak type has changed so we need another variation Object!
			this._variation = new VariationConstructor(
				new $.wikibase.snakview.ViewState( this ),
				this.$snakValue,
				this.options.entityStore,
				this.options.valueViewBuilder,
				this.options.dataTypeStore
			);

			if( !value.datavalue
				&& this._cachedValues[snakType] && this._cachedValues[snakType].datavalue
			) {
				value.datavalue = $.extend( {}, this._cachedValues[snakType].datavalue );
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
	 * Renders the `snakview`'s current state considering edit mode, current value, etc..
	 * @since 0.4
	 */
	draw: function() {
		var self = this,
			value = this.value(),
			propertyId = value ? value.property : null;

		// NOTE: Order of these shouldn't matter; If for any reasons draw functions start changing
		//  the outcome of the variation (or Snak type), then something must be incredibly wrong!
		if( propertyId ) {
			this.options.entityStore.get( propertyId ).done( function( fetchedProperty ) {
				self.drawProperty(
					fetchedProperty ? fetchedProperty.getContent() : null,
					fetchedProperty ? fetchedProperty.getTitle() : null
				);
			} );
		} else {
			this.drawProperty( null, null );
		}
		this.drawSnakTypeSelector();
		this.drawVariation();
	},

	/**
	 * Ensures the current `Snak`'s `Property` is displayed properly. If no `Snak` is set, the input
	 * form for specifying the `Snak`'s `Property` will be rendered.
	 * @since 0.4
	 *
	 * @param {wikibase.datamodel.Property|null} property
	 * @param {mediawiki.Title|null} title Only supposed to be `null` if `property` is `null`.
	 */
	drawProperty: function( property, title ) {
		var $propertyDom,
			value = this.value(),
			propertyId = value ? value.property : null;

		if( this.options.locked.property || !this.isInEditMode() ) {
			// property set and can't be changed afterwards, only display label
			$propertyDom = property
				? wb.utilities.ui.buildLinkToEntityPage( property, title )
				// shouldn't usually happen, only in non-edit mode, while no Snak is set:
				: wb.utilities.ui.buildMissingEntityInfo( propertyId, wb.datamodel.Property );
		} else {
			// no property set for this Snak, serve edit view to specify it:
			var propertySelector = this._getPropertySelector(),
				propertyLabel = wb.utilities.ui.buildPrettyEntityLabelText( property );

			// TODO: use selectedEntity() or other command to set selected entity in both cases!
			if( propertySelector && property ) {
				// property selector in DOM already, just replace current value
				var currentValue = propertySelector.widget().val();
				// Impose case-insensitivity:
				if( propertyLabel.toLowerCase() !== currentValue.toLocaleLowerCase() ) {
					propertySelector.widget().val( propertyLabel );
				}
				return;
			} else if( !propertySelector ) {
				// Create property selector and set value:
				$propertyDom = this._buildPropertySelector().val( propertyLabel );

				// propagate snakview state:
				$propertyDom.data( 'entityselector' ).option( 'disabled', this.options.disabled );
			} else {
				return;
			}
		}

		this.$property.empty().append( $propertyDom );
	},

	/**
	 * Updates the `snaktypeselector` for choosing the `Snak` type.
	 * @since 0.4
	 */
	drawSnakTypeSelector: function() {
		var snakTypes = $.wikibase.snakview.variations.getCoveredSnakTypes(),
			selector = this._getSnakTypeSelector();

		if(
			!this.isInEditMode()
			|| snakTypes.length <= 1
			|| this.options.locked.snaktype
		) {
			if( selector ) {
				selector.destroy();
			}
			this.$snakTypeSelector.empty();
			return; // No type selector required!
		}

		if( !selector ) {
			var $selector = this._buildSnakTypeSelector();
			this.$snakTypeSelector.empty().append( $selector );
			selector = $selector.data( 'snaktypeselector' );
		}

		// mark current Snak type as chosen one in the menu:
		selector.snakType(
			this.options.value instanceof wb.datamodel.Snak
				? this.options.value.getType()
				: this.options.value.snaktype
		);

		// only show selector if a property is chosen:
		this.$snakTypeSelector[ ( this.value().property ? 'show' : 'hide' ) ]();

		// propagate snakview state:
		if ( this.options.disabled ) {
			selector.disable();
		} else {
			selector.enable();
		}
	},

	/**
	 * Renders the `Variation` or placeholder text if no proper `Variation` is available.
	 * @since 0.4
	 */
	drawVariation: function() {
		// property ID will be null if not in edit mode and no Snak set or if in edit mode and user
		// didn't choose property yet.
		var self = this,
			value = this.value(),
			propertyId = value ? value.property : null;

		if( propertyId && this._variation ) {
			$( this._variation ).one( 'afterdraw', function() {
				if( self.isInEditMode() ) {
					self.variation().startEditing();
				}
			} );
			this.variation().draw();
		} else {
			// remove any remains from previous rendering or initial template (e.g. '$4')
			this.$snakValue.empty();

			if( propertyId ) {
				// property ID selected but apparently no variation available to handle it
				$( '<span/>' ).text( mw.msg( 'wikibase-snakview-choosesnaktype' ) )
				.addClass( this.widgetBaseClass + '-unsupportedsnaktype' )
				.appendTo( this.$snakValue );
				// NOTE: instead of doing this here and checking everywhere whether this._variation
				//  is set, we could as well use variations for displaying system messages like
				//  this, e.g. having a UnsupportedSnakType variation which is not registered for a
				//  specific snak type but is known to _updateVariation().
			}
		}
	},

	/**
	 * @private
	 * @since 0.4
	 *
	 * @return {jQuery}
	 */
	_buildSnakTypeSelector: function() {
		var self = this,
			$anchor = $( '<span/>' ),
			// initiate snak type selector widget which is a normal widget just without a
			// jQuery.widget.bridge...
			selector = new $.wikibase.snakview.SnakTypeSelector( {}, $anchor );

		// ...add the data information nevertheless:
		$anchor.data( 'snaktypeselector', selector );

		var changeEvent = ( selector.widgetEventPrefix + 'change' ).toLowerCase();

		// bind user interaction on selector to snakview's state:
		$anchor.on( changeEvent + '.' + this.widgetName, function( event ) {
			self._updateVariation( self.value() );
			self.drawVariation();
			if( self._variation ) {
				self._variation.focus();
			}
			self._trigger( 'change' );
		} );

		return $anchor;
	},

	/**
	 * @since 0.5
	 */
	hidePropertyLabel: function() {
		this.$property.hide();
	},

	/**
	 * @since 0.5
	 */
	showPropertyLabel: function() {
		this.$property.show();
	},

	/**
	 * Returns whether the property label currently is visible.
	 * @since 0.5
	 */
	propertyLabelIsVisible: function() {
		return this.$property.is( ':visible' );
	}
} );

$.extend( $.wikibase.snakview, existingSnakview );

}( mediaWiki, wikibase, jQuery, dataValues ) );
