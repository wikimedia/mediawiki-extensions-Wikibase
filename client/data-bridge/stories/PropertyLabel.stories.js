import { storiesOf } from '@storybook/vue';
import PropertyLabel from '@/presentation/components/PropertyLabel';
import loremIpsum from './loremIpsum';

storiesOf( 'PropertyLabel', module )
	.addParameters( { component: PropertyLabel } )
	.add( 'basic', () => ( {
		data: () => ( {
			term: {
				value: 'taxon name',
				language: 'en',
			},
			htmlFor: 'fake-id',
		} ),
		components: { PropertyLabel },
		template:
			`<div>
				<PropertyLabel
					:term="term"
					:htmlFor="htmlFor"
				/>
			</div>`,
	} ) )

	.add( 'long values', () => ( {
		data: () => ( {
			term: {
				value: loremIpsum( 3, '-' ),
				language: 'en',
			},
			htmlFor: 'fake-id',
		} ),
		components: { PropertyLabel },
		template:
			`<div>
				<PropertyLabel
					:term="term"
					:htmlFor="htmlFor"
				/>
			</div>`,
	} ) )

	.add( 'empty', () => ( {
		data: () => ( {
			term: {
				value: '',
				language: 'en',
			},
			htmlFor: 'fake-id',
		} ),
		components: { PropertyLabel },
		template:
			`<div>
				<PropertyLabel
					:term="term"
					:htmlFor="htmlFor"
				/>
			</div>`,
	} ) )

	.add( 'right-to-left', () => ( {
		data: () => ( {
			term: {
				value: 'שם מדעי',
				language: 'he',
			},
			htmlFor: 'fake-id',
		} ),
		components: { PropertyLabel },
		template:
			`<div>
				<PropertyLabel
					:term="term"
					:htmlFor="htmlFor"
				/>
			</div>`,
	} ) );
