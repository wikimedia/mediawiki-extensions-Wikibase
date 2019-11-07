import { createLocalVue, shallowMount } from '@vue/test-utils';
import Vuex, { Store } from 'vuex';
import Application from '@/store/Application';
import { createStore } from '@/store';
import Popper from '@/presentation/components/Popper.vue';
import { POPPER_HIDE } from '@/store/actionTypes';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'Popper.vue', () => {
	it( 'should render the Popper', () => {
		const store: Store<Application> = createStore();
		const wrapper = shallowMount( Popper, {
			store,
			localVue,
		} );
		expect( wrapper.classes() ).toContain( 'wb-tr-popper-wrapper' );
	} );
	it( 'closes the popper when the x is clicked', () => {
		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();
		const parentComponentStub = {
			name: 'parentStub',
			template: '<div></div>',
			data: () => {
				return { id: 'a-guid' };
			},
		};

		const wrapper = shallowMount( Popper, {
			store,
			localVue,
			parentComponent: parentComponentStub,
		} );
		wrapper.find( '.wb-tr-popper-close' ).trigger( 'click' );
		expect( store.dispatch ).toHaveBeenCalledWith( POPPER_HIDE, 'a-guid' );
	} );
} );
