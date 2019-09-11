import { storiesOf } from '@storybook/vue';
import StringDataValue from '@/presentation/components/StringDataValue.vue';

storiesOf( 'StringDataValue', module )
	.add( 'basic', () => ( {
		data() {
			return {
				sampleLabel: { value: 'lorem', language: 'la' },
				sampleValue: { type: 'string', value: 'ipsum' },
				sampleSetter: ( value ) => {
					this.sampleValue = { ...value };
				},
			};
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
	} ), { info: true } )

	.add( 'long values', () => ( {
		data() {
			return {
				sampleLabel: {
					value: 'Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.', // eslint-disable-line max-len
					language: 'la',
				},
				sampleValue: {
					type: 'string',
					value: 'Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.', // eslint-disable-line max-len
				},
				sampleSetter: ( value ) => {
					this.sampleValue = { ...value };
				},
			};
		},
		components: { StringDataValue },
		template:
		`<div>
			<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :setDataValue="sampleSetter"/>
		</div>`,
	} ), { info: true } )

	.add( 'empty', () => ( {
		data() {
			return {
				sampleLabel: { value: 'empty', language: 'en' },
				sampleValue: { type: 'string', value: '' },
				sampleSetter: ( value ) => {
					this.sampleValue = { ...value };
				},
			};
		},
		components: { StringDataValue },
		template:
		`<div>
			<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :setDataValue="sampleSetter"/>
		</div>`,
	} ), { info: true } )

	.add( 'empty with placeholder', () => ( {
		data() {
			return {
				sampleLabel: { value: 'empty', language: 'en' },
				sampleValue: { type: 'string', value: '' },
				sampleSetter: ( value ) => {
					this.sampleValue = { ...value };
				},
				placeholder: 'placeholder',
			};
		},
		components: { StringDataValue },
		template:
		`<div>
			<StringDataValue :label="sampleLabel" :dataValue="sampleValue" :placeholder="placeholder" :setDataValue="sampleSetter"/>
		</div>`,
	} ), { info: true } );
