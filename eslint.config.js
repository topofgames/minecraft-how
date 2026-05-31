import reactHooks from 'eslint-plugin-react-hooks';

export default [
  {
    ignores: ['public/build/**', 'vendor/**', 'node_modules/**'],
  },
  {
    files: ['resources/js/**/*.{ts,tsx}'],
    plugins: {
      'react-hooks': reactHooks,
    },
    rules: {
      ...reactHooks.configs.recommended.rules,
    },
  },
];
