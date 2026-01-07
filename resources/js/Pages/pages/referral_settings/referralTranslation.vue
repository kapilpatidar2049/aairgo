<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import { ref,onMounted, computed } from "vue";
import axios from "axios";
import { useSharedState } from '@/composables/useSharedState'; // Import the composable
import Multiselect from "@vueform/multiselect";
import FormValidation from "@/Components/FormValidation.vue";
import { useI18n } from 'vue-i18n';
import CKEditor from "@ckeditor/ckeditor5-vue";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import Swal from "sweetalert2";

export default {
  components: {
    Layout,
    PageHeader,
    Head,
    Pagination,
    Multiselect,
    FormValidation,
    ckeditor: CKEditor.component,
  },
   data() {
    return {
      editor: ClassicEditor,
      editorData: "",
    };
  },
  props: {
    activeTab: String,
    languages: Array,
    successMessage: String,
    alertMessage: String,
    validate: Function,
    referrals: Array,
    app_for: String,
  },
  setup(props) {
   // console.log("referrals",props.referrals);
    const { t } = useI18n();
    const { languages, fetchData } = useSharedState(); 
    const activeTab = ref('English');

    const userReferrals = computed(() =>
      props.referrals.filter(r => r.label_referral === "user")
    );

    const driverReferrals = computed(() =>
      props.referrals.filter(r => r.label_referral === "driver")
    );

    const form = useForm({ 
      referral_description: props.referrals?.reduce((acc, item) => {
      let desc = item.referral_description;
  
      if (typeof desc === 'string') {
        desc = { en: desc }; 
      } else if (!desc || typeof desc !== 'object') {
        desc = {}; 
      }
      acc[item.referral_type] = desc;
      return acc;
      }, {}) || {}
    });

    const validationRules = {
      description: { required: true },
    };

    const validationRef = ref(null);
    const errors = ref({});
    const successMessage = ref(props.successMessage || '');
    const alertMessage = ref(props.alertMessage || '');

    const dismissMessage = () => {
      successMessage.value = "";
      alertMessage.value = "";
    };

    const handleSubmit = async () => {
      if(props.app_for == "demo"){
        Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
        return;
      }
      try {
        await axios.post('/referral-translation/update', form.data());
        successMessage.value = t('description_updated_successfully');
        alertMessage.value = '';
        setTimeout(() => {
          router.get('/referral-translation'); 
        }, 1500);

      } catch (e) {
        alertMessage.value = t('update_failed');
        successMessage.value = '';
        console.error(e);
      }
    };

    const setActiveTab = (tab) => {
      activeTab.value = tab;
    }
     

  // Store the original brace blocks extracted from DB
  const braceBlocks = ref({});
  const extractBraces = (text) => {
    const matches = text?.match(/\{[^}]*\}/g);
    return matches ? matches.join(" ") : "";
  };

  // Run on mount — extract braces for EACH referral type + language
  onMounted(() => {
    for (const type in form.referral_description) {
      braceBlocks.value[type] = {};

      for (const lang in form.referral_description[type]) {
        const original = form.referral_description[type][lang] || "";
        braceBlocks.value[type][lang] = extractBraces(original);
      }
    }
  });

    // Auto-restore braces when user clears everything
    const onTextInput = (refType, lang) => {
      const current = form.referral_description[refType][lang];

      // If cleared or contains only spaces → restore braces
      if (!current || !current.trim()) {
        form.referral_description[refType][lang] =
          braceBlocks.value[refType][lang] || "";
      }
    };
    
    onMounted(async () => {
      if (!languages.value || !languages.value.length) {
        await fetchData(); 
      }
      fetchUserReferralDatas();
      fetchDriverReferralDatas();
    });
    const results = ref([]);
    const driverResults = ref([])
    const fetchUserReferralDatas = async () => {
      try {
          const response = await axios.get(`/referral-translation/referral-condition`);
          results.value = response.data.data;
          console.log("results.value",results.value);
      } catch (error) {
          console.error(t('error_fetching_onboarding_screen'), error);
      }
    };
    const fetchDriverReferralDatas = async () => {
      try {
          const response = await axios.get(`/referral-translation/driver-referral-condition`);
          driverResults.value = response.data.data;
          console.log("driverResults.value",driverResults.value);
      } catch (error) {
          console.error(t('error_fetching_onboarding_screen'), error);
      }
    };

    return {
      form,
      successMessage,
      alertMessage,
      handleSubmit,
      dismissMessage,
      validationRules,
      setActiveTab,
      activeTab,
      languages,
      validationRef,
      errors,
      userReferrals,
      driverReferrals,
      onTextInput,
      results,
      driverResults
    };
  }
  
};

