<template>
  <div id="ioidc_prefs" class="section">
    <h2>
      <IOIDCIcon class="icon" />
      {{ t('integration_ioidc', 'Integration OIDC') }}
    </h2>
    <div id="openai-content">
      <p>{{ t('ioidc_prefs_desc', 'This is the description of the integration') }}</p>
    </div>
  </div>
</template>

<script>
import IOIDCIcon from './icons/IOIDCIcon.vue'

export default {
  name: 'AdminSettings',

  components: {
    IOIDCIcon,
  },

  props: [],
  data() {
    return {
      state: loadState('integration_ioidc', 'admin-config'),
      // to prevent some browsers to fill fields with remembered passwords
      clientId: "",
      clientSecret: "",
    }
  },
  computed: {
    configured() {
      return !!this.state.client_id || !!this.state.client_secret
    },
    watch: {
    },

    mounted() {
      // if (this.configured) {
      // 	this.getModels()
      // }
      // this.loadQuotaInfo()
    },
    methods: {
      getModels() { },
    },
  }
</script>
