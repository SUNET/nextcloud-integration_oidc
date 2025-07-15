/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Micke Nordin <kano@sunet.se>
 * @copyright Micke Nordin <kano@sunet.se> 2025
 */

import { createApp } from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'

const app = createApp(PersonalSettings)
app.mixin({ methods: { t, n } })
app.mount('#ioidc_prefs')
