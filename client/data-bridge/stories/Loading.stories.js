import { storiesOf } from '@storybook/vue';
import { boolean, number } from '@storybook/addon-knobs';
import Loading from '@/presentation/components/Loading';

function getLoadingProps( options = {} ) {
	return {
		isInitializing: {
			default: boolean(
				'Is Initializing',
				options.isInitializing === undefined ? true : options.isInitializing,
			),
		},
		isSaving: {
			default: boolean(
				'Is Saving',
				options.isSaving === undefined ? false : options.isSaving,
			),
		},
		TIME_UNTIL_CONSIDERED_SLOW: {
			default: number(
				'TIME_UNTIL_CONSIDERED_SLOW (ms)',
				options.TIME_UNTIL_CONSIDERED_SLOW || 1000,
			),
		},
		MINIMUM_TIME_OF_PROGRESS_ANIMATION: {
			default: number(
				'MINIMUM_TIME_OF_PROGRESS_ANIMATION (ms)',
				options.MINIMUM_TIME_OF_PROGRESS_ANIMATION || 500,
			),
		},
	};
}

storiesOf( 'Loading', module )
	.addParameters( { component: Loading } )
	.add( 'initializing', () => ( {
		components: { Loading },
		props: getLoadingProps( { isInitializing: true, isSaving: false } ),
		template:
			`<Loading
				:is-initializing="isInitializing"
				:is-saving="isSaving"
				:TIME_UNTIL_CONSIDERED_SLOW="TIME_UNTIL_CONSIDERED_SLOW"
				:MINIMUM_TIME_OF_PROGRESS_ANIMATION="MINIMUM_TIME_OF_PROGRESS_ANIMATION"
			>Content which may be slow</Loading>`,
	} ) )
	.add( 'saving', () => ( {
		components: { Loading },
		props: getLoadingProps( { isInitializing: false, isSaving: true } ),
		template:
			`<Loading
				:is-initializing="isInitializing"
				:is-saving="isSaving"
				:TIME_UNTIL_CONSIDERED_SLOW="TIME_UNTIL_CONSIDERED_SLOW"
				:MINIMUM_TIME_OF_PROGRESS_ANIMATION="MINIMUM_TIME_OF_PROGRESS_ANIMATION"
			>
				<h3>I am under the loading bar</h3>
				<div style="max-width: 50em">
					Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet. Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.-Lorem-ipsum-dolor-sit-amet,-consetetur-sadipscing-elitr,-sed-diam-nonumy-eirmod-tempor-invidunt-ut-labore-et-dolore-magna-aliquyam-erat,-sed-diam-voluptua.-At-vero-eos-et-accusam-et-justo-duo-dolores-et-ea-rebum.-Stet-clita-kasd-gubergren,-no-sea-takimata-sanctus-est-Lorem-ipsum-dolor-sit-amet.
				</div>
			</Loading>`,
	} ) );
