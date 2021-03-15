import StringDataValue from '@/presentation/components/StringDataValue.vue';
import loremIpsum from './loremIpsum';

export default {
	title: 'StringDataValue',
	component: StringDataValue,
};

export function basic() {
	return {
		data: () => ( {
			sampleLabel: { value: 'lorem', language: 'la' },
			sampleValue: { type: 'string', value: 'ipsum' },
		} ),
		methods: {
			sampleSetter( value ) {
				this.sampleValue = { ...value };
			},
		},
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue
					:label="sampleLabel"
					:dataValue="sampleValue"
					:setDataValue="sampleSetter"
				/>
			</div>`,
	};
}

export function longValues() {
	return {
		data: () => ( {
			sampleLabel: {
				value: loremIpsum( 3, '-' ),
				language: 'la',
			},
			sampleValue: {
				type: 'string',
				value: loremIpsum( 3, '-' ),
			},
		} ),
		methods: {
			sampleSetter( value ) {
				this.sampleValue = { ...value };
			},
		},
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :setDataValue="sampleSetter"/>
			</div>`,
	};
}

export function empty() {
	return {
		data: () => ( {
			sampleLabel: { value: 'empty', language: 'en' },
			sampleValue: { type: 'string', value: '' },
		} ),
		methods: {
			sampleSetter( value ) {
				this.sampleValue = { ...value };
			},
		},
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :setDataValue="sampleSetter"/>
			</div>`,
	};
}

export function emptyWithPlaceholder() {
	return {
		data: () => ( {
			sampleLabel: { value: 'empty', language: 'en' },
			sampleValue: { type: 'string', value: '' },
			placeholder: 'placeholder',
		} ),
		methods: {
			sampleSetter( value ) {
				this.sampleValue = { ...value };
			},
		},
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :placeholder="placeholder" :setDataValue="sampleSetter"/>
			</div>`,
	};
}

export function maxlength15() {
	return {
		data: () => ( {
			sampleLabel: { value: 'maxlength=15', language: 'en' },
			sampleValue: { type: 'string', value: 'lorem ipsum' },
		} ),
		methods: {
			sampleSetter( value ) {
				this.sampleValue = { ...value };
			},
		},
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :setDataValue="sampleSetter" :maxlength="15"/>
			</div>`,
	};
}
