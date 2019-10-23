import App from '@/presentation/App.vue';
import { createLocalVue, shallowMount } from '@vue/test-utils';
import Vuex, { Store } from 'vuex';
import Application from '@/store/Application';
import { createStore } from '@/store';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { STATEMENT_TAINTED_STATE_INIT } from '@/store/actionTypes';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'App.vue', () => {
	it( 'should render the mounted root element', () => {
		const store: Store<Application> = createStore();
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );
		expect( wrapper.classes() ).toContain( 'wb-tr-app' );
	} );
	it( 'should render the TaintedIcon when the statement is tainted', () => {
		const store: Store<Application> = createStore();
		const wrapper = shallowMount( App, {
			store,
			localVue,
			data: () => { return { id: 'fooId' }; },
		} );
		store.dispatch( STATEMENT_TAINTED_STATE_INIT, [ 'fooId' ] );
		expect( wrapper.find( TaintedIcon ).exists() ).toBeFalsy();
	} );
} );
