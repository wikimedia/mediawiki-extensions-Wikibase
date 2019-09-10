import { storiesOf } from '@storybook/vue';
import PropertyLabel from '@/presentation/components/PropertyLabel';

storiesOf( 'PropertyLabel', module )
	.add( 'basic', () => ( {
		data() {
			return {
				term: {
					value: 'taxon name',
					language: 'en',
				},
				htmlFor: 'fake-id',
			};
		},
		components: { PropertyLabel },
		template:
			`<div>
				<PropertyLabel
					:term="term"
					:htmlFor="htmlFor"
				/>
			</div>`,
	} ), { info: true } )

	.add( 'long values', () => ( {
		data() {
			return {
				term: {
					value: 'Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.', // eslint-disable-line max-len
					language: 'en',
				},
				htmlFor: 'fake-id',
			};
		},
		components: { PropertyLabel },
		template:
			`<div>
				<PropertyLabel
					:term="term"
					:htmlFor="htmlFor"
				/>
			</div>`,
	} ), { info: true } )

	.add( 'empty', () => ( {
		data() {
			return {
				term: {
					value: '',
					language: 'en',
				},
				htmlFor: 'fake-id',
			};
		},
		components: { PropertyLabel },
		template:
			`<div>
				<PropertyLabel
					:term="term"
					:htmlFor="htmlFor"
				/>
			</div>`,
	} ), { info: true } )

	.add( 'right-to-left', () => ( {
		data() {
			return {
				term: {
					value: 'שם מדעי',
					language: 'he',
				},
				htmlFor: 'fake-id',
			};
		},
		components: { PropertyLabel },
		template:
			`<div>
				<PropertyLabel
					:term="term"
					:htmlFor="htmlFor"
				/>
			</div>`,
	} ), { info: true } );
