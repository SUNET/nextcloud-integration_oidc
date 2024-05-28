<template>
  <div id="ioidc_prefs" class="section">
    <div id="ioidc-content">
      <NcSettingsSection name="Integration OIDC" description="Generic OIDC integration engine."
        doc-url="https://github.com/SUNET/nextcloud-integration_oidc">
        <p>{{ t('ioidc_prefs_desc', 'Add new integrations here') }}</p>
        <div class="external-label">
          <label for="Name">Name</label>
          <NcTextField id="Name" :value.sync="name" :label-outside="true" placeholder="Name" />
        </div>
        <div class="external-label">
          <label for="ClientID">Client ID</label>
          <NcPasswordField id="ClientID" :value.sync="client_id" :label-outside="true" placeholder="Client ID" />
        </div>
        <div class="external-label">
          <label for="ClientSecret">Client Secret</label>
          <NcPasswordField id="ClientSecret" :value.sync="client_secret" :label-outside="true"
            placeholder="Client Secret" />
        </div>
        <NcButton :disabled="disabled" :readonly="readonly" :wide="true" text="Save">
          Save
        </NcButton>
      </NcSettingsSection>
    </div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

export default {
  name: 'AdminSettings',

  components: {
    NcButton,
    NcPasswordField,
    NcSettingsSection,
    NcTextField,
  },

  props: [],
  data() {
    return {
      name: '',
      client_id: '',
      client_secret: '',
    }
  },
  //   computed: {
  //     configured() {
  //       return !!this.state.client_id || !!this.state.client_secret
  //     },
  //     watch: {
  //     },

  //     mounted() {
  //       // if (this.configured) {
  //       // 	this.getModels()
  //       // }
  //       // this.loadQuotaInfo()
  //     },
  //     methods: {
  //       getModels() { },
  //     },
  //   }
}
</script>
