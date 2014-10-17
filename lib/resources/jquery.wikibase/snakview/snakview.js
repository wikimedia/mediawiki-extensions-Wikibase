/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	// Back-up components already initialized in the namespace to re-apply them after initializing
	// the snakview widget.
	$.wikibase = $.wikibase || {};
	var existingSnakview = $.wikibase.snakview || {};

	// Erase existing object to prevent jQuery.Widget detecting an existing constructor:
	delete $.wikibase.snakview;

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing Wikibase Snaks.
 * @since 0.3
 *
 * @option {Object|wb.datamodel.Snak|null} value The snak this view should represent initially. If omitted,
 *         an empty view will be served, ready to take some input by the user. The value can also be
 *         overwritten later, by using the value() or snak() functions.
 *         Default: { property: null, snaktype: wb.datamodel.PropertyValueSnak.TYPE }
 *
 * @option {Object|boolean} locked Key-value pairs determining which snakview elements to lock for
 *         being edited by the user. May also be a boolean value enabling/disabling all elements.
 *         Default: false (no elements to be locked)
 *
 * @option {boolean} autoStartEditing Whether or not view should go into edit mode by its own upon
 *         initialization if its initial value is empty.
 *         Default: true
 *
 * @option {wb.store.EntityStore} entityStore
 *
 * @option {wikibase.ValueViewBuilder} valueViewBuilder
 *
 * @event startediting: Called before edit mode gets initialized.
 *        (1) {jQuery.Event} event
 *
 * @event stopediting: Called before edit mode gets closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} dropValue Will be false if the value will be kept.
 *        (3) {wb.datamodel.Snak|null} newSnak The Snak which will be displayed after editing has stopped.
 *            Normally this is the Snak representing the last state of the view during edit mode
 *            but can also be the Snak from before edit mode in case editing has been cancelled.
 *
 * @event afterstopediting: Called after edit mode got closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} droppedValue false if the value edited during edit mode has been preserved.
 *
 * @event change: Triggered whenever the snakview's status ("valid"/"invalid") is changed in any
 *        way.
 *        (1) {jQuery.Event} event
 */
