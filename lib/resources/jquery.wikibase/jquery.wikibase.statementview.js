/**
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.wikibase.claimview;

/**
 * View for displaying and editing Wikibase Statements.
 * @since 0.4
 * @extends $.wikibase.claimview
 *
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @event afterremove: Triggered after a reference(view) has been remove from the statementview's
 *        list of references/-views.
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.statementview', PARENT, {
	options: {
		template: 'wb-statement',
		templateParams: [
			function() { // Rank selector
				return $( '<div>' );
			},
			function() { // class='wb-claim-$2'
				return ( this._claim && this._claim.getGuid() ) || 'new';
			},
			function() {
				return $( '<div/>' );
			}, // .wb-claim-mainsnak
			'', // Qualifiers
			'', // TODO: This toolbar placeholder should be removed from the template.
			'', // References heading
			'' // List of references
		],
		templateShortCuts: {
			'$rankSelector': '.wb-statement-rank',
			'$mainSnak': '.wb-claim-mainsnak > :first-child',
			'$qualifiers': '.wb-statement-qualifiers',
			'$refsHeading': '.wb-statement-references-heading',
			'$references': '.wb-statement-references'
		},
		entityChangersFactory: null
	},

	/**
	 * Shortcut to the list item adapter in use in the reference view.
	 * @type {$.wikibase.listview.ListItemAdapter}
	 */
	_referenceviewLia: null,

	/**
	 * Shortcut to the listview holding the reference views.
	 * @type {$.wikibase.listview}
	 */
	_referencesListview: null,

	/**
	 * @type {wikibase.entityChangers.ReferencesChanger}
	 */
	_referencesChanger: null,

	/**
	 * @see jQuery.claimview._create
	 */
	_create: function() {
		if( !this.options.entityStore || !this.options.valueViewBuilder || !this.options.entityChangersFactory ) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		var self = this,
			statement = this.value(),
			refs = statement ? statement.getReferences() : [];

		this._createRankSelector( statement ? statement.getRank() : null );

		this._referencesChanger = this.options.entityChangersFactory.getReferencesChanger();

		function indexOf( element, array ) {
			var index = $.inArray( element, array );
			return ( index !== -1 ) ? index : null;
		}

		if( this.value() ) {
			var $listview = this.$references.children();
			if( !$listview.length ) {
				$listview = $( '<div/>' ).prependTo( this.$references );
			}

			$listview.listview( {
				listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
					listItemWidget: $.wikibase.referenceview,
					newItemOptionsFn: function( value ) {
						var index = indexOf( value, self.value().getReferences() );
						if( index === null ) {
							// The empty list view item for this is already appended to the list view
							index = self._referencesListview.items().length - 1;
						}
						return {
							value: value || null,
							statementGuid: self.value().getGuid(),
							index: index,
							entityStore: self.option( 'entityStore' ),
							valueViewBuilder: self.option( 'valueViewBuilder' ),
							referencesChanger: self._referencesChanger
						};
					}
				} ),
				value: refs
			} );

			this._referencesListview = $listview.data( 'listview' );

			this._referenceviewLia = this._referencesListview.listItemAdapter();

			$listview
			.on( 'listviewitemadded listviewitemremoved', function( event, value, $li ) {
				if( event.target === $listview.get( 0 ) ) {
					self.drawReferencesCounter();
					self._updateReferenceIndices();
				}
			} )
			.on( 'referenceviewafterstopediting', function( event, dropValue ) {
				if( dropValue ) {
					// Re-order claims according to their initial indices:
					var $referenceviews = self._referencesListview.items();

					for( var i = 0; i < $referenceviews.length; i++ ) {
						var referenceview = self._referenceviewLia.liInstance( $referenceviews.eq( i ) );
						self._referencesListview.move( $referenceviews.eq( i ), referenceview.getInitialIndex() );
					}
				}
			} )
			.on( 'listviewenternewitem', function( event, $newLi ) {
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
							self.drawReferencesCounter();
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
				this.drawReferencesCounter();
			}

			this._updateReferenceIndices();
		}
	},

	/**
	 * Creates the rank selector to select the statement rank.
	 * @since 0.5
	 *
	 * @param {number} rank
	 */
	_createRankSelector: function( rank ) {
		var $rankSelector = this.$rankSelector.children().first();
		this._rankSelector = new $.wikibase.statementview.RankSelector( {
			rank: rank,
			templateParams: ['ui-state-disabled', '', '']
		}, $rankSelector );

		var self = this,
			changeEvent = ( this._rankSelector.widgetEventPrefix + 'afterchange' ).toLowerCase();

		this.$rankSelector.on( changeEvent + '.' + this.widgetName, function( event ) {
			if( self.value() ) {
				self._trigger( 'change' );
			}
		} );

		this.element
		.on( this.widgetEventPrefix + 'afterstartediting.' + this.widgetName, function( event ) {
			// FIXME: This should be the responsibility of the rankSelector
			$rankSelector.addClass( 'ui-state-default' );
			if( !self._claim ) {
				self._rankSelector.rank( wb.datamodel.Statement.RANK.NORMAL );
			}
			self._rankSelector.enable();
		} )
		.on(
			this.widgetEventPrefix + 'stopediting.' + this.widgetName,
			function( event, dropValue ) {
				self._rankSelector.disable();
			}
		)
		.on( this.widgetEventPrefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			if( !error ) {
				self._rankSelector.enable();
			}
		} )
		.on(
			this.widgetEventPrefix + 'afterstopediting.' + this.widgetName,
			function( event, dropValue ) {
				// FIXME: This should be the responsibility of the rankSelector
				$rankSelector.removeClass( 'ui-state-default' );
				if( dropValue && self._claim ) {
					self._rankSelector.rank( self._claim.getRank() );
				}
				self._rankSelector.disable();
			}
		);
	},

	/**
	 * @see $.wikibase.claimview.isInitialValue
	 */
	isInitialValue: function() {
		if( !PARENT.prototype.isInitialValue.call( this ) ) {
			return false;
		}
		if( this._claim && this._rankSelector ) {
			return this._claim.getRank() === this._rankSelector.rank();
		}
		return true;
	},

	/**
	 * Instantiates a statement with the statementview's current value.
	 * @see $.wikibase.claimview._instantiateClaim
	 *
	 * @param {string} guid
	 * @return {wb.datamodel.Statement}
	 */
	_instantiateClaim: function( guid ) {
		var qualifiers = null;

		// Gather qualifiers in one single wb.datamodel.SnakList object. (The qualifiers are split into
		// separate snaklistivews grouping snaks featuring the same property.)
		if( this._qualifiers ) {
			var snaklistviews = this._qualifiers.value();

			qualifiers = new wb.datamodel.SnakList();

			for( var i = 0; i < snaklistviews.length; i++ ) {
				qualifiers.add( snaklistviews[i].value() );
			}
		}

		return new wb.datamodel.Statement(
			this.$mainSnak.data( 'snakview' ).snak(),
			qualifiers,
			this.getReferences(),
			this._rankSelector.rank(),
			guid
		);
	},

	/**
	 * Adds one reference to the list and renders it in the view.
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Reference} reference
	 */
	_addReference: function( reference ) {
		this._referencesListview.addItem( reference );
	},

	/**
	 * Returns all references currently set (including all pending changes).
	 *
	 * @return {wb.datamodel.Reference[]}
	 */
	getReferences: function() {
		var self = this,
			references = [];

		// If the statement is pending (not yet stored), the listview widget for the references is
		// not defined.
		if ( !this._referencesListview ) {
			return references;
		}

		$.each( this._referencesListview.items(), function( i, item ) {
			var referenceview = self._referenceviewLia.liInstance( $( item ) );
			references.push( referenceview.value() );
		} );

		return references;
	},

	/**
	 * Updates the reference view indices.
	 * @since 0.5
	 */
	_updateReferenceIndices: function() {
		var $referenceviews = this._referencesListview.items();

		for( var i = 0; i < $referenceviews.length; i++ ) {
			var referenceview = this._referenceviewLia.liInstance( $referenceviews.eq( i ) );
			referenceview.option( 'index', i );
		}
	},

	/**
	 * Removes a referenceview from the list of references.
	 * @since 0.4
	 *
	 * @param {$.wikibase.referenceview} referenceview
	 */
	remove: function( referenceview ) {
		var self = this;

		referenceview.disable();

		this._referencesChanger.removeReference(
			this.value().getGuid(),
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
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		this._rankSelector.destroy();
		this.$rankSelector.off( '.' + this.widgetName );

		this.element.removeClass( 'wb-claimview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Returns the current Statement represented by the view. If null is returned, than this is a
	 * fresh view where a new Statement is being constructed.
	 *
	 * @since 0.4
	 *
	 * @return {wb.datamodel.Statement|null}
	 */
	value: function() {
		var claim = this._claim;

		if( !claim ) {
			return null;
		}
		if( !( claim instanceof wb.datamodel.Statement ) ) {
			return new wb.datamodel.Statement( claim.getMainSnak(), null, [], 0, claim.getGuid() );
		}
		return claim;
	},

	/**
	 * Will update the references counter in the DOM.
	 *
	 * @since 0.4
	 */
	drawReferencesCounter: function() {
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
	}
} );

// Register toolbars:
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'references',
	selector: '.wb-statement-references',
	events: {
		listviewcreate: function( event, toolbarController ) {
			var $listview = $( event.target ),
				listview = $listview.data( 'listview' ),
				lia = listview.listItemAdapter(),
				$node = $listview.parent();

			if( !$node.hasClass( 'wb-statement-references' ) ) {
				return;
			}

			$node
			.addtoolbar( {
				$container: $( '<div/>' ).appendTo( $node ),
				label: mw.msg( 'wikibase-addreference' )
			} )
			.on( 'addtoolbaradd.addtoolbar', function( e ) {
				if( e.target !== $node.get( 0 ) ) {
					return;
				}

				listview.enterNewItem();

				// Re-focus "add" button after having added or having cancelled adding a reference:
				var eventName = lia.prefixedEvent( 'afterstopediting.addtoolbar' );
				$listview.one( eventName, function( event ) {
					$node.data( 'addtoolbar' ).focus();
				} );

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewdestroy',
					function( event, toolbarController ) {
						var $listview = $( event.target ),
							$node = $listview.parent();

						if( !$node.hasClass( '.wb-statement-references' ) ) {
							return;
						}

						toolbarController.destroyToolbar( $node.data( 'addtoolbar' ) );
						$node.off( 'addtoolbar' );
					}
				);
			} );

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'listviewdisable',
				function( event ) {
					if( event.target !== $listview.get( 0 ) ) {
						return;
					}
					$node.data( 'addtoolbar' )[
						listview.option( 'disabled' )
							? 'disable'
							: 'enable'
					]();
				}
			);
		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'referenceview',
	selector: ':' + $.wikibase.referenceview.prototype.namespace
		+ '-' + $.wikibase.referenceview.prototype.widgetName,
	events: {
		referenceviewcreate: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				options = {
					interactionWidget: referenceview
				},
				$container = $referenceview.find( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<div/>' ).appendTo(
					$referenceview.find( '.wb-referenceview-heading' )
				);
			}

			options.$container = $container;

			if( !!referenceview.value() ) {
				options.onRemove = function() {
					var $statementview = $referenceview.closest( ':wikibase-statementview' ),
						statementview = $statementview.data( 'statementview' );
					if( statementview ) {
						statementview.remove( referenceview );
					}
				};
			}

			$referenceview.edittoolbar( options );
		},
		referenceviewchange: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				edittoolbar = $referenceview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = referenceview.isValid() && !referenceview.isInitialValue();

			btnSave[enableSave ? 'enable' : 'disable']();
		},
		referenceviewdisable: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' );

			if( !referenceview ) {
				return;
			}

			var disable = referenceview.option( 'disabled' ),
				edittoolbar = $referenceview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = ( referenceview.isValid() && !referenceview.isInitialValue() );

			edittoolbar.option( 'disabled', disable );
			if( !disable ) {
				btnSave.option( 'disabled', !enableSave );
			}
		}

		// Destroying the referenceview will destroy the toolbar. Trying to destroy the toolbar
		// in parallel will cause interference.
	}
} );

