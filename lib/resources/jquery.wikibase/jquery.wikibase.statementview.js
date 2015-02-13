( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

/**
 * View for displaying and editing `wikibase.datamodel.Statement` objects.
 * @see wikibase.datamodel.Statement
 * @class jQuery.wikibase.statementview
 * @extends jQuery.ui.EditableTemplatedWidget
 * @uses jQuery.ui.toggler
 * @uses jQuery.wikibase.listview
 * @uses jQuery.wikibase.listview.ListItemAdapter
 * @uses jQuery.wikibase.referenceview
 * @uses jQuery.wikibase.snakview
 * @uses jQuery.wikibase.snaklistview
 * @uses jQuery.wikibase.statementview.RankSelector
 * @uses mediaWiki
 * @uses wikibase.datamodel.Claim
 * @uses wikibase.datamodel.SnakList
 * @uses wikibase.datamodel.ReferenceList
 * @uses wikibase.datamodel.Statement
 * @uses wikibase.utilities.ui
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.Statement|null} [options.value=null]
 *        The `Statement` displayed by the view. May be set initially only and gets updated
 *        automatically if changes to the `Statement` are saved.
 *        If `null`, the view will be switched to edit mode initially.
 * @param {wikibase.utilities.ClaimGuidGenerator} options.guidGenerator
 *        Required for dynamically generating GUIDs for new `Statement`s.
 * @param {wikibase.entityChangers.ClaimsChanger} options.claimsChanger
 *        Required to store the view's `Statement`.
 * @param {wikibase.store.EntityStore} options.entityStore
 *        Required for dynamically gathering `Entity`/`Property` information.
 * @param {wikibase.ValueViewBuilder} options.valueViewBuilder
 *        Required by the `snakview` interfacing a `snakview` "value" `Variation` to
 *        `jQuery.valueview`.
 * @param {wikibase.entityChangers.EntityChangersFactory} options.entityChangersFactory
 *        Required to store the `Reference`s gathered from the `referenceview`s aggregated by the
 *        `statementview`.
 * @param {dataTypes.DataTypeStore} options.dataTypeStore
 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
 *        object when interacting on a "value" `Variation`.
 * @param {Object} [options.predefined={ mainSnak: false }]
 *        Allows to predefine certain aspects of the `Statement` to be created from the view. If
 *        this option is omitted, an empty view is created. A common use-case is adding a value to a
 *        property existing already by specifying, for example: `{ mainSnak.property: 'P1' }`.
 * @param {Object} [options.locked={ mainSnak: false }]
 *        Elements that shall be locked and may not be changed by user interaction.
 * @param {string} [options.helpMessage=mw.msg( 'wikibase-claimview-snak-new-tooltip' )]
 *        End-user message explaining how to use the `statementview` widget. The message is most
 *        likely to be used inside the tooltip of the toolbar corresponding to the `statementview`.
 */
/**
 * @event afterremove
 * Triggered after a `referenceview` has been remove from the `statementview`'s list of
 * `referenceview`s.
 * @param {jQuery.Event} event
 */
/**
 * @event change
 * Triggered whenever the view's content is changed.
 * @param {jQuery.Event} event
 */
$.widget( 'wikibase.statementview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	options: {
		template: 'wikibase-statementview',
		templateParams: [
			function() { // GUID
				return ( this.options.value && this.options.value.getClaim().getGuid() ) || 'new';
			},
			function() { // Rank selector
				return $( '<div/>' );
			},
			function() { // Main snak
				return $( '<div/>' );
			},
			'', // Qualifiers
			'', // Toolbar placeholder
			'', // References heading
			'' // List of references
		],
		templateShortCuts: {
			$rankSelector: '.wikibase-statementview-rankselector',
			$mainSnak: '.wikibase-statementview-mainsnak > :first-child',
			$qualifiers: '.wikibase-statementview-qualifiers',
			$refsHeading: '.wikibase-statementview-references-heading',
			$references: '.wikibase-statementview-references'
		},
		value: null,
		claimsChanger: null,
		dataTypeStore: null,
		entityChangersFactory: null,
		predefined: {
			mainSnak: false
		},
		locked: {
			mainSnak: false
		},
		helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' )
	},

	/**
	 * @property {jQuery.wikibase.statementview.RankSelector}
	 * @private
	 */
	_rankSelector: null,

	/**
	 * Shortcut to the `ListItemAdapter` in use in the `listview` managing the `referenceview`s.
	 * @property {jQuery.wikibase.listview.ListItemAdapter}
	 * @private
	 */
	_referenceviewLia: null,

	/**
	 * Shortcut to the `listview` managing the `referenceview`s.
	 * @property {jQuery.wikibase.listview}
	 * @private
	 */
	_referencesListview: null,

	/**
	 * Reference to the `listview` widget managing the qualifier `snaklistview`s.
	 * @property {jQuery.wikibase.listview}
	 * @private
	 */
	_qualifiers: null,

	/**
	 * @property {wikibase.entityChangers.ReferencesChanger}
	 * @private
	 */
	_referencesChanger: null,

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if(
			!this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.claimsChanger
			|| !this.options.entityChangersFactory
			|| !this.options.dataTypeStore
			|| !this.options.guidGenerator
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

		this._referencesChanger = this.options.entityChangersFactory.getReferencesChanger();

		this._updateHelpMessage();

		this.draw();
	},

	/**
	 * @since 0.5
	 * @private
	 *
	 * @param {number} rank
	 */
	_createRankSelector: function( rank ) {
		if( this._rankSelector ) {
			return;
		}

		var $rankSelector = this.$rankSelector.children().first();
		this._rankSelector = new $.wikibase.statementview.RankSelector( {
			value: rank,
			templateParams: ['ui-state-disabled', '', ''],
			// TODO: Directionality should be determined on entityview level and forwarded to here
			isRTL: $( 'html' ).prop( 'dir' ) === 'rtl'
		}, $rankSelector );

		var self = this,
			changeEvent = ( this._rankSelector.widgetEventPrefix + 'afterchange' ).toLowerCase();

		this.$rankSelector.on( changeEvent + '.' + this.widgetName, function( event ) {
			if( self.value() ) {
				self._trigger( 'change' );
			}
		} );
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.Snak|null} [snak=null]
	 */
	_createMainSnak: function( snak ) {
		if( this.$mainSnak.data( 'snakview' ) ) {
			return;
		}

		var self = this;

		this.$mainSnak
		.on( 'snakviewchange.' + this.widgetName, function( event, status ) {
			event.stopPropagation();
			self._trigger( 'change' );
		} )
		.on( 'snakviewstopediting.' + this.widgetName, function( event ) {
			event.stopPropagation();
		} );

		this.$mainSnak.snakview( {
			value: snak || undefined,
			locked: this.options.locked.mainSnak,
			autoStartEditing: false,
			dataTypeStore: this.options.dataTypeStore,
			entityStore: this.options.entityStore,
			valueViewBuilder: this.options.valueViewBuilder
		} );
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.SnakList|null} [qualifiers=null]
	 */
	_createQualifiersListview: function( qualifiers ) {
		if( this._qualifiers ) {
			return;
		}

		var self = this,
			groupedQualifierSnaks = null;

		// Group qualifiers by property id:
		if( qualifiers && qualifiers.length ) {
			var propertyIds = qualifiers.getPropertyOrder();

			groupedQualifierSnaks = [];

			for( var i = 0; i < propertyIds.length; i++ ) {
				groupedQualifierSnaks.push( qualifiers.getFilteredSnakList( propertyIds[i] ) );
			}
		}

		// Using the property id, qualifier snaks are split into groups of snaklistviews. These
		// snaklistviews are managed in a listview:
		var $qualifiers = this.$qualifiers.children();
		if( !$qualifiers.length ) {
			$qualifiers = $( '<div/>' ).prependTo( this.$qualifiers );
		}
		$qualifiers.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.snaklistview,
				newItemOptionsFn: function( value ) {
					return {
						value: value || undefined,
						singleProperty: true,
						dataTypeStore: self.options.dataTypeStore,
						entityStore: self.options.entityStore,
						valueViewBuilder: self.options.valueViewBuilder
					};
				}
			} ),
			value: groupedQualifierSnaks
		} )
		.on( 'snaklistviewstopediting.' + this.widgetName, function( event, dropValue ) {
			event.stopPropagation();
		} )
		.on( 'snaklistviewchange.' + this.widgetName
			+ ' listviewafteritemmove.' + this.widgetName,
			function( event ) {
				event.stopPropagation();
				self._trigger( 'change' );
			}
		)
		.on( 'listviewitemremoved.' + this.widgetName, function( event, value, $itemNode ) {
			if( event.target === self._qualifiers.element.get( 0 ) ) {
				self._trigger( 'change' );
				return;
			}

			// Check if last snaklistview of a qualifier listview item has been removed and
			// remove the listview item if so:
			var $snaklistview = $( event.target ).closest( ':wikibase-snaklistview' ),
				snaklistview = $snaklistview.data( 'snaklistview' );

			if( !snaklistview.value().length ) {
				self._qualifiers.removeItem( snaklistview.element );
			}
		} );

		this._qualifiers = $qualifiers.data( 'listview' );
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.Statement} [statement]
	 */
	_createReferences: function( statement ) {
		if( !statement ) {
			return;
		}

		var self = this,
			references = statement.getReferences();

		var $listview = this.$references.children();
		if( !$listview.length ) {
			$listview = $( '<div/>' ).prependTo( this.$references );
		} else if( $listview.data( 'listview' ) ) {
			return;
		}

		$listview.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.referenceview,
				newItemOptionsFn: function( value ) {
					return {
						value: value || null,
						statementGuid: self.options.value.getClaim().getGuid(),
						dataTypeStore: self.options.dataTypeStore,
						entityStore: self.options.entityStore,
						valueViewBuilder: self.options.valueViewBuilder,
						referencesChanger: self._referencesChanger
					};
				}
			} ),
			value: references.toArray()
		} );

		this._referencesListview = $listview.data( 'listview' );

		this._referenceviewLia = this._referencesListview.listItemAdapter();

		$listview
		.on( 'listviewitemadded listviewitemremoved', function( event, value, $li ) {
			if( event.target === $listview[0] ) {
				self._drawReferencesCounter();
			}
		} )
		.on( 'listviewenternewitem', function( event, $newLi ) {
			if( event.target !== $listview[0] ) {
				return;
			}

			// Enter first item into the referenceview.
			self._referenceviewLia.liInstance( $newLi ).enterNewItem();

			var lia = self._referenceviewLia,
				liInstance = lia.liInstance( $newLi );

			if ( !liInstance.value() ) {
				$newLi
				.on( lia.prefixedEvent( 'afterstopediting' ), function( event, dropValue ) {
					if( dropValue ) {
						liInstance.destroy();
						$newLi.remove();
						self._drawReferencesCounter();
					} else {
						var newReferenceWithHash = liInstance.value();

						// Destroy new reference input form and add reference to list
						liInstance.destroy();
						$newLi.remove();

						// Display new reference with final GUID
						self._addReference( newReferenceWithHash );
					}
				} );
			}
		} );

		// Collapse references if there is at least one.
		if ( this._referencesListview.items().length > 0 ) {
			this.$references.css( 'display', 'none' );
		}

		// toggle for references section:
		var $toggler = $( '<a/>' ).toggler( { $subject: this.$references } );

		if( this.$refsHeading.text() ) {
			$toggler.find( '.ui-toggler-label' ).text( this.$refsHeading.text() );
			this.$refsHeading.html( $toggler );
		} else {
			this.$refsHeading.html( $toggler );
			this._drawReferencesCounter();
		}
	},

	/**
	 * Updates the `helpMessage` option according to whether the main `Snak`'s `Property` is
	 * predefined.
	 * @private
	 */
	_updateHelpMessage: function() {
		if( !this.options.value && !this.options.predefined.mainSnak ) {
			return;
		}

		var property = this.options.value
			? this.options.value.getClaim().getMainSnak().getPropertyId()
			: this.options.predefined.mainSnak.property;

		var deferred = $.Deferred(),
			helpMessage = this.options.helpMessage;

		this.options.helpMessage = deferred.promise();

		if( property ) {
			this.options.entityStore.get( property ).done( function( fetchedProperty ) {
				if( fetchedProperty ) {
					helpMessage = mw.msg(
						'wikibase-claimview-snak-tooltip',
						wb.utilities.ui.buildPrettyEntityLabelText( fetchedProperty.getContent() )
					);
				}
				deferred.resolve( helpMessage );
			} );
		} else {
			deferred.resolve( helpMessage );
		}
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		this._rankSelector.destroy();
		this.$rankSelector.off( '.' + this.widgetName );

		this.$mainSnak.snakview( 'destroy' );
		this.$mainSnak.off( '.' + this.widgetName );

		this._destroyQualifiersListView();

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @private
	 */
	_destroyQualifiersListView: function() {
		if( this._qualifiers ) {
			this._qualifiers.destroy();
			this.$qualifiers
				.off( '.' + this.widgetName )
				.empty();
			this._qualifiers = null;
		}
	},

	/**
	 * @inheritdoc
	 */
	draw: function() {
		this._createRankSelector( this.options.value
			? this.options.value.getRank()
			: wb.datamodel.Statement.RANK.NORMAL
		);

		this._createMainSnak( this.options.value
				? this.options.value.getClaim().getMainSnak()
				: this.option( 'predefined' ).mainSnak || null
		);

		if( this.isInEditMode() ) {
			this._createQualifiersListview(
				this.options.value
					? this.options.value.getClaim().getQualifiers()
					: new wb.datamodel.SnakList()
			);
		}
		this._createReferences( this.options.value );

		return $.Deferred().resolve().promise();
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		if( this.options.value ) {
			if( !this._rankSelector.isInitialValue() ) {
				return false;
			}

			var snaklistviews = ( this._qualifiers ) ? this._qualifiers.value() : [],
				qualifiers = new wb.datamodel.SnakList();

			// Generate a SnakList object featuring all current qualifier snaks to be able to
			// compare it to the SnakList object the claimview has been initialized with:
			if( snaklistviews.length ) {
				for( var i = 0; i < snaklistviews.length; i++ ) {
					if( snaklistviews[i].value() ) {
						qualifiers.merge( snaklistviews[i].value() );
					}
				}
			}

			if( !qualifiers.equals( this.options.value.getClaim().getQualifiers().length ) ) {
				return false;
			}
		}

		return this.$mainSnak.data( 'snakview' ).isInitialValue();
	},

	/**
	 * Instantiates a `Statement` with the `statementview`'s current value.
	 * @private
	 *
	 * @param {string} guid
	 * @return {wikibase.datamodel.Statement|null}
	 */
	_instantiateStatement: function( guid ) {
		var mainSnak = this.$mainSnak.data( 'snakview' ).snak();

		if( !mainSnak ) {
			return null;
		}

		var qualifiers = new wb.datamodel.SnakList(),
			snaklistviews = this._qualifiers ? this._qualifiers.value() : [];

		// Combine qualifiers grouped by property to a single SnakList:
		for( var i = 0; i < snaklistviews.length; i++ ) {
			var snakList = snaklistviews[i].value();
			if( snakList ) {
				qualifiers.merge( snakList );
			}
		}

		return new wb.datamodel.Statement(
			new wb.datamodel.Claim( mainSnak, qualifiers, guid ),
			new wb.datamodel.ReferenceList( this._getReferences() ),
			this._rankSelector.value()
		);
	},

	/**
	 * Adds a `Reference` and renders it in the view.
	 * @private
	 *
	 * @param {wikibase.datamodel.Reference} reference
	 */
	_addReference: function( reference ) {
		this._referencesListview.addItem( reference );
	},

	/**
	 * Returns all `Reference`s currently specified in the view (including all pending changes).
	 * @private
	 *
	 * @return {wikibase.datamodel.Reference[]}
	 */
	_getReferences: function() {
		var self = this,
			references = [];

		// If the statement is pending (not yet stored), the listview widget for the references is
		// not defined.
		if( !this._referencesListview ) {
			return references;
		}

		$.each( this._referencesListview.items(), function( i, item ) {
			var referenceview = self._referenceviewLia.liInstance( $( item ) ),
				reference = referenceview ? referenceview.value() : null;
			if( reference ) {
				references.push( reference );
			}
		} );

		return references;
	},

	/**
	 * Removes a `referenceview` from the view's list of `referenceview`s.
	 *
	 * @param {jQuery.wikibase.referenceview} referenceview
	 */
	remove: function( referenceview ) {
		var self = this;

		referenceview.disable();

		this._referencesChanger.removeReference(
			this.value().getClaim().getGuid(),
			referenceview.value()
		)
		.done( function() {
			self._referencesListview.removeItem( referenceview.element );
			self._trigger( 'afterremove' );
		} ).fail( function( error ) {
			referenceview.enable();
			referenceview.setError( error );
		} );
	},

	/**
	 * Returns the current `Statement` represented by the view, considering all pending changes not
	 * yet stored. Use `this.option( 'value' )` to retrieve the stored/original `Statement`.
	 *
	 * @return {wikibase.datamodel.Statement|null}
	 */
	value: function() {
		var guid = this.options.value ? this.options.value.getClaim().getGuid() : null;
		return this._instantiateStatement( guid );
	},

	/**
	 * Updates the visual `Reference`s counter.
	 * @private
	 */
	_drawReferencesCounter: function() {
		var numberOfValues = this._referencesListview.nonEmptyItems().length,
			numberOfPendingValues = this._referencesListview.items().length - numberOfValues;

		// build a nice counter, displaying fixed and pending values:
		var $counterMsg = wb.utilities.ui.buildPendingCounter(
			numberOfValues,
			numberOfPendingValues,
			'wikibase-statementview-referencesheading-pendingcountersubject',
			'wikibase-statementview-referencesheading-pendingcountertooltip' );

		// update counter, don't touch the toggle!
		this.$refsHeading.find( '.ui-toggler-label' ).empty().append( $counterMsg );
	},

	/**
	 * @inheritdoc
	 */
	startEditing: function() {
		var self = this,
			deferred = $.Deferred();

		this.$mainSnak.one( 'snakviewafterstartediting', function() {
			PARENT.prototype.startEditing.call( self ).done( function() {
				self._rankSelector.startEditing();

				if( self._qualifiers ) {
					var snaklistviews = self._qualifiers.value();
					if( snaklistviews.length ) {
						for( var i = 0; i < snaklistviews.length; i++ ) {
							snaklistviews[i].startEditing();
						}
					}
				}

				deferred.resolve();
			} )
			.fail( deferred.reject );
		} );

		this.$mainSnak.data( 'snakview' ).startEditing();

		return deferred.promise();
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_afterStopEditing: function( dropValue ) {
		if( this.$mainSnak.data( 'snakview' ) ) {
			this.$mainSnak.data( 'snakview' ).stopEditing( dropValue );
		}
		this._stopEditingQualifiers( dropValue );
		this._rankSelector.stopEditing( dropValue );

		return PARENT.prototype._afterStopEditing.call( this, dropValue );
	},

	/**
	 * @private
	 *
	 * @param {boolean} [dropValue=false]
	 */
	_stopEditingQualifiers: function( dropValue ) {
		var snaklistviews,
			i;

		if( this._qualifiers ) {
			snaklistviews = this._qualifiers.value();

			if( snaklistviews.length ) {
				for( i = 0; i < snaklistviews.length; i++ ) {
					snaklistviews[i].stopEditing( dropValue );

					if( dropValue && !snaklistviews[i].value() ) {
						// Remove snaklistview from qualifier listview if no snakviews are left in
						// that snaklistview:
						this._qualifiers.removeItem( snaklistviews[i].element );
					}
				}
			}
		}

		// Destroy and (if qualifiers still exist) re-create the qualifier listview in order to
		// re-group the qualifiers by their property. This will also send out the event to erase
		// the "add qualifier" toolbar.
		this._destroyQualifiersListView();

		var qualifiers = this.options.value ? this.options.value.getClaim().getQualifiers() : [];

		if( qualifiers.length > 0 ) {
			// Refill the qualifier listview with the initial (or new initial) qualifiers:
			this._createQualifiersListview( qualifiers );
		}
	},

	/**
	 * @inheritdoc
	 * @private
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {wikibase.datamodel.Statement} return.done.statement The saved statement.
	 * @return {Function} return.fail
	 * @return {wikibase.api.RepoApiError} return.fail.error
	 *
	 * @throws {Error} if unable to instantiate a `Statement` from the current view state.
	 */
	_save: function() {
		var self = this;

		var guid = this.options.value
			? this.options.value.getClaim().getGuid()
			: this.options.guidGenerator.newGuid();

		var statement = this._instantiateStatement( guid );

		if( !statement ) {
			throw new Error( 'Unable to instantiate Statement' );
		}

		return this.option( 'claimsChanger' ).setStatement( statement )
		.done( function( savedStatement ) {
			// Update model of represented Statement:
			self.options.value = savedStatement;
		} );
	},

	/**
	 * Exits edit mode and restores the value from before the edit mode has been started.
	 * (Shortcut to `this.stopEditing( true )`)
	 *
	 * @return {undefined}
	 */
	cancelEditing: function() {
		return this.stopEditing( true );
	},

	/**
	 * @inheritdoc
	 */
	isEmpty: function() {
		return false;
		// TODO: Supposed to do at least...
		// this.$mainSnak.data( 'snakview' ).isEmpty(); (does not exist at the moment of writing)
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		if( this._qualifiers ) {
			var snaklistviews = this._qualifiers.value();

			if( snaklistviews.length ) {
				for( var i = 0; i < snaklistviews.length; i++ ) {
					if( !snaklistviews[i].isValid() ) {
						return false;
					}
				}
			}
		}

		return this._instantiateStatement( null ) instanceof wb.datamodel.Statement;
	},

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} when tyring to set `value` option.
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.$mainSnak.data( 'snakview' ).option( key, value );
			if( this._qualifiers ) {
				this._qualifiers.option( key, value );
			}
			this._rankSelector.option( key, value );
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		this.$mainSnak.data( 'snakview' ).focus();
	}
} );

}( mediaWiki, wikibase, jQuery ) );
