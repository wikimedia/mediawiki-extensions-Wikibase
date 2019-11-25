import Initializing from '@/presentation/components/Initializing.vue';
import { shallowMount } from '@vue/test-utils';
import IndeterminateProgressBar from '@/presentation/components/IndeterminateProgressBar.vue';

describe( 'Initializing', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	it( 'is a Vue instance', () => {
		const wrapper = shallowMount( Initializing, {
			propsData: {
				isInitializing: false,
			},
		} );
		expect( wrapper.isVueInstance() ).toBeTruthy();
	} );

	it( 'renders default slot if constructed as not initializing', () => {
		const content = 'Already initialized content';
		const wrapper = shallowMount( Initializing, {
			propsData: {
				isInitializing: false,
			},
			slots: {
				default: content,
			},
		} );
		expect( wrapper.text() ).toBe( content );
	} );

	describe( 'when initializing', () => {
		it( 'renders empty loading screen and hides default slot', () => {
			const wrapper = shallowMount( Initializing, {
				propsData: {
					isInitializing: true,
				},
			} );
			expect( wrapper.html() ).toBe( '<div class="wb-db-init"><!----></div>' );
		} );

		it( 'renders IndeterminateProgressBar after TIME_UNTIL_CONSIDERED_SLOW', () => {
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const wrapper = shallowMount( Initializing, {
				propsData: {
					isInitializing: true,
					TIME_UNTIL_CONSIDERED_SLOW,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW + 1 );

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBeTruthy();
		} );

		// Scenario 3 (one half)
		it( 'keeps showing IndeterminateProgressBar even after MINIMUM_TIME_OF_PROGRESS_ANIMATION', () => {
			const TIME_UNTIL_CONSIDERED_SLOW = 5;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 15;
			const wrapper = shallowMount( Initializing, {
				propsData: {
					isInitializing: true,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
			} );

			// way after minimum animation time
			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW + MINIMUM_TIME_OF_PROGRESS_ANIMATION * 2 );

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBeTruthy();
		} );
	} );

	describe( 'when initializing is done', () => {
		// Scenario 1
		it( 'renders content right away if initialized before TIME_UNTIL_CONSIDERED_SLOW', () => {
			const content = 'Content';
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const wrapper = shallowMount( Initializing, {
				propsData: {
					isInitializing: true,
					TIME_UNTIL_CONSIDERED_SLOW,
				},
				slots: {
					default: content,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW / 2 ); // well before considered slow
			wrapper.setProps( { isInitializing: false } );

			expect( wrapper.text() ).toBe( content );
		} );

		// Scenario 2
		it( 'keeps showing IndeterminateProgressBar during MINIMUM_TIME_OF_PROGRESS_ANIMATION', () => {
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 20;
			const wrapper = shallowMount( Initializing, {
				propsData: {
					isInitializing: true,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW ); // just after considered slow
			wrapper.setProps( { isInitializing: false } );
			jest.advanceTimersByTime( MINIMUM_TIME_OF_PROGRESS_ANIMATION - 1 ); // just before animation end

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBeTruthy();
		} );

		// Scenario 2
		it( 'renders content after initializing done & MINIMUM_TIME_OF_PROGRESS_ANIMATION', () => {
			const content = 'Content';
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 20;
			const wrapper = shallowMount( Initializing, {
				propsData: {
					isInitializing: true,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
				slots: {
					default: content,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW ); // just after considered slow
			wrapper.setProps( { isInitializing: false } );
			jest.advanceTimersByTime( MINIMUM_TIME_OF_PROGRESS_ANIMATION ); // just after animation end

			expect( wrapper.text() ).toBe( content );
		} );

		// Scenario 3 (second half)
		it( 'renders content right away after MINIMUM_TIME_OF_PROGRESS_ANIMATION & initializing done', () => {
			const content = 'Content';
			const TIME_UNTIL_CONSIDERED_SLOW = 5;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 15;
			const wrapper = shallowMount( Initializing, {
				propsData: {
					isInitializing: true,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
				slots: {
					default: content,
				},
			} );

			// way after minimum animation time
			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW + MINIMUM_TIME_OF_PROGRESS_ANIMATION * 2 );
			wrapper.setProps( { isInitializing: false } );

			expect( wrapper.text() ).toBe( content );
		} );
	} );
} );
