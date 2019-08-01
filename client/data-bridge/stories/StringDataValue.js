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
	} ) )

	.add( 'readonly long values', () => ( {
		data() {
			return {
				sampleLabel: 'Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.', // eslint-disable-line max-len
				sampleValue: {
					type: 'string',
					value: 'Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.', // eslint-disable-line max-len
				},
			};
		},
		components: { StringDataValue },
		template:
		`<div>
			<StringDataValue :label="sampleLabel" :dataValue="sampleValue"/>
		</div>`,
	} ) )

	.add( 'readonly empty', () => ( {
		data() { return { sampleLabel: 'empty', sampleValue: { type: 'string', value: '' } }; },
		components: { StringDataValue },
		template:
		`<div>
			<StringDataValue :label="sampleLabel" :dataValue="sampleValue"/>
		</div>`,
	} ) )

	.add( 'readonly empty placeholder', () => ( {
		data() {
			return {
				sampleLabel: 'empty',
				sampleValue: { type: 'string', value: '' },
				placeholder: 'placeholder',
			};
		},
		components: { StringDataValue },
		template:
		`<div>
			<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :placeholder="placeholder"/>
		</div>`,
	} ) );
