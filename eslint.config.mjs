/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
*/

import { recommended } from '@nextcloud/eslint-config'
import { defineConfig } from 'eslint/config'

export default defineConfig([
  ...recommended,
  {
    rules: {
      // Relax some rules for now. Can be improved later on (baseline).
      'no-console': 'off',
      'vue/multi-word-component-names': 'off',
      // JSDocs are welcome but lint:fix should not create empty ones
      'jsdoc/require-jsdoc': 'off',
      'jsdoc/require-param': 'off',
      '@stylistic/indent': [2, 2],
      // The template uses tab indentation while the script uses 2 spaces;
      // indent-binary-ops otherwise fights itself on multi-line expressions.
      '@stylistic/indent-binary-ops': 'off',
      // Baseline: existing OIDC code uses snake_case identifiers that mirror
      // the backend fields and loose equality. Relaxed for now rather than
      // risk behaviour changes; tighten incrementally.
      camelcase: 'off',
      eqeqeq: 'off',
      'no-useless-assignment': 'off',
    },
  },
])
