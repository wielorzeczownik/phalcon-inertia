import eslintConfigPrettier from 'eslint-config-prettier';
import simpleImportSort from 'eslint-plugin-simple-import-sort';
import sonarjs from 'eslint-plugin-sonarjs';
import unicorn from 'eslint-plugin-unicorn';
import tseslint from 'typescript-eslint';

export default [
  {
    ignores: ['node_modules/', 'dist/'],
  },
  ...tseslint.configs.recommendedTypeChecked,
  unicorn.configs.recommended,
  sonarjs.configs?.recommended,
  {
    files: ['**/*.ts', '**/*.js', '**/*.mjs'],
    languageOptions: {
      parserOptions: {
        projectService: true,
        tsconfigRootDir: import.meta.dirname,
      },
    },
    plugins: {
      'simple-import-sort': simpleImportSort,
    },
    rules: {
      'simple-import-sort/imports': 'error',
      'simple-import-sort/exports': 'error',
      '@typescript-eslint/no-unused-vars': [
        'error',
        { argsIgnorePattern: '^_', varsIgnorePattern: '^_' },
      ],
      '@typescript-eslint/consistent-type-imports': [
        'error',
        { disallowTypeAnnotations: false },
      ],
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/no-shadow': 'error',
      '@typescript-eslint/prefer-nullish-coalescing': [
        'error',
        { ignorePrimitives: { boolean: true } },
      ],
      '@typescript-eslint/prefer-optional-chain': 'error',
      eqeqeq: 'error',
      'no-void': ['error', { allowAsStatement: true }],
      'no-console': 'warn',
      'no-var': 'error',
      'prefer-const': 'error',
      'id-length': [
        'error',
        { min: 3, properties: 'never', exceptions: ['id'] },
      ],
      'sonarjs/no-unused-vars': 'off',
      'sonarjs/void-use': 'off',
      'unicorn/prefer-at': 'off',
      'unicorn/prefer-query-selector': 'off',
      'unicorn/no-array-sort': 'off',
      'unicorn/number-literal-case': 'off',
      'unicorn/prevent-abbreviations': 'error',
      // `params` is public Ziggy API (route().params), so it keeps its name.
      'unicorn/name-replacements': [
        'error',
        { replacements: { params: false } },
      ],
    },
  },
  eslintConfigPrettier,
];
