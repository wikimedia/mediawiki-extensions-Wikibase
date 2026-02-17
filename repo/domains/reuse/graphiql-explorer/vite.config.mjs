import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import $monacoEditorPlugin from 'vite-plugin-monaco-editor';
import path from 'path';

const monacoEditorPlugin = $monacoEditorPlugin.default ?? $monacoEditorPlugin;

const outDir = path.resolve( __dirname, '../../../../docs/graphql-explorer' );
const workerAssetsDir = 'assets';
export default defineConfig( {
	base: './', // use relative import paths so that it works when running in a sub dir
	build: {
		outDir,
		emptyOutDir: true
	},
	plugins: [
		react(),
		monacoEditorPlugin( {
			customDistPath: () => outDir + `/${ workerAssetsDir }`,
			publicPath: workerAssetsDir,
			languageWorkers: [ 'editorWorkerService', 'json' ],
			customWorkers: [
				{
					label: 'graphql',
					entry: 'monaco-graphql/esm/graphql.worker.js',
				},
			],
		} ),
	],
} );
