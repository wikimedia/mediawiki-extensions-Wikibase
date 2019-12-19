import Vue from 'vue';
import ReferenceSection from '@/presentation/components/ReferenceSection.vue';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import { createStore } from '@/store';
import Vuex, {
	Store,
} from 'vuex';
import SingleReferenceDisplay from '@/presentation/components/SingleReferenceDisplay.vue';
import Application from '@/store/Application';
import newMockServiceContainer from '../../services/newMockServiceContainer';
import MessageKeys from '@/definitions/MessageKeys';

const localVue = createLocalVue();

localVue.use( Vuex );

describe( 'ReferenceSection', () => {
	let store: Store<Application>;

	beforeEach( () => {
		store = createStore(
			newMockServiceContainer( {
				entityLabelRepository: {},
				wikibaseRepoConfigRepository: {},
				readingEntityRepository: {},
				writingEntityRepository: {},
				propertyDatatypeRepository: {},
				tracker: {},
			} ),
		);
	} );

	it( 'shows the Reference message as the header', () => {
		const referenceHeading = 'here be credibility';
		const get = jest.fn(
			( key: string ) => {
				if ( key === MessageKeys.REFERENCES_HEADING ) {
					return referenceHeading;
				}

				return '';
			},
		);
		Vue.set( store, 'getters', { targetReferences: [] } );

		const wrapper = shallowMount( ReferenceSection, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get,
				},
			},
		} );

		expect( wrapper.text() ).toBe( referenceHeading );
		expect( get ).toHaveBeenCalledWith( MessageKeys.REFERENCES_HEADING );
	} );

	it( 'mounts SingleReferenceDisplay if there are references', () => {
		Vue.set( store, 'getters', {
			targetReferences: [ {
				snaks: {
					P214: [ {
						snaktype: 'value',
						property: 'P214',
						datavalue: {
							value: 'example reference',
							type: 'string',
						},
						datatype: 'external-id',
					} ],
				},
				'snaks-order': [
					'P214',
				],
			} ],
		} );
		const wrapper = shallowMount( ReferenceSection, {
			store,
			localVue,
		} );

		expect( wrapper.find( SingleReferenceDisplay ).exists() ).toBeTruthy();
	} );

	it( 'does not mount SingleReferenceDisplay if there are no references', () => {
		Vue.set( store, 'getters', {
			targetReferences: [],
		} );
		const wrapper = shallowMount( ReferenceSection, {
			store,
			localVue,
		} );

		expect( wrapper.find( SingleReferenceDisplay ).exists() ).toBeFalsy();
	} );

	it( 'delegates the necessary props to SingleReferenceDisplay', () => {
		const snakSeparator = 'TBD';
		const get = jest.fn(
			( key: string ) => {
				if ( key === MessageKeys.REFERENCE_SNAK_SEPARATOR ) {
					return snakSeparator;
				}

				return '';
			},
		);
		const targetReference = {};
		Vue.set( store, 'getters', { targetReferences: [ targetReference ] } );

		const wrapper = shallowMount( ReferenceSection, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get,
				},
			},
		} );

		expect( wrapper.find( SingleReferenceDisplay ).props( 'reference' ) ).toBe( targetReference );
		expect( wrapper.find( SingleReferenceDisplay ).props( 'separator' ) ).toBe( snakSeparator );
		expect( get ).toHaveBeenCalledWith( MessageKeys.REFERENCE_SNAK_SEPARATOR );
	} );
} );
