import { createLocalVue, shallowMount } from '@vue/test-utils';
import App from '@/presentation/App.vue';
import DataPlaceholder from '@/presentation/components/DataPlaceholder.vue';

const localVue = createLocalVue();

describe( 'App.vue', () => {
	let propsData: any;

	beforeEach( () => {
		propsData = {
			entityId: '',
			editFlow: '',
			propertyId: '',
		};
	} );

	it( 'renders the mountable root element', () => {
		const wrapper = shallowMount( App, {
			propsData,
			localVue,
		} );

		expect( wrapper.classes() ).toContain( 'wb-db-app' );
	} );

	it( 'mount Placeholder', () => {
		const wrapper = shallowMount( App, {
			propsData,
			localVue,
		} );
		expect( wrapper.find( DataPlaceholder ).exists() ).toBeTruthy();
	} );

	describe( 'property delegation', () => {
		it( 'delegates entityId to the Placeholder', () => {
			const entityId = 'Q123';
			propsData.entityId = entityId;

			const wrapper = shallowMount( App, {
				propsData,
				localVue,
			} );

			expect(
				wrapper.find( DataPlaceholder ).props( 'entityId' ),
			).toBe( entityId );
		} );

		it( 'delegates editFlow to the Placeholder', () => {
			const editFlow = 'Heraklid ';
			propsData.editFlow = editFlow;

			const wrapper = shallowMount( App, {
				propsData,
				localVue,
			} );

			expect(
				wrapper.find( DataPlaceholder ).props( 'editFlow' ),
			).toBe( editFlow );
		} );

		it( 'delegates editFlow to the Placeholder', () => {
			const propertyId = 'P42';
			propsData.propertyId = propertyId;

			const wrapper = shallowMount( App, {
				propsData,
				localVue,
			} );

			expect(
				wrapper.find( DataPlaceholder ).props( 'propertyId' ),
			).toBe( propertyId );
		} );
	} );
} );
