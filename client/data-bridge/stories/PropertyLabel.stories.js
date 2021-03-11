import PropertyLabel from '@/presentation/components/PropertyLabel';
import loremIpsum from './loremIpsum';

export default {
	title: 'PropertyLabel',
	component: PropertyLabel,
};

export function basic() {
	return {
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
	};
}

export function longValues() {
	return {
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
	};
}

export function empty() {
	return {
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
	};
}

export function rightToLeft() {
	return {
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
	};
}
