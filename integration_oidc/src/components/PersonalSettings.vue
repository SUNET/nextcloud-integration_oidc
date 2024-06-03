<template>
  <div id="ioidc_prefs" class="section">
    <div id="ioidc-content">
      <div id="oidc-unconfigured">
        <NcSettingsSection name="Integration OIDC" description="Connect these services to your Nextcloud account."
          doc-url="https://github.com/SUNET/nextcloud-integration_oidc" @default="populate">
          <NcButton :disabled=false :readonly="readonly" :wide="true" :nativeType="submit" v-for="i in unconfigured"
            :text="i.name" :id="i.id" @click="(_) => register(i.id)">
            {{ i.name }}
          </NcButton>
      </div>
      <div id="oidc-configured">
        <ul id="oidc-configured-list">
          <NcListItemIcon v-for="i in configured" :name="i.name" :subname="i.token_endpoint">
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
// Icons
import Delete from 'vue-material-design-icons/Delete.vue'

// Nextcloud components
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcListItemIcon from '@nextcloud/vue/dist/Components/NcListItemIcon.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

// Nextcloud API
import axios from '@nextcloud/axios'
import { generateUrl, getBaseUrl } from '@nextcloud/router'

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
  methods: {
    async remove(id) {
      var url = generateUrl('/apps/integration_oidc/remove_user');
      let params = { 'id': id };
      let result = await axios.post(url, params);
      if (result.data.status == 'success') {
        var removed = this.configured.find((a) => a.id == id);
        console.log("removed", removed);
        this.configured = this.configured.filter((a) => a.id !== id);
        this.unconfigured.push(removed);
      }
    },
    async register(provider_id) {
      var provider = this.available.find((a) => a.id == provider_id);
      console.log("configured", this.configured);

      var form = document.createElement('form');
      form.setAttribute('method', 'GET'); // Send as a GET request.
      form.setAttribute('action', provider.auth_endpoint);
      let state = self.crypto.randomUUID();

      var client_config = {
        'client_id': provider.client_id,
        'redirect_uri': getBaseUrl() + '/apps/integration_oidc/callback',
        'response_type': 'token',
        'scope': provider.scope,
        'include_granted_scopes': 'true',
        'state': state
      };

      var url = generateUrl('/apps/integration_oidc/register_state');
      let params = { 'id': provider_id, 'state': state };
      let result = await axios.post(url, params);
      if (result.data.status == 'success') {
        // Add form parameters as hidden input values.
        for (var c in client_config) {
          var input = document.createElement('input');
          input.setAttribute('type', 'hidden');
          input.setAttribute('name', c);
          input.setAttribute('value', client_config[c]);
          form.appendChild(input);
        }
        console.log("form", form);

        document.body.appendChild(form);
        form.submit();
      }
    }
  },
  mounted() {
    var url = generateUrl('/apps/integration_oidc/query');
    axios.get(url).then((result) => {
      this.available = result.data;
      console.log('available', this.available);
    });
    url = generateUrl('/apps/integration_oidc/query_user');
    axios.get(url).then((result) => {
      // The configuired providers for this user
      this.configured = result.data;
      console.log('configured', this.configured);
      // All the available not in configured
      this.unconfigured = this.available.filter((a) => !this.configured.find((c) => c.id == a.id));
      console.log('unconfigured', this.unconfigured);
    });
  },
}
</script>
