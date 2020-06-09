import { createLocalVue, shallowMount } from '@vue/test-utils';
import { SnakType } from '@wmde/wikibase-datamodel-types';
import ErrorUnsupportedSnakType from '@/presentation/components/ErrorUnsupportedSnakType.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import MessageKeys from '@/definitions/MessageKeys';
import Vuex from 'vuex';
import { calledWithHTMLElement } from '../../../util/assertions';
import { createTestStore } from '../../../util/store';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'ErrorUnsupportedSnakType', () => {
	const targetProperty = 'P569',
		pageTitle = 'Marie_Curie',
		originalHref = 'https://www.wikidata.org/wiki/Q7186',
		messageGet = jest.fn( ( key ) => key ),
		store = createTestStore( {
			state: {
				targetProperty,
				pageTitle,
				originalHref,
			},
		} );

	it( 'uses IconMessageBox to display the error message', () => {
		const wrapper = shallowMount( ErrorUnsupportedSnakType, {
			localVue,
			propsData: {
				snakType: 'somevalue',
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );
		expect( wrapper.find( IconMessageBox ).exists() ).toBe( true );
	} );

	it.each( [
		[ 'somevalue' as SnakType, MessageKeys.SOMEVALUE_ERROR_HEAD ],
		[ 'novalue' as SnakType, MessageKeys.NOVALUE_ERROR_HEAD ],
	] )( 'shows the message header for %s', ( snakType: SnakType, messageKey: MessageKeys ) => {
		shallowMount( ErrorUnsupportedSnakType, {
			localVue,
			propsData: {
				snakType,
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );

		calledWithHTMLElement( messageGet, 0, 1 );

		expect( messageGet ).toHaveBeenNthCalledWith(
			1,
			messageKey,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${targetProperty}</span>`,
		);
	} );

	it.each( [
		[ 'somevalue' as SnakType, MessageKeys.SOMEVALUE_ERROR_BODY ],
		[ 'novalue' as SnakType, MessageKeys.NOVALUE_ERROR_BODY ],
	] )( 'shows the message body for %s', ( snakType: SnakType, messageKey: MessageKeys ) => {
		shallowMount( ErrorUnsupportedSnakType, {
			localVue,
			propsData: {
				snakType,
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );

		calledWithHTMLElement( messageGet, 1, 1 );

		expect( messageGet ).toHaveBeenNthCalledWith(
			2,
			messageKey,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${targetProperty}</span>`,
		);
	} );

	it( 'uses BailoutActions to provide a bail out path for unsupported snak type', () => {
		const wrapper = shallowMount( ErrorUnsupportedSnakType, {
			localVue,
			propsData: {
				snakType: 'somevalue',
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );

		expect( wrapper.find( BailoutActions ).exists() ).toBe( true );
		expect( wrapper.find( BailoutActions ).props() ).toStrictEqual( {
			originalHref,
			pageTitle,
		} );
	} );

} );
