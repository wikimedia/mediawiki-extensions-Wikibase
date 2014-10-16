/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $ ) {
	'use strict';

	var WIDGET_NAME = 'claimgrouplabelscroll';

	/**
	 * For keeping track of currently active claimgrouplabelscroll widgets which need updates on
	 * certain browser window events.
	 *
	 * NOTE: In this performance critical case this makes more sense than jQuery's widget selector.
	 *
	 * @type {jQuery.wikibase.claimgrouplabelscroll[]}
	 */
	var activeInstances = [];

	function updateActiveInstances() {
		for( var i in activeInstances ) {
			activeInstances[i].update();
		}
	}

	function registerWidgetInstance( instance ) {
		if( activeInstances.length === 0 ) {
			$( window ).on(
				'scroll resize'.replace( /(\w+)/g, '$1.' + WIDGET_NAME ),
				updateActiveInstances
			);
		}
		activeInstances.push( instance );
	}

	function unregisterWidgetInstance( instance ) {
		var index = $.inArray( instance );
		if( index ) {
			activeInstances.splice( index, 1 );
		}
		if( activeInstances.length === 0 ) {
			$( window ).off( '.' + WIDGET_NAME );
		}
	}

	/**
	 * Name of the animation queue used for animations moving the claim group labels around.
	 * @type {string}
	 */
	var ANIMATION_QUEUE = 'wikibase-' + WIDGET_NAME;

	/**
	 * Counter for expensive checks done in an update. Used for debugging output.
	 * @type {number}
	 */
	var expensiveChecks = 0;

	/**
	 * Widget which will reposition labels of Claim groups while scrolling through the page. This
	 * ensures that the labels are always displayed on the same line with the first Main Snak
	 * visible within the viewport. When the label gets moved, the movement is animated for a smooth
	 * transition.
	 *
	 * TODO: Consider the rare case where window.scrollTo() is used. In that case we should move all
	 *  labels below the top of the new viewport position to the first claim and all labels above
	 *  the viewport position to the last claim in their group.
	 *
	 * @since 0.4
	 *
	 * @widget jQuery.wikibase.claimgrouplabelscroll
	 * @extends jQuery.Widget
	 */
	$.widget( 'wikibase.' + WIDGET_NAME, {
		/**
		 * @see jQuery.widget.options
		 * @type {Object}
		 */
		options: {
			/**
			 * If set, this object will be used for logging certain debug messages. Requires a
			 * member called "log" taking any value as parameter 1 to n.
			 *
			 * @type {Object|null}
			 */
			logger: null
		},

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			registerWidgetInstance( this );

			// Assume that all labels are in the proper place if no scrolling has happened yet.
			if( window.pageYOffset ) {
				this.update();
			}
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			unregisterWidgetInstance( this );
		},

		/**
		 * Will update the position of the claimgroup labels the widget is controlling.
		 *
		 * @since 0.4
		 */
		update: function() {
			var startTime = new Date().getTime();

			expensiveChecks = 0;

			var $visibleMainSnaks =
					findFirstVisibleMainSnakElementsWithinClaimList( this.element );

			for( var i = 0; i < $visibleMainSnaks.length; i++ ) {
				var $visibleMainSnak = $visibleMainSnaks.eq( i ),
					$claimGroup = $visibleMainSnak.closest( '.wb-claimlistview' ),
					$claimNameSection = $claimGroup.children( '.wb-claimgrouplistview-groupname' ),
					$claimGroupLabel = $claimNameSection.children( '.wb-claim-name' );

				if( !$claimNameSection.length ) {
					// No claim name present (claim likely is pending).
					continue;
				}

				this._log(
					'positioning',
					$claimGroupLabel.get( 0 ),
					'on',
					$visibleMainSnak.get( 0 )
				);

				var newLabelPosition =
					positionElementInOneLineWithAnother( $claimGroupLabel, $visibleMainSnak );

				this._log( newLabelPosition
					? ( 'moving label to ' + newLabelPosition )
					: 'no position update required'
				);

				var endTime = new Date().getTime();
				this._log( expensiveChecks + ' expensive checks, execution time '
					+ ( endTime - startTime ) + 'ms' );
			}
		},

		/**
		 * If the "logger" option is set, then this method will forward any given arguments
		 * to its "log" function.
		 */
		_log: function() {
			var logger = this.option( 'logger' );
			if( logger ) {
				logger.log.apply( logger, arguments );
			}
		}
	} );

	/**
	 * Returns an array with the active instances of the widget. A widget instance is considered
	 * active after its first initialization and inactive after its "destroy" function got called.
	 *
	 * @return $.wikibase.claimgrouplabelscroll[]
	 */
	$.wikibase[ WIDGET_NAME ].activeInstances = function() {
		return activeInstances.slice();
	};

	/**
	 * Checks an Claim Group's element for Main Snak elements and returns all that are visible in
	 * the browser's viewport.
	 * This is an optimized version of "findFirstVisibleMainSnakElement" in case Claim groups
	 * are expected within the DOM that should be searched for Main Snaks.
	 *
	 * @param {jQuery} $searchRange
	 * @return {jQuery}
	 */
	function findFirstVisibleMainSnakElementsWithinClaimList( $searchRange ) {
		var $claimGroups = $searchRange.find( '.wb-claimlistview' ),
			$visibleClaimGroups = $();

		// TODO: Optimize! E.g.:
		//  (1) don't walk them top to bottom, instead, take the one in the middle, check whether
		//      it is within/above/below viewport and exclude following/preceding ones which are
		//      obviously not within the viewport.
		//  (2) remember last visible node, start checking there and depending on scroll movement
		//      (up/down) on its neighbouring nodes.
		$claimGroups.each( function( i, claimGroupNode ) {
			if( elementPartlyVerticallyInViewport( claimGroupNode ) ) {
				var $mainSnakElement = findFirstVisibleMainSnakElement( $( claimGroupNode ) );
				$visibleClaimGroups = $visibleClaimGroups.add( $mainSnakElement );
			}
		} );

		return $visibleClaimGroups;
	}

	/**
	 * Checks an element for Main Snak elements and returns the first one visible in the browser's
	 * viewport.
	 *
	 * @param {jQuery} $searchRange
	 * @return {null|jQuery}
	 */
	function findFirstVisibleMainSnakElement( $searchRange ) {
		var result = null;

		// ".wb-snak-value-container" is better than using ".wb-claim-mainsnak" since we don't
		// care about whether the margin/padding around the value is within viewport or not.
		var $mainSnaks =
				$searchRange.find( '.wb-claim-mainsnak' ).children( '.wb-snak-value-container' );

		$mainSnaks.each( function( i, mainSnakNode ) {
			// Take first Main Snak value in viewport. If value is not fully visible in viewport,
			// check whether the next one is fully visible, if so, take that one.
			if( elementPartlyVerticallyInViewport( mainSnakNode ) ) {
				result = $( mainSnakNode );

				if( !elementFullyVerticallyInViewport( mainSnakNode ) ) {
					var nextMainSnakNode = $mainSnaks.get( i+1 );
					if( nextMainSnakNode && elementFullyVerticallyInViewport( nextMainSnakNode ) ) {
						result = $( nextMainSnakNode );
					}
				}
				return false;
			}
		} );

		if( result ) {
			// Don't forget to get the actual Snak node rather than the value container.
			result = result.closest( '.wb-claim-mainsnak');
		}
		return result;
	}

	/**
	 * Takes an element and positions it to be vertically at the same position as another given
	 * element. Animates the element to move towards that position.
	 *
	 * @param {jQuery} $element
	 * @param {jQuery} $target
	 * @return {bool|string} false if the position requires no update, otherwise the string of
	 *          the "top" css style after the animation will be complete.
	 */
	function positionElementInOneLineWithAnother( $element, $target ) {
		var elementNode = $element.get( 0 ),
			targetNode = $target.get( 0 );

		var newElementOffset = absoluteOffsetFromTop( targetNode ) - absoluteOffsetFromTop( elementNode.offsetParent ),
			currentElementOffset = $element.css( 'top' );

		// Have '0' without 'px' suffix, make it a string either way:
		newElementOffset = newElementOffset ? newElementOffset + 'px' : '0';

		if( currentElementOffset === 'auto' && newElementOffset === '0'
			|| currentElementOffset === newElementOffset
		) {
			return false;
		}

		$element
		.css( 'position', 'relative' )
		.stop( ANIMATION_QUEUE, true, false ) // stop all queued animations, don't jump to end
		.animate(
			{
				top: newElementOffset
			}, {
				queue: ANIMATION_QUEUE,
				easing: 'easeInOutCubic',
				duration: 'normal'
			}
		).dequeue( ANIMATION_QUEUE ); // run animations in queue

		return newElementOffset;
	}

	function absoluteOffsetFromTop( elem ) {
		++expensiveChecks;
		var top = 0;
		while( elem ) {
			top += elem.offsetTop;
			elem = elem.offsetParent;
		}
		return top;
	}

	function elementFullyVerticallyInViewport( elem ) {
		var top = absoluteOffsetFromTop( elem );
		return (
			top >= window.pageYOffset
			&& ( top + elem.offsetHeight ) <= ( window.pageYOffset + window.innerHeight )
		);
	}

	function elementPartlyVerticallyInViewport( elem ) {
		var top = absoluteOffsetFromTop( elem );
		return (
			top < ( window.pageYOffset + window.innerHeight )
			&& ( top + elem.offsetHeight ) > window.pageYOffset
		);
	}

}( jQuery ) );
