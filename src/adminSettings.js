/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Micke Nordin <kano@sunet.se>
 * @copyright Micke Nordin <kano@sunet.se> 2025
 */

import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

// Mixa in globala metoder (om du fortfarande använder t/n på detta sätt)
const app = createApp(AdminSettings)
app.mixin({ methods: { t, n } })

app.mount('#ioidc_prefs')
