import MessageKeys from '@/definitions/MessageKeys';
import EditDecision from '@/presentation/components/EditDecision.vue';
import RadioGroup from '@/presentation/components/RadioGroup.vue';
import { createStore } from '@/store';
import Application from '@/store/Application';
import {
	createLocalVue,
	mount,
	shallowMount,
} from '@vue/test-utils';
import { RadioInput } from '@wmde/wikibase-vuejs-components';
import Vuex, { Store } from 'vuex';
import { createTestStore } from '../../../util/store';
import newMockServiceContainer from '../../services/newMockServiceContainer';

const localVue = createLocalVue();

localVue.use( Vuex );

/**
 * Array.filter callback to return unique elements of an array.
 *
 * (The resulting array will contain elements in order of their last appearance
 * in the source array.)
 */
function unique<T>( value: T, index: number, array: readonly T[] ): boolean {
	return array.includes( value, index + 1 );
}

describe( 'EditDecision', () => {
	let store: Store<Application>;

	beforeEach( () => {
		store = createStore( newMockServiceContainer( {} ) );
	} );

	it( 'shows the Edit Decision message as the header', () => {
		const editDecisionHeading = 'edit decision heading';
		const getText = jest.fn(
			( key: string ) => {
				if ( key === MessageKeys.EDIT_DECISION_HEADING ) {
					return editDecisionHeading;
				}
				return '';
			},
		);
		const get = jest.fn( () => '' );

		const wrapper = shallowMount( EditDecision, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get,
					getText,
				},
			},
		} );

		expect( wrapper.text() ).toBe( editDecisionHeading );
		expect( getText ).toHaveBeenCalledWith( MessageKeys.EDIT_DECISION_HEADING );
	} );

	it( 'mounts RadioGroup and two RadioInputs', () => {
		const wrapper = shallowMount( EditDecision, {
			store,
			localVue,
		} );

		expect( wrapper.find( RadioGroup ).exists() ).toBe( true );
		expect( wrapper.findAll( RadioInput ) ).toHaveLength( 2 );
	} );

	it( 'passes the same name to all RadioInputs', () => {
		const wrapper = shallowMount( EditDecision, {
			store,
			localVue,
		} );

		const radioInputs = wrapper.findAll( RadioInput ).wrappers;
		const allNames = radioInputs.map( ( radioInput ) => radioInput.props( 'name' ) );
		const distinctNames = allNames.filter( unique );
		expect( distinctNames ).toHaveLength( 1 );
	} );

	it( 'dispatches action when radio button is selected', () => {
		const setEditDecisionAction = jest.fn();
		store = createTestStore( { actions: { 'setEditDecision': setEditDecisionAction } } );
		const wrapper = mount( EditDecision, {
			store,
			localVue,
		} );
		wrapper.find( 'input[value=replace]' ).setChecked( true );
		wrapper.find( 'input' ).trigger( 'input' );
		expect( setEditDecisionAction ).toHaveBeenCalledTimes( 1 );
		expect( setEditDecisionAction.mock.calls[ 0 ][ 0 ] ).toBe( 'replace' );
	} );

} );
