import { storiesOf } from '@storybook/vue';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import loremIpsum from './loremIpsum';

storiesOf( 'StringDataValue', module )
	.addParameters( { component: StringDataValue } )
	.add( 'basic', () => ( {
		data: () => ( {
			sampleLabel: { value: 'lorem', language: 'la' },
			sampleValue: { type: 'string', value: 'ipsum' },
			sampleSetter: ( value ) => {
				this.sampleValue = { ...value };
			},
		} ),
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue
					:label="sampleLabel"
					:dataValue="sampleValue"
					:setDataValue="sampleSetter"
				/>
			</div>`,
	} ) )

	.add( 'long values', () => ( {
		data: () => ( {
			sampleLabel: {
				value: loremIpsum( 3, '-' ),
				language: 'la',
			},
			sampleValue: {
				type: 'string',
				value: loremIpsum( 3, '-' ),
			},
			sampleSetter: ( value ) => {
				this.sampleValue = { ...value };
			},
		} ),
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :setDataValue="sampleSetter"/>
			</div>`,
	} ) )

	.add( 'empty', () => ( {
		data: () => ( {
			sampleLabel: { value: 'empty', language: 'en' },
			sampleValue: { type: 'string', value: '' },
			sampleSetter: ( value ) => {
				this.sampleValue = { ...value };
			},
		} ),
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :setDataValue="sampleSetter"/>
			</div>`,
	} ) )

	.add( 'empty with placeholder', () => ( {
		data: () => ( {
			sampleLabel: { value: 'empty', language: 'en' },
			sampleValue: { type: 'string', value: '' },
			sampleSetter: ( value ) => {
				this.sampleValue = { ...value };
			},
			placeholder: 'placeholder',
		} ),
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :placeholder="placeholder" :setDataValue="sampleSetter"/>
			</div>`,
	} ) )

	.add( 'maxlength=15', () => ( {
		data: () => ( {
			sampleLabel: { value: 'maxlength=15', language: 'en' },
			sampleValue: { type: 'string', value: 'lorem ipsum' },
			sampleSetter: ( value ) => {
				this.sampleValue = { ...value };
			},
		} ),
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :setDataValue="sampleSetter" :maxlength="15"/>
			</div>`,
	} ) );