$.widget( 'wikibase.snakview', PARENT, {
	widgetName: 'wikibase-snakview',

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-snak',
		templateParams: [ '', '', '' ],
		templateShortCuts: {
			'$property': '.wb-snak-property',
			'$snakValue': '.wb-snak-value',
			'$snakTypeSelector': '.wb-snak-typeselector'
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
		valueViewBuilder: null
	},

	/**
	 * The DOM node of the entity selector for choosing a property or the node with plain text of
	 * the properties label. This is a selector widget only the first time in edit mode.
	 * @type jQuery
	 */
	$property: null,

	/**
	 * The DOM node of the Snak's value, some message if the value is not supported or "no value"/
	 * "some value" message.
	 * @type jQuery
	 */
	$snakValue: null,

	/**
	 * The DOM node of the Snak type selector.
	 * @type jQuery
	 */
	$snakTypeSelector: null,

	/**
	 * Variation object responsible for presenting the essential parts of a certain kind of Snak.
	 * Can be null if a unsupported Snak Type is represented by the snakview. In this case the
	 * snakview won't be able to display the Snak but display an appropriate message instead.
	 * @type $.wikibase.snakview.variations.Variation|null
	 */
	_variation: null,

	/**
	 * Cache for the values of specific wikibase.snakview.variations in order to have those restored
	 * when toggling he snak type.
	 * @type {Object}
	 */
	_cachedValues: null,

	/**
	 * The property of the Snak currently represented by the view.
	 * @type {String}
	 */
	_propertyId: null,

	/**
	 * The Snak type of the Snak currently represented by the view.
	 * @type {String}
	 */
	_snakType: null,

	/**
	 * The Snak from before edit mode has been entered.
	 * @type wb.datamodel.Snak|null
	 */
	_initialSnak: null,

	/**
	 * @type Boolean
	 */
	_isInEditMode: false,

	/**
	 * Caching whether to move the focus from the property input to the value input after pressing
	 * the TAB key.
	 * @type Boolean
	 */
	_tabToValueView: false,

	/**
	 * @type boolean
	 */
	_isValid: false,

	/**
	 * @type {wb.store.EntityStore}
	 */
	_entityStore: null,

	/**
	 * @type {wikibase.ValueViewBuilder}
	 */
	_valueViewBuilder: null,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._entityStore = this.option( 'entityStore' );
		this._valueViewBuilder = this.option( 'valueViewBuilder' );

		this._cachedValues = {};

		this.value( this.option( 'value' ) || {} );

		if( this.option( 'autoStartEditing' ) && !this.snak() ) {
			// If no Snak is represented, offer UI to build one.
			// This clearly implies draw() since it requires visual changes!
			this.startEditing();
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
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
	 * @return {boolean}
	 */
	isDisabled: function() {
		// Function is required by snakview.ViewState.
		return this.option( 'disabled' );
	},

	/**
	 * Returns an input element with initialized entity selector for selecting entities.
	 * @since 0.3
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

			self._entityStore.get( entityId ).done( function( entity ) {
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
	 * @see $.widget.destroy
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
	 * Starts the edit mode where the snak can be edited.
	 * @since 0.3
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	startEditing: $.NativeEventHandler( 'startEditing', {
		// don't start edit mode or trigger event if in edit mode already:
		initially: function( e ) {
			if( this.isInEditMode() ) {
				e.cancel();
			}
		},
		// start edit mode if event doesn't prevent default:
		natively: function( e ) {
			var self = this;

			this._initialSnak = this.snak();
			this._isInEditMode = true;
			this.draw();

			// attach keyboard input events
			this.element.on( 'keydown.' + this.widgetName, function( event ) {
				if ( self.options.disabled ) {
					return;
				}

				var propertySelector = self._getPropertySelector();

				self._leavePropertyInput = false;

				// TODO: (Bug 54021) Widget should not switch between edit modes on its own!
				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					self.cancelEditing();
				} else if ( event.keyCode === $.ui.keyCode.ENTER && self.isValid() ) {
					if ( ( !propertySelector || event.target !== propertySelector.element[0] ) ) {
						self.stopEditing();
						event.preventDefault();
					}
				} else if ( event.keyCode === $.ui.keyCode.TAB && !self._variation ) {
					// When pressing TAB in the property input element while the value input element
					// does not yet exist, we assume that the user wants to auto-complete/select the
					// currently suggested property and tab into the value element. Since the API
					// needs to be queried to construct the correct value input, the intended action
					// needs to be cached and triggered as soon as the value input has been created.
					if ( propertySelector && event.target === propertySelector.element[0] ) {
						if( self._getPropertySelector().selectedEntity() ) {
							self._tabToValueView = true;
							event.preventDefault();
						}
					}
				}
				// no point in propagating event after having destroyed the event's target
				if ( !self.isInEditMode() ) {
					event.stopImmediatePropagation();
				}
			} );

			if ( this._getPropertySelector() !== null ) {
				this._getPropertySelector().element.focus();
			} else if( this._variation ) {
				$( this._variation ).one( 'afterdraw', function() {
					this.focus();
				} );
			}
		}
	} ),

	/**
	 * Ends the edit mode where the snak can be edited.
	 * @since 0.3
	 *
	 * @param {Boolean} [dropValue] If true, the value from before edit mode has been started will
	 *        be reinstated. false by default. Consider using cancelEditing() instead.
	 * @return {undefined} (allows chaining widget calls)
	 */
	stopEditing: $.NativeEventHandler( 'stopEditing', {
		// don't stop edit mode or trigger event if not in edit mode currently:
		initially: function( e, dropValue ) {
			if( !this.isInEditMode() ) {
				e.cancel();
			}
			var snak;

			if( dropValue ) {
				// cancel edit, or no variation, e.g. because no valid data type is chosen
				snak = this._initialSnak;
			} else if( !this._variation ) {
				snak = null;
			} else {
				snak = this.snak();
			}

			e.handlerArgs = [ !!dropValue, snak ];
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue, newSnak ) {
			this._isInEditMode = false;
			this._initialSnak = null;

			// update view; will remove edit interfaces and represent value statically
			this._setValue( newSnak !== null ? this._serializeSnak( newSnak ) : {} ); // triggers this.draw()
			// TODO: should throw an error somewhere when trying to leave edit mode while
			//  this.snak() still returns null. For now setting {} is a simple solution for non-
			//  existent error handling in the snak UI

			this.element.off( 'keydown.' + this.widgetName );

			this._trigger( 'afterStopEditing', null, [ dropValue, newSnak ] );
		}
	} ),

	/**
	 * short-cut for stopEditing( false ). Closes the edit view and restores the value from before
	 * the edit mode has been started.
	 * @since 0.3
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	cancelEditing: function() {
		return this.stopEditing( true ); // stop editing and drop value
	},

	/**
	 * Updates this snakview's status.
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
	 * Returns whether the Snak is valid in its current state.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isValid: function() {
		return this._isValid;
	},

	/**
	 * Returns whether the current snak matches the one the snakview has been initialized with.
	 *
	 * TODO/FIXME: think about logic behind true being returned if initial and current Snaks are
	 *             null. Perhaps a 'hasChanged' or 'isInitialValue' function would be conceptually
	 *             less confusing, though, different in their result.
	 *
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isInitialSnak: function() {
		var snak = this.snak(),
			initialSnak = this.initialSnak();

		if( !initialSnak && !snak ) {
			// no snaks at all, but we consider this situation as having same Snaks anyhow.
			return true;
		}
		return snak && snak.equals( initialSnak );
	},

	/**
	 * Returns whether the Snak is editable at the moment.
	 * @since 0.3
	 *
	 * @return Boolean
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Returns the property selector for choosing the Snak's property. Returns null if the Snak is
	 * created already and has a Property (once created, the Property is immutable).
	 * @since 0.3
	 *
	 * @return $.wikibase.entityselector|null
	 */
	_getPropertySelector: function() {
		if( this.$property ) {
			return this.$property.children().first().data( 'entityselector' ) || null;
		}
		return null;
	},

	/**
	 * Returns the Snak type selector for choosing the Snak's type. Returns null if the Snak is
	 * created already and has a Property (once created, the Property is immutable).
	 * @since 0.3
	 *
	 * @return $.wikibase.snakview.SnakTypeSelector|null
	 */
	_getSnakTypeSelector: function() {
		if( this.$snakTypeSelector ) {
			return this.$snakTypeSelector.children().first().data( 'snaktypeselector' ) || null;
		}
		return null;
	},

	/**
	 * Returns the initial value from before edit mode has been entered. If not in edit mode, this
	 * will return the same as value().
	 *
	 * @return {Object}
	 */
	initialValue: function() {
		return this.isInEditMode() ? this._serializeSnak( this.initialSnak() ) : this._getValue();
	},

	/**
	 * Just like initialValue() but returns a Snak object or null if there was no Snak set before
	 * edit mode.
	 *
	 * @return {wb.datamodel.Snak|null}
	 */
	initialSnak: function() {
		return this.isInEditMode() ? this._initialSnak : this.snak();
	},

	/**
	 * Returns an object representing the currently displayed Snak. This is equivalent to the JSON
	 * structure of a Snak, except that it does not have to be complete. For example for a
	 * PropertyValueSnak where only the property and snak types are chosen but the value has not
	 * been entered yet, the returned Object would not have a field for the value either.
	 *
	 * @since 0.3
	 *
	 * @param {Object|wb.datamodel.Snak|null} [value]
	 * @return {wb.datamodel.Snak|null|undefined} undefined in case value() is called to set the value
	 */
	value: function( value ) {
		if( value === undefined ) {
			return this._getValue();
		}
		if( value !== null && typeof value !== 'object' ) {
			throw new Error( 'The given value has to be a plain object, an instance of' +
				' wikibase.datamodel.Snak, or null' );
		}
		this._setValue( ( value instanceof wb.datamodel.Snak ) ? this._serializeSnak( value ) : value );
	},

	/**
	 * @param {wb.datamodel.Snak} value
	 * @return {Object}
	 */
	_serializeSnak: function( snak ) {
		var snakSerializer = new wikibase.serialization.SnakSerializer();
		return snakSerializer.serialize( snak );
	},

	/**
	 * Private getter for this.value()
	 * @since 0.3
	 *
	 * @return Object
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
	 * Will update the view to represent a given Snak in form of a plain Object. The given object
	 * can have all fields - or a subset of fields - a serialized wb.datamodel.Snak would have.
	 *
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
	 *
	 * @param {Object} changes
	 */
	_updateValue: function( changes ) {
		this._setValue( $.extend( this._getValue(), changes ) );
	},

	/**
	 * Returns the current Snak represented by the view or null in case the view is in edit mode,
	 * also allows to set the view to represent a given Snak.
	 *
	 * @since 0.4
	 *
	 * @param {wikibase.datamodel.Snak|null} [snak]
	 * @return {wikibase.datamodel.Snak|null}
	 */
	snak: function( snak ) {
		if( snak === undefined ) {
			// factory method will fail when essential data is not yet defined!
			// TODO: variations should have a function to ask whether fully defined yet
			try{
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
	 * Returns the property ID of the property chosen for this Snak or null if none is set.
	 * Equal to .value().getPropertyId() but might be set while .value() still returns null, e.g.
	 * if property has been selected or pre-defined while value or Snak type are not yet set.
	 *
	 * @since 0.3 (setter since 0.4)
	 *
	 * @return String|null
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
	 * Returns the Snak type ID in use for the Snak represented by the view or null if not defined.
	 * Equal to .value().getType() but might be set while .value() still returns null, e.g. if
	 * Snak type has been selected or pre-defined while other required information for constructing
	 * the Snak object has not been defined yet.
	 * If the first parameter is set, it will replace the current Snak type.
	 *
	 * @since 0.4
	 *
	 * @param {String|null} [snakType]
	 * @return String|null
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

			if( this._cachedValues[snakType] ) {
				$.extend( changes, this._cachedValues[snakType] );
			}

			this._updateValue( changes );
		}
	},

	/**
	 * Returns the jQuery.snakview variation object required for presenting the current Snak type.
	 * If a Snak type has not been defined yet, this will return null.
	 * @since 0.4
	 *
	 * @return {$.wikibase.snakview.variations.Variation|null}
	 */
	variation: function() {
		return this._variation;
	},

	/**
	 * Checks whether the Snak type has been changed by the user and will build a new variation
	 * object for that type if necessary.
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
				this._entityStore,
				this._valueViewBuilder
			);
		}
	},

	/**
	 * Will render the view's current state (does consider edit mode, current value, etc.).
	 * @since 0.4
	 */
	draw: function() {
		var self = this;

		// NOTE: Order of these shouldn't matter; If for any reasons draw functions start changing
		//  the outcome of the variation (or Snak type), then something must be incredibly wrong!
		if( this._propertyId ) {
			this._entityStore.get( this._propertyId ).done( function( fetchedProperty ) {
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
	 * Will make sure the current Snak's property is displayed properly. If not Snak is set, then
	 * this will serve the input form for the Snak's property.
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Property|null} property
	 * @param {mediawiki.Title|null} title Only null if property is null
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
				propertySelector.widget().val( propertyLabel );
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
	 * Will update the selector for choosing the Snak type to represent the currently chosen type.
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
	 * Convenience function for this.variation().draw( ... ), does not require additional check
	 * whether the variation is null.
	 *
	 * @since 0.4
	 */
	drawVariation: function() {
		// property ID will be null if not in edit mode and no Snak set or if in edit mode and user
		// didn't choose property yet.
		var propertyId = this._propertyId;

		if( propertyId && this._variation ) {
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
	 * Returns the DOM of the Snak type selector for choosing what kind of Snak this is.
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

		var changeEvent = ( selector.widgetEventPrefix + 'afterchange' ).toLowerCase();

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
	 * Hides the property label.
	 * @since 0.5
	 */
	hidePropertyLabel: function() {
		this.$property.hide();
	},

	/**
	 * Shows the property label.
	 * @since 0.5
	 */
	showPropertyLabel: function() {
		this.$property.show();
	},

	/**
	 * Returns whether the property label is currently visible.
	 * @since 0.5
	 */
	propertyLabelIsVisible: function() {
		return this.$property.is( ':visible' );
	}
} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.wikibase.snakview.prototype.widgetBaseClass = 'wb-snakview';

$.extend( $.wikibase.snakview, existingSnakview );

}( mediaWiki, wikibase, jQuery ) );
