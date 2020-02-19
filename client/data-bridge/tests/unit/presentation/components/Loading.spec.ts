import Loading from '@/presentation/components/Loading.vue';
import { shallowMount } from '@vue/test-utils';
import { IndeterminateProgressBar } from '@wmde/wikibase-vuejs-components';

describe( 'Loading', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	it( 'is a Vue instance', () => {
		const wrapper = shallowMount( Loading, {
			propsData: {
				isInitializing: false,
				isSaving: false,
			},
		} );
		expect( wrapper.isVueInstance() ).toBe( true );
	} );

	it( 'renders default slot if constructed as not initializing', () => {
		const content = 'Already initialized content';
		const wrapper = shallowMount( Loading, {
			propsData: {
				isInitializing: false,
				isSaving: false,
			},
			slots: {
				default: content,
			},
		} );
		expect( wrapper.text() ).toBe( content );
	} );

	describe( 'when initializing', () => {
		it( 'renders empty loading screen and hides default slots', () => {
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: true,
					isSaving: false,
				},
			} );
			expect( wrapper.html() ).toBe( '<div class="wb-db-load"><!----> <!----></div>' );
		} );

		it( 'renders IndeterminateProgressBar after TIME_UNTIL_CONSIDERED_SLOW', () => {
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: true,
					isSaving: false,
					TIME_UNTIL_CONSIDERED_SLOW,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW + 1 );

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBe( true );
		} );

		// Scenario 3 (one half)
		it( 'keeps showing IndeterminateProgressBar even after MINIMUM_TIME_OF_PROGRESS_ANIMATION', () => {
			const TIME_UNTIL_CONSIDERED_SLOW = 5;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 15;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: true,
					isSaving: false,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
			} );

			// way after minimum animation time
			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW + MINIMUM_TIME_OF_PROGRESS_ANIMATION * 2 );

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBe( true );
		} );
	} );

	describe( 'when initializing is done', () => {
		// Scenario 1
		it( 'renders content right away if initialized before TIME_UNTIL_CONSIDERED_SLOW', () => {
			const content = 'Content';
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: true,
					isSaving: false,
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
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: true,
					isSaving: false,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW ); // just after considered slow
			wrapper.setProps( { isInitializing: false } );
			jest.advanceTimersByTime( MINIMUM_TIME_OF_PROGRESS_ANIMATION - 1 ); // just before animation end

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBe( true );
		} );

		// Scenario 2
		it( 'renders content after initializing done & MINIMUM_TIME_OF_PROGRESS_ANIMATION', () => {
			const content = 'Content';
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 20;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: true,
					isSaving: false,
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
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: true,
					isSaving: false,
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

	describe( 'when saving', () => {
		it( 'still shows the default slot', () => {
			const slot = 'Haiii';
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: false,
					isSaving: false,
				},
				slots: {
					default: slot,
				},
			} );
			wrapper.setProps( { isSaving: true } );

			expect( wrapper.text() ).toBe( slot );
		} );

		it( 'renders IndeterminateProgressBar after TIME_UNTIL_CONSIDERED_SLOW', () => {
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: false,
					isSaving: true,
					TIME_UNTIL_CONSIDERED_SLOW,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW + 1 );

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBe( true );
		} );

		it( 'keeps showing IndeterminateProgressBar even after MINIMUM_TIME_OF_PROGRESS_ANIMATION', () => {
			const TIME_UNTIL_CONSIDERED_SLOW = 5;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 15;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: true,
					isSaving: false,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
			} );

			// way after minimum animation time
			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW + MINIMUM_TIME_OF_PROGRESS_ANIMATION * 2 );

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBe( true );
		} );
	} );

	describe( 'when saving is done', () => {
		// Scenario 1
		it( 'hides IndeterminateProgressBar if saved before TIME_UNTIL_CONSIDERED_SLOW', () => {
			const content = 'Content';
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: false,
					isSaving: true,
					TIME_UNTIL_CONSIDERED_SLOW,
				},
				slots: {
					default: content,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW / 2 ); // well before considered slow
			wrapper.setProps( { isSaving: false } );

			expect( wrapper.text() ).toBe( content );
			expect( wrapper.find( IndeterminateProgressBar ).exists() ).toBeFalsy();
		} );

		// Scenario 2
		it( 'keeps showing IndeterminateProgressBar during MINIMUM_TIME_OF_PROGRESS_ANIMATION', () => {
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 20;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: false,
					isSaving: true,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW ); // just after considered slow
			wrapper.setProps( { isSaving: false } );
			jest.advanceTimersByTime( MINIMUM_TIME_OF_PROGRESS_ANIMATION - 1 ); // just before animation end

			expect( wrapper.find( IndeterminateProgressBar ).isVisible() ).toBe( true );
		} );

		// Scenario 2
		it( 'hides IndeterminateProgressBar after saving done & MINIMUM_TIME_OF_PROGRESS_ANIMATION', () => {
			const content = 'Content';
			const TIME_UNTIL_CONSIDERED_SLOW = 10;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 20;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: false,
					isSaving: true,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
				slots: {
					default: content,
				},
			} );

			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW ); // just after considered slow
			wrapper.setProps( { isSaving: false } );
			jest.advanceTimersByTime( MINIMUM_TIME_OF_PROGRESS_ANIMATION ); // just after animation end

			expect( wrapper.text() ).toBe( content );
			expect( wrapper.find( IndeterminateProgressBar ).exists() ).toBeFalsy();
		} );

		// Scenario 3 (second half)
		it( 'hides IndeterminateProgressBar right away after MINIMUM_TIME_OF_PROGRESS_ANIMATION & saving done', () => {
			const content = 'Content';
			const TIME_UNTIL_CONSIDERED_SLOW = 5;
			const MINIMUM_TIME_OF_PROGRESS_ANIMATION = 15;
			const wrapper = shallowMount( Loading, {
				propsData: {
					isInitializing: false,
					isSaving: true,
					TIME_UNTIL_CONSIDERED_SLOW,
					MINIMUM_TIME_OF_PROGRESS_ANIMATION,
				},
				slots: {
					default: content,
				},
			} );

			// way after minimum animation time
			jest.advanceTimersByTime( TIME_UNTIL_CONSIDERED_SLOW + MINIMUM_TIME_OF_PROGRESS_ANIMATION * 2 );
			wrapper.setProps( { isSaving: false } );

			expect( wrapper.text() ).toBe( content );
			expect( wrapper.find( IndeterminateProgressBar ).exists() ).toBeFalsy();
		} );
	} );
} );
