<template>
	<div id="ioidc_prefs" class="section">
		<div id="ioidc-content">
			<NcSettingsSection
				name="Integration OIDC"
				description="Connect these services to your Nextcloud account."
				docUrl="https://github.com/SUNET/nextcloud-integration_oidc"
				@default="populate">
				<div id="oidc-unconfigured">
					<NcButton
						v-for="i in unconfigured"
						:id="i.id"
						:key="i.id"
						:disabled="!i.issuer"
						:readonly="readonly"
						:title="i.issuer ? undefined : 'Provider is missing an issuer'"
						:wide="true"
						nativeType="submit"
						:text="i.name"
						@click="(_) => register(i.id)">
						{{ i.name }}
					</NcButton>
				</div>
				<div id="oidc-configured">
					<ul id="oidc-configured-list">
						<NcListItemIcon
							v-for="i in configured"
							:key="i.id"
							:name="i.name"
							:subname="i.token_endpoint">
							<NcActions>
								<NcActionButton @click="(_) => remove(i.id)">
									<template #icon>
										<Delete :size="20" />
									</template>
									Delete
								</NcActionButton>
							</NcActions>
						</NcListItemIcon>
					</ul>
				</div>
			</NcSettingsSection>
		</div>
	</div>
</template>

<script>
// Nextcloud API
import axios from '@nextcloud/axios'
import { generateUrl, getBaseUrl } from '@nextcloud/router'
// Nextcloud components
import {
  NcActionButton,
  NcActions,
  NcButton,
  NcListItemIcon,
  NcSettingsSection,
} from '@nextcloud/vue'
// Icons
import Delete from 'vue-material-design-icons/Delete.vue'

export default {
  name: 'PersonalSettings',

  components: {
    Delete,
    NcActionButton,
    NcActions,
    NcButton,
    NcListItemIcon,
    NcSettingsSection,
  },

  props: [],
  data() {
    return {
      available: [],
      configured: [],
      unconfigured: [],
    }
  },

  mounted() {
    this.private_load().then(() => {})
  },

  methods: {
    // Entry point shared by mounted() and the section's @default handler.
    async populate() {
      await this.private_load()
    },

    async private_load() {
      let url = generateUrl('/apps/integration_oidc/query')
      let result = await axios.get(url)
      this.available = result.data
      url = generateUrl('/apps/integration_oidc/query_user')
      result = await axios.get(url)
      // The configured providers for this user
      this.configured = result.data
      const activeProviderIds = this.configured
        .filter((connection) => !connection.requires_reauthorization)
        .map((connection) => connection.provider_id)
      this.unconfigured = this.available.filter((provider) => !activeProviderIds.includes(provider.id))
    },

    async remove(id) {
      const url = generateUrl('/apps/integration_oidc/remove_user')
      try {
        const result = await axios.post(url, { id })
        if (result.data.status == 'success') {
          await this.private_load()
        }
      } catch (error) {
        const response = error.response?.data
        if (!response?.canForce) {
          throw error
        }

        const message = 'The provider could not confirm remote token revocation. Remove the local connection anyway?'
        const confirmed = window.confirm(message)
        if (confirmed) {
          const result = await axios.post(url, { id, force: true })
          if (result.data.status == 'success') {
            await this.private_load()
          }
        }
      }
    },

    async register(provider_id) {
      const provider = this.available.find((a) => a.id == provider_id)
      if (!provider?.issuer) {
        return
      }

      const url = generateUrl('/apps/integration_oidc/register_state')
      const result = await axios.post(url, { providerId: provider_id })
      if (result.data.status != 'success' || !result.data.state || !result.data.nonce) {
        return
      }

      const client_config = {
        access_type: provider.accessType,
        client_id: provider.clientId,
        include_granted_scopes:
          provider.includeGrantedScopes?.toLowerCase?.() === 'true',

        nonce: result.data.nonce,
        prompt: provider.prompt,
        redirect_uri:
          getBaseUrl() + '/index.php/apps/integration_oidc/callback',

        response_type: 'code',
        scope: provider.scope,
        state: result.data.state,
      }
      const form = document.createElement('form')
      form.setAttribute('method', 'GET') // Send as a GET request.
      form.setAttribute('action', provider.authEndpoint)

      // Add form parameters as hidden input values.
      for (const c in client_config) {
        const input = document.createElement('input')
        input.setAttribute('type', 'hidden')
        input.setAttribute('name', c)
        input.setAttribute('value', client_config[c])
        form.appendChild(input)
      }

      document.body.appendChild(form)
      form.submit()
    },
  },
}
</script>
