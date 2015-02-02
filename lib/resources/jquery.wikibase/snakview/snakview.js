( function( mw, wb, $ ) {
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
 * @costructor
 *
 * @param {Object} options
 * @param {Object|wikibase.datamodel.Snak|null} [options.value]
 *        The `Snak` this `snakview` should represent initially. If omitted, an empty view will be
 *        served, ready to take some input by the user. The value may be overwritten later, by using
 *        the `value()` or the `snak()` function.
 *        Default: `{ property: null, snaktype: wikibase.datamodel.PropertyValueSnak.TYPE }`
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
 * @param {wikibase.datamodel.Snak|null} newSnak
 *        The `Snak` which will be displayed after editing has stopped. Normally, this is the `Snak`
 *        representing the last state of the `snakview` during edit mode but can also be the `Snak`
 *        from before edit mode in case editing has been cancelled.
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
			property: null,
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
	 * The `Property` id of the `Snak` currently represented by the `snakview`.
	 * @property {string}
	 * @private
	 */
	_propertyId: null,

	/**
	 * The `Snak` type of the `Snak` currently represented by the `snakvview`.
	 * @property {string}
	 * @private
	 */
	_snakType: null,

	/**
	 * The `Snak` from before edit mode was started.
	 * @property {wikibase.datamodel.Snak|null}
	 * @private
	 */
	_initialSnak: null,

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

		this.value( this.option( 'value' ) || {} );

		if( this.option( 'autoStartEditing' ) && !this.snak() ) {
			// If no Snak is represented, offer UI to build one.
			// This clearly implies draw() since it requires visual changes!
			this.startEditing();
		}
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_setOption: function( key, value ) {
		if ( key === 'locked' && typeof value === 'boolean' ) {
			var locked = value;
			value = $.extend( {}, $.wikibase.snakview.prototype.options.locked );
			$.each( $.wikibase.snakview.prototype.options.locked, function( k, v ) {
				value[k] = locked;
			} );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.draw();
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
				self.propertyId( null );
				self._trigger( 'change' );
			}
		} )
		.on( 'entityselectorselected', function( e, entityId ) {
			// Display spinner as long as the value view is loading. There is no need to display the
			// spinner when the selected item actually has not changed since the variation will stay
			// in place.
			if( !self._propertyId || self._propertyId !== entityId ) {
				// Reset the cached property id for re-rendering being triggered as soon as the new
				// property's attributes have been received:
				self.propertyId( null );

				self.$snakValue.empty().append(
					$( '<div/>' ).append( $( '<span/>' ).addClass( 'mw-small-spinner' ) )
				);
			}

			self.options.entityStore.get( entityId ).done( function( entity ) {
				self.propertyId( entityId );

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

		this._initialSnak = this.snak();
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

		var newSnak = null;

		if( dropValue ) {
			newSnak = this._initialSnak;
		} else if( this._variation ) {
			newSnak = this.snak();
		}

		this._trigger( 'stopediting', [!!dropValue, newSnak] );

		this._isInEditMode = false;
		this._initialSnak = null;

		if( this._variation ) {
			this._variation.stopEditing( dropValue );
		}

		// update view; will remove edit interfaces and represent value statically
		this._setValue( newSnak !== null ? this._serializeSnak( newSnak ) : {} );
		// TODO: Should throw an error somewhere when trying to leave edit mode while this.snak()
		//  still returns null. For now, setting {} is a simple solution for non-existent error
		//  handling in the snak UI.

		this.element.off( 'keydown.' + this.widgetName );

		this._trigger( 'afterStopEditing', null, [dropValue, newSnak] );
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
	 * Returns whether the current `Snak` matches the one the `snakview` has been initialized with.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isInitialSnak: function() {
		var snak = this.snak(),
			initialSnak = this.initialSnak();

		if( !initialSnak && !snak ) {
			// No snaks at all, but we consider this situation as having same Snaks anyhow.
			return true;
		}
		return snak && snak.equals( initialSnak );
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
	 * Returns the initial value from before edit mode was started. If not in edit mode, this will
	 * return the same as `value()`.
	 *
	 * @return {Object}
	 */
	initialValue: function() {
		return this.isInEditMode() ? this._serializeSnak( this.initialSnak() ) : this._getValue();
	},

	/**
	 * Just like `initialValue()`, but returns a `Snak` object or `null` if there was no `Snak` set
	 * before starting edit mode.
	 *
	 * @return {wikibase.datamodel.Snak|null}
	 */
	initialSnak: function() {
		return this.isInEditMode() ? this._initialSnak : this.snak();
	},

	/**
	 * Returns an object representing the currently displayed `Snak`. This is equivalent to the JSON
	 * structure of a `Snak`, except that it does not have to be complete. For example, for a
	 * `PropertyValueSnak` where only the `Property` and `Snak` type are specified, but the value
	 * has not yet been supplied, the returned object would not have a field for the value either.
	 *
	 * @param {Object|wikibase.datamodel.Snak|null} [value]
	 * @return {wikibase.datamodel.Snak|null|undefined} `undefined` in case `value()` is called to
	 *         set the value.
	 */
	value: function( value ) {
		if( value === undefined ) {
			return this._getValue();
		}
		if( value !== null && typeof value !== 'object' ) {
			throw new Error( 'The given value has to be a plain object, an instance of '
				+ 'wikibase.datamodel.Snak, or null' );
		}
		this._setValue( value instanceof wb.datamodel.Snak ? this._serializeSnak( value ) : value );
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.Snak} snak
	 * @return {Object}
	 */
	_serializeSnak: function( snak ) {
		var snakSerializer = new wikibase.serialization.SnakSerializer();
		return snakSerializer.serialize( snak );
	},

	/**
	 * @private
	 *
	 * @return {Object}
	 */
	_getValue: function() {
		var value = {
			property: this.propertyId(),
			snaktype: this.snakType()
		};

		if( !this._variation ) {
			return value;
		}

		return $.extend( this._variation.value(), value );
	},

	/**
	 * Updates the `snakview` to represent a given `Snak` in form of a plain object. The given
	 * object can have all (or a subset of) fields a serialized `wikibase.datamodel.Snak` features.
	 * @private
	 * @since 0.4
	 *
	 * @param {Object|null} value
	 */
	_setValue: function( value ) {
		if( this._snakType && this._variation ) {
			this._cachedValues[this._snakType] = this._variation.value();
		}

		value = value || {};

		this._propertyId = value.property || null;
		this._snakType = value.snaktype || null ;

		this._updateVariation();

		if( this._variation ) {
			// give other Snak information to variation object. Remove basic info since these should
			// rather be accessed via the variation's ViewState object. Also, use a fresh object so
			// the given object doesn't change for outside world.
			var valueCopy = $.extend( {}, value );
			delete valueCopy.property;
			delete valueCopy.snaktype;

			this._variation.value( valueCopy );
		}

		this.draw();
	},

	/**
	 * Updates specifics of the value.
	 * @private
	 *
	 * @param {Object} changes
	 */
	_updateValue: function( changes ) {
		this._setValue( $.extend( this._getValue(), changes ) );
	},

	/**
	 * If a `wikibase.datamodel.Snak` instance is passed, the `snakview` is updated to represent the
	 * `Snak`. If no parameter is supplied, the current `Snak` represented by the `snakview` or
	 * `null` if the `snakview is in edit mode is returned.
	 * @since 0.4
	 *
	 * @param {wikibase.datamodel.Snak|null} [snak]
	 * @return {wikibase.datamodel.Snak|null}
	 */
	snak: function( snak ) {
		if( snak === undefined ) {
			// factory method will fail when essential data is not yet defined!
			// TODO: variations should have a function to ask whether fully defined yet
			try {
				// NOTE: can still be null if user didn't enter essential information in variation's UI
				var value = this.value();
				if( value.datavalue ) {
					value.datavalue = {
						type: value.datavalue.getType(),
						value: value.datavalue.toJSON()
					};
				}
				return ( new wb.serialization.SnakDeserializer() ).deserialize( value );
			} catch( e ) {
				return null;
			}
			// TODO: have a cached version of that snak! Not only for performance, but also to allow
			//  x.snak() === x.snak() which would return false because a new instance of wb.datamodel.Snak
			//  would be factored on each call. On the other hand, wb.datamodel.Snak.equals() should be used.
			// NOTE: One possibility would be to use the Flyweight pattern in wb.datamodel.Snak factories.
		}
		if( snak !== null && !( snak instanceof wb.datamodel.Snak ) ) {
			throw new Error( 'The given value has to be null or an instance of wikibase.datamodel.Snak' );
		}
		return this.value( snak );
	},

	/**
	 * Returns the `Property` ID of the `Property` chosen for this `Snak` or `null` if none is set.
	 * Equal to `.value().getPropertyId()`, but might be set while `.value()` still returns `null`,
	 * e.g. if `Property` has been selected or pre-defined while value or `Snak` type are not set
	 * yet.
	 * If a `Property` is passed, the `snakview` `Snak`'s `Property` reference will be updated.
	 * @since 0.3 (setter since 0.4)
	 *
	 * @return {string|null|undefined}
	 */
	propertyId: function( propertyId ) {
		if( propertyId === undefined ) {
			return this._propertyId;
		}
		if( propertyId !== this._propertyId ) {
			this._updateValue( {
				property: propertyId
			} );
		}
	},

	/**
	 * Returns the `Snak` type ID in use for the `Snak` represented by the `snakview` or `null` if
	 * not defined. Equal to `.value().getType()`, but might be set while `.value()` still returns
	 * `null`, e.g. if `Snak` type has been selected or pre-defined while other required information
	 * for constructing the `Snak` object has not been defined yet.
	 * If a `Snak` type is passed, the `snakview` `Snak`'s type will be updated.
	 * @since 0.4
	 *
	 * @param {string|null} [snakType]
	 * @return {string|null|undefined}
	 */
	snakType: function( snakType ) {
		if( snakType === undefined ) {
			return this._snakType;
		}
		if( snakType !== this._snakType ) {
			// TODO: check whether given snak type is actually valid!
			var changes = {
				snaktype: snakType
			};

			if( this._cachedValues[snakType] && this._cachedValues[snakType].datavalue ) {
				$.extend( changes, {
					datavalue: {
						type: this._cachedValues[snakType].datavalue.getType(),
						value: this._cachedValues[snakType].datavalue.toJSON()
					}
				} );
			}

			this._updateValue( changes );
		}
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
	 */
	_updateVariation: function() {
		var variationsFactory = $.wikibase.snakview.variations,
			snakType = this._snakType,
			VariationConstructor = variationsFactory.getVariation( snakType );

		if( this._variation
			&& ( !this._propertyId || this._variation.constructor !== VariationConstructor )
		) {

			this.$snakValue.empty();

			// clean destruction of old variation in case variation will change or property not set
			this._variation.destroy();
			this._variation = null;
		}

		if( !this._variation && this._propertyId && VariationConstructor ) {
			// Snak type has changed so we need another variation Object!
			this._variation = new VariationConstructor(
				new $.wikibase.snakview.ViewState( this ),
				this.$snakValue,
				this.options.entityStore,
				this.options.valueViewBuilder,
				this.options.dataTypeStore
			);
		}
	},

	/**
	 * Renders the `snakview`'s current state considering edit mode, current value, etc..
	 * @since 0.4
	 */
	draw: function() {
		var self = this;

		// NOTE: Order of these shouldn't matter; If for any reasons draw functions start changing
		//  the outcome of the variation (or Snak type), then something must be incredibly wrong!
		if( this._propertyId ) {
			this.options.entityStore.get( this._propertyId ).done( function( fetchedProperty ) {
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
		var $propertyDom, propertyId = this._propertyId;

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
		selector.snakType( this.snakType() );

		// only show selector if a property is chosen:
		this.$snakTypeSelector[ ( this._propertyId ? 'show' : 'hide' ) ]();

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
		var propertyId = this._propertyId,
			self = this;

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
			self.snakType( selector.snakType() );
			if( self._variation ) {
				self._variation.focus();
			}
			if ( self.snak() ) {
				self._trigger( 'change' );
			}
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

}( mediaWiki, wikibase, jQuery ) );
