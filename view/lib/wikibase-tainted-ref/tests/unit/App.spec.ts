import { TrackFunction } from '@/@types/TrackingOptions';
import App from '@/presentation/App.vue';
import { shallowMount } from '@vue/test-utils';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { createStore } from '@/store';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { STORE_INIT, STATEMENT_TAINTED_STATE_TAINT, START_EDIT } from '@/store/actionTypes';

const mockTrackFunction: TrackFunction = jest.fn();

function getStore(): Store<Application> {
	return createStore( mockTrackFunction );
}

describe( 'App.vue', () => {
	it( 'should render the mounted root element', () => {
		const store = getStore();
		const wrapper = shallowMount( App as any, {
			global: { plugins: [ store ] },
			props: { id: 'fooId' },
		} );
		expect( wrapper.classes() ).toContain( 'wb-tr-app' );
	} );
	it( 'should render the TaintedIcon when the statement is tainted', async () => {
		const store = getStore();
		const wrapper = shallowMount( App as any, {
			global: { plugins: [ store ] },
			props: { id: 'fooId' },
		} );
		store.dispatch( STORE_INIT, [ 'fooId' ] );
		expect( wrapper.findComponent( TaintedIcon as any ).exists() ).toBeFalsy();
		store.dispatch( STATEMENT_TAINTED_STATE_TAINT, 'fooId' );
		await wrapper.vm.$nextTick();
		expect( wrapper.findComponent( TaintedIcon as any ).exists() ).toBeTruthy();
	} );
	it( 'should not render the TaintedIcon during edit', () => {
		const store = getStore();
		const wrapper = shallowMount( App as any, {
			global: { plugins: [ store ] },
			props: { id: 'fooId' },
		} );
		store.dispatch( STORE_INIT, [ 'fooId' ] );
		store.dispatch( STATEMENT_TAINTED_STATE_TAINT, 'fooId' );
		store.dispatch( START_EDIT, 'fooId' );
		expect( wrapper.findComponent( TaintedIcon as any ).exists() ).toBeFalsy();
	} );
} );