$.wikibase.toolbarcontroller.definition( 'movetoolbar', {
	id: 'statementview-referenceview',
	selector: '.wb-referenceview',
	events: {
		'referenceviewstartediting': function( event, toolbarController ) {
			// Initialize movetoolbar.

			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				$statementview = $referenceview.closest( ':wikibase-statementview' ),
				statementview = $statementview.data( 'statementview' ),
				$referencesListview = statementview.$references.children( ':wikibase-listview' ),
				referencesListview = $referencesListview.data( 'listview' );

			if( !referenceview.value() ) {
				// Prevent creating the toolbar for pending values.
				return;
			}

			$referenceview.movetoolbar( {
				$container: $( '<div/>' ).appendTo( $referenceview )
			} );

			// Disable "move up" button of topmost and "move down" button of bottommost
			// referenceview:
			var $topMostReferenceview = referencesListview.items().first();
			var $bottomMostReferenceview = referencesListview.items().last();

			if ( $topMostReferenceview.get( 0 ) === $referenceview.get( 0 ) ) {
				$referenceview.data( 'movetoolbar' ).getButton( 'up' ).disable();
			}

			if( $bottomMostReferenceview.get( 0 ) === $referenceview.get( 0 ) ) {
				$referenceview.data( 'movetoolbar' ).getButton( 'down' ).disable();
			}

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'referenceviewafterstopediting',
				function( event, toolbarcontroller ) {
					toolbarcontroller.destroyToolbar( $( event.target ).data( 'movetoolbar' ) );
				}
			);

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'movetoolbarup movetoolbardown',
				function( event ) {
					var $referenceview = $( event.target ),
						referenceview = $referenceview.data( 'referenceview' );

					if( !referenceview ) {
						// Not the event of the corresponding toolbar but of some other movetoolbar.
						return;
					}

					var $statementview = $referenceview.closest( ':wikibase-statementview' ),
						statementview = $statementview.data( 'statementview' ),
						$referencesListview = statementview.$references.children( ':wikibase-listview' ),
						referencesListview = $referencesListview.data( 'listview' ),
						action = ( event.type === 'movetoolbarup' ) ? 'moveUp' : 'moveDown',
						referenceviewIndex = referencesListview.indexOf( $referenceview ),
						isLastListItem = ( referenceviewIndex !== referencesListview.items().length - 1 );

					if( action === 'moveUp' && referencesListview.indexOf( $referenceview ) !== 0 ) {
						referencesListview.moveUp( $referenceview );
					} else if( action === 'moveDown' && isLastListItem ) {
						referencesListview.moveDown( $referenceview );
					}

					// Disable "move up" button of topmost and "move down" button of bottommost
					// referenceview:
					var movetoolbar = $referenceview.data( 'movetoolbar' ),
						$topmostReferenceview = referencesListview.items().first(),
						isTopmost = $topmostReferenceview.get( 0 ) === $referenceview.get( 0 ),
						$bottommostReferenceview = referencesListview.items().last(),
						isBottommost = $bottommostReferenceview.get( 0 ) === $referenceview.get( 0 );

					movetoolbar.getButton( 'up' )[( isTopmost ) ? 'disable' : 'enable' ]();
					movetoolbar.getButton( 'down' )[( isBottommost ) ? 'disable' : 'enable' ]();

					// Update referenceview indices:
					var $referenceviews = referencesListview.items(),
						referenceListviewLia = referencesListview.listItemAdapter();

					for( var i = 0; i < $referenceviews.length; i++ ) {
						referenceview = referenceListviewLia.liInstance( $referenceviews.eq( i ) );
						referenceview.option( 'index', i );
					}
				}
			);

		}
	}
} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.wikibase.statementview.prototype.widgetBaseClass = 'wb-statementview';

}( mediaWiki, wikibase, jQuery ) );
