import { storiesOf } from '@storybook/vue';
import StringDataValue from '@/presentation/components/StringDataValue.vue';

storiesOf( 'StringDataValue', module )
	.add( 'readonly', () => ( {
		data() { return { sampleLabel: 'lorem', sampleValue: { type: 'string', value: 'ipsum' } }; },
		components: { StringDataValue },
		template:
			`<div>
				<StringDataValue :label="sampleLabel" :dataValue="sampleValue"/>
			</div>`,
	} ) );
