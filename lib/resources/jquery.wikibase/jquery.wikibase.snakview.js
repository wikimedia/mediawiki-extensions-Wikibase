/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, dt, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing Wikibase Snaks.
 * @since 0.3
 *
 * @option value {wb.Snak|null} The snak this view should represent. If set to null (by default),
 *         an empty view will be served, ready to take some input by the user. The value can be
 *         overwritten later, by using the value() function.
 *
 * @option predefined {Object} Allows to pre-define certain aspects of the Snak to be created.
 *         Can be used to only allow creation of Snaks using a certain pre-defined Property.
 *         This option will be overruled by the value option in case of a contradiction.
 *         The following fields can be set:
 *         - predefined.property {String} a property ID, will prevent users from choosing a property.
 *         - predefined.snakType {String} a Snak type, will set Snak type selector to the given.
 *
 * @event startediting: Called before edit mode gets initialized.
 *        (1) {jQuery.Event} event
 *
 * @event stopediting: Called before edit mode gets closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} dropValue Will be false if the value will be kept.
 *
 * @event afterstopediting: Called after edit mode got closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} droppedValue false if the value edited during edit mode has been preserved.
 */
$.widget( 'wikibase.snakview', PARENT, {
	widgetName: 'wikibase-snakview',
	widgetBaseClass: 'wb-snakview',
	widgetTemplate: 'wb-snak',
	widgetTemplateShortCuts: {
		'$property': '.wb-snak-property',
		'$snakValue': '.wb-snak-value',
		'$snakTypeSelector': '.wb-snak-typeselector'
	},

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		value: null,
		predefined: {
			property: false,
			snakType: wb.PropertyValueSnak.TYPE
		}
	},

	/**
	 * The DOM node of the entity selector for choosing a property or the node with plain text of
	 * the properties label. This is a selector widget only the first time in edit mode.
	 * @type jQuery
	 */
	$property: null,

	/**
	 * The DOM node of the Snak's value or some message if the value is not supported.
	 * TODO later we will support 'novalue' and 'somevalue' snaks which will probably be displayed
	 *      in this node as well somehow.
	 * @type jQuery
	 */
	$snakValue: null,

	/**
	 * The DOM node of the type selector.
	 * @type jQuery
	 */
	$snakTypeSelector: null,

	/**
	 * Variation object responsible for presenting the essential parts of a certain kind of Snak.
	 * @type jQuery.wikibase.snakview.variations.Variation
	 */
	_variation: null,

	/**
	 * The Snak represented by this view. This is null if no valid Snak is constructed yet.
	 * @type wb.Snak|null
	 */
	_snak: null,

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
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._snak = this.option( 'value' );
		this._updateVariation();

		if( this._snak === null ) {
			// If no Snak is represented, offer UI to build one.
			// This clearly implies draw() since it requires visual changes!
			this.startEditing();
		} else {
			this.draw();
		}
	},

	/**
	 * Returns an input element with initialized entity selector for selecting entities.
	 * @since 0.3
	 *
	 * @return {jQuery}
	 */
	_buildPropertySelector: function() {
		var self = this,
			language = mw.config.get( 'wgUserLanguage' );

		return $( '<input/>' ).entityselector( {
			url: mw.util.wikiScript( 'api' ),
			language: language,
			type: 'property'
		} )
		.prop( 'placeholder', mw.msg( 'wikibase-snakview-property-input-placeholder' ) )
		.on( 'blur', function( event ) {
			self._tabToValueView = false;
		} )
		.eachchange( function( oldValue ) {
			// remove out-dated variations
			if( self._variation ) {
				self._variation.destroy();
				self.$snakValue.empty();
				self._variation = null;
			}
		} )
		.on( 'entityselectorselect', function( e, ui ) {
			// entity chosen in entity selector but we still need the data type of the entity, so
			// we have to make a separate API call:
			var api = new wb.Api();

			// display spinner as long as the value view is loading
			self.$snakValue.empty().append(
				$( '<div/>' ).append( $( '<span/>' ).addClass( 'mw-small-spinner' ) )
			);

			api.getEntities( ui.item.id, null ).done( function( response ) {
				var entity = response.entities[ ui.item.id ],
					dataTypeId = entity.datatype, // TODO: include datatype into search API result
					dataType = dt.getDataType( dataTypeId ),
					label;

				if( entity.labels && entity.labels[ language ] ) {
					label = entity.labels[ language ].value;
				}

				// update local store with newest information about selected property
				// TODO: create more sophisticated local store interface rather than accessing
				//       wb.entities directly
				wb.entities[ ui.item.id ] = {
					label: label,
					datatype: dataType.getId(),
					url: ui.item.url
				};

				self._updateVariation();

				if( self._variation && dataType ) {
					// display view for other Snak essentials (variation) which might depend on this:
					self.drawVariation();

					// Since it takes a while for the value view to gather its data from the API,
					// the property might not be valid anymore aborting the rendering of the value
					// view.
					if( self._tabToValueView ) {
						self._variation.focus();
					}
				} else {
					// TODO: display a message that the property has a data type unknown to the UI
				}
			} );
		} );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass );
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

			this._isInEditMode = true;
			this.draw();

			// attach keyboard input events
			this.element.on( 'keydown', function( event ) {
				var propertySelector = self._getPropertySelector();

				self._leavePropertyInput = false;

				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					self.cancelEditing();
				} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
					if ( !propertySelector || event.target !== propertySelector.element[0] ) {
						self.stopEditing();
					}
				} else if ( event.keyCode === $.ui.keyCode.TAB && !self._variation ) {
					// When pressing TAB in the property input element while the value input element
					// does not yet exist, we assume that the user wants to auto-complete/select the
					// currently suggested property and tab into the value element. Since the API
					// needs to be queried to construct the correct value input, the intended action
					// needs to be cached and triggered as soon as the value input has been created.
					if ( propertySelector && event.target === propertySelector.element[0] ) {
						if ( self._getPropertySelector().validateInput() ) {
							// Have the current property input validated instead of just blurring
							// since the value input element will not appear if the property is not
							// valid and setting the focus would remain unresolved.
							// We do not blur automatically. If the user decides to manually focus
							// another element while the value input element is loading, cancel the
							// cached tab event.
							self._getPropertySelector().widget().data( 'menu' ).select(
								$.Event( 'programmatic' )
							);
							self._tabToValueView = true;
							event.preventDefault();
						} else if ( self._getPropertySelector().selectedEntity() !== null ) {
							// A property has already been selected (e.g. selecting a suggestion
							// with the keyboard's arrow keys and then pressing TAB).
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
				this._variation.focus();
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
			e.handlerArgs = [ !!dropValue ]; // just make sure this is a Boolean for event handlers
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue ) {
			this._isInEditMode = false;

			if( dropValue || !this._variation ) {
				// cancel edit, or no variation, e.g. because no valid data type is chosen
				this.draw();
			} else {
				// update view; will remove edit interfaces and represent value statically
				var snak = this._variation.newSnak( this._getPropertyId() );
				this._setValue( snak ); // triggers this.draw()
			}

			this._trigger( 'afterStopEditing', null, [ dropValue ] );
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
	 * created already and has a Property (once created, the Property is immutable). Also returns
	 * null if predefined.property option is set.
	 * @since 0.3
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
	 * Returns the current Snak represented by the view or null in case the view is in edit mode,
	 * also allows to set the view to represent a given Snak.
	 *
	 * @since 0.3
	 *
	 * @return {wb.Snak|null}
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
	 * Private getter for this.value()
	 * @since 0.3
	 *
	 * @return wb.Snak|null
	 */
	_getValue: function() {
		if( this._snak ) {
			return this._snak;
		}
		// TODO: think about moving the following code. this._snak !== this.value() in rare cases!
		var propertyId = this._getPropertyId();

		// snak is not set explicitly, perhaps it is set indirectly from all required 'predefine'
		// options being set initially
		if( !this._variation || !propertyId ) {
			return null;
		}
		this._snak = this._variation.newSnak( propertyId );
		// NOTE: can still be null if user didn't enter essential information in variation's UI
		return this._snak;
	},

	/**
	 * Will update the view to represent a given Snak or nothing but an empty form instead.
	 * @since 0.3
	 *
	 * @param {wb.Snak|null} snak
	 */
	_setValue: function( snak ) {
		this._snak = snak;
		this._updateVariation();
		this.draw();
	},

	/**
	 * Returns the property ID of the property chosen for this Snak or null if none is set.
	 * Equal to .value().getPropertyId() but might be set while .value() still returns null, e.g.
	 * if property has been selected or pre-defined while value or Snak type are not yet set.
	 * @since 0.3
	 *
	 * TODO implement setter functionality for this
	 *
	 * @return String|null
	 */
	propertyId: function() {
		return this._getPropertyId();
	},

	/**
	 * Private getter for this.propertyId()
	 * @since 0.3
	 *
	 * @return String|null
	 */
	_getPropertyId: function() {
		// return user-chosen property ID
		var propertySelector = this._getPropertySelector();
		if( propertySelector ) {
			var selectedEntity = propertySelector.selectedEntity();
			return selectedEntity ? selectedEntity.id : null;
		}

		// no selector, perhaps Snak is defined already
		if( this._snak ) {
			return this._snak.getPropertyId();
		}

		// if set, return pre-defined property ID
		var predefinedPropertyId = this.option( 'predefined' ).property;
		if( predefinedPropertyId ) {
			return predefinedPropertyId;
		}

		return null;
	},

	/**
	 * Returns the Snak type ID in use for the Snak represented by the view or null if not defined.
	 * Equal to .value().getType() but might be set while .value() still returns null, e.g. if
	 * Snak type has been selected or pre-defined while other required information for constructing
	 * the Snak object has not been defined yet.
	 *
	 * @since 0.4
	 *
	 * TODO implement setter functionality for this
	 *
	 * @return String|null
	 */
	snakType: function() {
		return this._getSnakType();
	},

	/**
	 * Private getter for this.snakType()
	 * @since 0.4
	 *
	 * @return String|null
	 */
	_getSnakType: function() {
		if( this._snak ) {
			return this._snak.getType();
		}
		// if set, return pre-defined type
		var predefinedSnakType = this.option( 'predefined' ).snakType;
		if( predefinedSnakType ) {
			return predefinedSnakType;
		}
		return null;
	},

	/**
	 * Returns the jQuery.snakview variation object required for presenting the current Snak type.
	 * If a Snak type has not been defined yet, this will return null.
	 * @since 0.4
	 *
	 * @return {jQuery.wikibase.snakview.variations.Variation|null}
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
			snakType = this._getSnakType(),
			VariationConstructor = variationsFactory.getVariation( snakType );

		if( !VariationConstructor || !this._getPropertyId() ) {
			this._variation = null;
		}
		else if( !this._variation || this._variation.constructor !== VariationConstructor ) {
			// Snak type has changed so we need another variation Object!
			this._variation = new VariationConstructor( this.$snakValue );
		}
	},

	/**
	 * Will render the view's current state (does consider edit mode, current value, etc.).
	 * @since 0.4
	 */
	draw: function() {
		// NOTE: Order of these shouldn't matter; If for any reasons draw functions start changing
		//  the outcome of the variation (or Snak type), then something must be incredibly wrong!
		this.drawProperty();
		this.drawSnakTypeSelector();
		this.drawVariation();
	},

	/**
	 * Will make sure the current Snak's property is displayed properly. If not Snak is set, then
	 * this will serve the input form for the Snak's property (except if the property is set per the
	 * 'predefined' option).
	 * @since 0.4
	 */
	drawProperty: function() {
		var $propertyDom,
			propertyId = this._getPropertyId(),
			property = propertyId ? wb.entities[ propertyId ] : null,
			propertyLabel = '';

		if( property ) {
			propertyLabel = property.label || propertyId;
		}

		if( property || !this.isInEditMode() ) {
			// property set and can't be changed afterwards, only display label
			$propertyDom = $( document.createTextNode( propertyLabel ) );
			// TODO: display nice label with link here, just like claimlistview
		} else {
			// no property set for this Snak, serve edit view to specify it:
			var propertySelector = this._getPropertySelector();

			// TODO: use selectedEntity() or other command to set selected entity in both cases!
			//       When asking _getValue(), _getPropertyId() will return null because it asks the
			//       widget which doesn't know that the val() set here actually is an entity.
			if( propertySelector ) {
				// property selector in DOM already, just replace current value
				propertySelector.widget().val( propertyLabel );
				return;
			}
			// property selector in DOM already, just remove current value
			$propertyDom = this._buildPropertySelector().val( propertyLabel );
		}

		this.$property.empty().append( $propertyDom );
	},

	/**
	 * Will update the selector for choosing the Snak type.
	 * @since 0.4
	 */
	drawSnakTypeSelector: function() {
		var snakTypes = $.wikibase.snakview.variations.getCoveredSnakTypes();

		if( !this.isInEditMode() || snakTypes.length <= 1 ) {
			this.$snakTypeSelector.empty();
			return; // no type selector required in non-edit mode!
		}
		var $snakTypes = $( '<ul/>' );

		$.each( $.wikibase.snakview.variations.getCoveredSnakTypes(), function() {
			$snakTypes.append( $( '<li/>', {
				text: this
			} ) );
		} );

		this.$snakTypeSelector.empty().append( $snakTypes );
	},

	/**
	 * Convenience function for this.variation().draw( ... ), does not require additional check
	 * whether the variation is null.
	 *
	 * @since 0.4
	 */
	drawVariation: function() {
		if( this._variation ) {
			this.variation().draw( this.isInEditMode(), this._getPropertyId(), this._getValue() );
		} else if( this._getPropertyId() ) {
			// property ID selected but apparently no variation available to handle it
			this.$snakValue.empty().append( $( '<span/>', {
				text: mw.msg( 'wikibase-snakview-unsupportedsnaktype', this._getSnakType() ),
				'class': this.widgetBaseClass + '-unsupportedsnaktype'
			} ) );
			// NOTE: instead of doing this here and checking everywhere whether this._variation is
			//  set, we could as well use variations for displaying system messages like this, e.g.
			//  having a UnsupportedSnakType variation which is not registered for a specific snak
			//  type but is known to _updateVariation().
		}
	}
} );

}( mediaWiki, wikibase, dataTypes, jQuery ) );