</script>

<template>
  <Layout>
    <Head title="Referral Translation" />
    <PageHeader :title="$t('referral-translation')" :pageTitle="$t('referral-translation')"  pageLink="/referral-translation"/>
    <BRow>      
      <BCol lg="6">
        <BCard>          
          <BCardHeader class="border-0">
            <h5>{{$t('mobile_view')}} - {{ $t('user_referrals') }}</h5>
          </BCardHeader>     
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <div class="col-sm-12">
              <div class="mb-3" style="display: grid;place-items:center;">                      
                <div class="referralScreen">                 
                  <div class="overlap">                    
                    <div class="card banner-cards rounded-0" style="background-color: #001CAD;">
                      <div class="fs-15  text-white" 
                          v-html="results.user_banner?.data.description">
                      </div>                     
                    </div>
                    <div class="card cards" style="background-color: white;">
                      <div class="fs-10 text-black" 
                          v-html="results.referral_content?.data.description">
                      </div>                     
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </BCardBody>     
        </BCard>
        </BCol>
        <BCol lg="6">
         <BCard style="">          
          <BCardHeader class="border-0">
            <h5>{{$t('mobile_view')}} - {{ $t('driver_referrals') }}</h5>
          </BCardHeader>     
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <div class="col-sm-12">
              <div class="mb-3" style="display: grid;place-items:center;">                      
                <div class="referralScreen">                 
                  <div class="overlap">                    
                    <div class="card banner-cards rounded-0" style="background-color: #001CAD;">
                      <div class="fs-15  text-white" 
                          v-html="driverResults.driver_banner?.data.description">
                      </div>                     
                    </div>
                    <div class="card cards" style="background-color: white;">
                      <div class="fs-10 text-black" 
                          v-html="driverResults.referral_content?.data.description">
                      </div>                     
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </BCardBody>     
        </BCard>
      </BCol>
    </BRow>
    <BRow>
      <BCol lg="12">
        <BCard no-body id="tasksList">
          <BCardHeader class="border-0"></BCardHeader>
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <form @submit.prevent="handleSubmit">
              <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                <div class="row">
                  <ul class="nav nav-tabs nav-tabs-custom nav-success nav-justified" role="tablist">
                    <BRow v-for="language in languages" :key="language.code">
                      <BCol lg="12">
                        <li class="nav-item" role="presentation">
                          <a class="nav-link" @click="setActiveTab(language.label)"
                            :class="{ active: activeTab === language.label }" role="tab" aria-selected="true">
                            {{ language.label }}
                          </a>
                        </li>
                      </BCol>
                    </BRow>
                  </ul>
                  <!-- User Referral -->
                  <div class="col-sm-6">
                    <div class="mt-3">
                      <label for="user_referrals" class="form-label">{{$t("user_referrals")}}
                        <span class="text-danger">*</span>
                      </label>
                    </div>
                  </div>
                  <div v-for="language in languages" :key="language.code" class="tab-content ">
                    <div v-if="activeTab === language.label" class="tab-pane active show">
                      <div >
                        <div class="row">
                          <div v-for="ref in userReferrals" :key="ref.referral_type + '-' + language.code" class="mb-3 col-sm-6">
                            <label class="form-label text-muted">
                              {{ ref.referral_type.replaceAll('_', ' ') }}
                            </label>
                            <ckeditor :disabled="app_for === 'demo'"  :editor="editor"
                              @input="onTextInput(ref.referral_type, language.code)"
                                class="form-control"
                                rows="3"
                                :placeholder="`Enter ${language.label} description`"
                                v-model="form.referral_description[ref.referral_type][language.code]"
                                :id="`referral_description-${language.code}`" :required="language.code === 'en'"
                            ></ckeditor>
                          </div>
                        </div>                        
                      </div>
                    </div>
                  </div>
                  <!-- Driver Referral -->
                  <div class="col-sm-6">
                    <div class="mt-3">
                      <label for="driver_referrals" class="form-label">{{$t("driver_referrals")}}
                        <span class="text-danger">*</span>
                      </label>
                    </div>
                  </div>
                  <div v-for="language in languages" :key="language.code" class="tab-content">
                    <div v-if="activeTab === language.label" class="tab-pane active show">
                      <div class="row">
                        <div v-for="ref in driverReferrals" :key="ref.referral_type + '-' + language.code" class="mb-3 col-sm-6">
                          <label class="form-label text-muted">
                            {{ ref.referral_type.replaceAll('_', ' ') }}
                          </label>
                          <ckeditor :disabled="app_for === 'demo'"  :editor="editor"
                            @input="onTextInput(ref.referral_type, language.code)"
                            class="form-control"
                            rows="3"
                            :placeholder="`Enter ${language.label} description`"
                            v-model="form.referral_description[ref.referral_type][language.code]"
                            :id="`referral_description-${language.code}`" :required="language.code === 'en'"
                          ></ckeditor>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-12">
                    <div class="text-end">
                      <button type="submit" class="btn btn-primary">{{ $t('update')}}</button>
                    </div>
                   </div>
                </div>
              </FormValidation>
            </form>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
    <div>
      <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" role="alert" id="alertMsg">
        <div class="alert-content">
          <i class="ri-notification-off-line me-3 align-middle"></i>
          <strong>Success</strong> - {{ successMessage }}
          <button type="button" class="btn-close btn-close-success" @click="dismissMessage" aria-label="Close Success Message"></button>
        </div>
      </div>
      <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" role="alert" id="alertMsg">
        <div class="alert-content">
          <i class="ri-notification-off-line me-3 align-middle"></i>
          <strong>Alert</strong> - {{ alertMessage }}
          <button type="button" class="btn-close btn-close-danger" @click="dismissMessage" aria-label="Close Alert Message"></button>
        </div>
      </div>
    </div>
</Layout>
</template>

<style>
.custom-alert {
  max-width: 600px;
  float: right;
  position: fixed;
  top: 90px;
  right: 20px;
}
.rtl .custom-alert {
  max-width: 600px;
  float: left;
  top: -300px;
  right: 10px;
}
.referralScreen{
  width: 300px;
  height: 550px;
  background-image: url(/images/referral.png);
  background-position: center;
  background-repeat: no-repeat;
  background-size: contain;
}
.cards{
  position: relative;
  top: 165px;
  left: 14px;
  z-index: 2;
  width: 248px;
  height: 217px;
  padding:10px;
  box-shadow: none;
}
.banner-cards{
  position: relative;
  top: 57px;
  left: 25px;
  z-index: 2;
  width: 195px;
  height: 71px;
  padding: 10px;
  box-shadow: none;
}
@media only screen and (max-width: 1024px) {
  .custom-alert {
  max-width: 600px;
  float: right;
  position: fixed;
  top: 90px;
  right: 20px;
}
.rtl .custom-alert {
  max-width: 600px;
  float: left;
  top: -230px;
  right: 10px;
}
}
</style>
