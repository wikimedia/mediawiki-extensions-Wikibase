const Wbui2025Stepper = require( '../../../resources/wikibase.wbui2025/components/stepper.vue' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.stepper', () => {
	it( 'defines component', () => {
		expect( typeof Wbui2025Stepper ).toBe( 'object' );
		expect( Wbui2025Stepper ).toHaveProperty( 'name', 'WikibaseWbui2025Stepper' );
	} );

	describe( 'the mounted component', () => {
		let wrapper, stepElements;
		const baseStepClass = 'wikibase-wbui2025-stepper-step';
		const visitedClass = 'wikibase-wbui2025-stepper-step__previous';
		const activeClass = 'wikibase-wbui2025-stepper-step__active';

		beforeEach( async () => {
			wrapper = await mount( Wbui2025Stepper, {
				props: {
					currentStep: 2,
					totalSteps: 4
				}
			} );
			stepElements = wrapper.findAll( `span.${ baseStepClass }` );
		} );

		it( 'displays the correct number of step indicators', () => {
			expect( stepElements ).toHaveLength( 4 );
		} );

		it( 'marks previous steps as visited, current as active', () => {
			expect( stepElements.map( ( span ) => span.attributes( 'class' ) ) ).toEqual( [
				`${ baseStepClass } ${ visitedClass }`,
				`${ baseStepClass } ${ activeClass }`,
				baseStepClass,
				baseStepClass
			] );
		} );
	} );
} );
