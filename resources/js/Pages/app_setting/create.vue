<template>
  <Layout>
    <Head :title="$t('app_modules')" />
    <PageHeader :title="setting ? $t('edit') : $t('create')" :pageTitle="$t('app_modules')" pageLink="/app_modules" />
    <BRow>
        <BCol lg="12">
          <BCard no-body id="tasksList">
            <BCardHeader class="border-0"></BCardHeader>
            <BCardBody class="border border-dashed border-end-0 border-start-0">
              <form @submit.prevent="handleSubmit" enctype="multipart/form-data">
                <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                  <div class="row">
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="name" class="form-label">{{$t("name")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" placeholder="Enter Name" id="name" v-model="form.name" required />
                        <span v-for="(error, index) in errors.name" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="service_type" class="form-label">{{$t("service_type")}}
                          <span class="text-danger">*</span>
                        </label>
                        <select id="service_type" class="form-select" v-model="form.service_type">
                          <option disabled value="">{{$t('choose_service_type')}}</option>
                          <option value="normal">{{$t("normal")}}</option>
                          <option value="rental">{{$t("rental")}}</option>
                          <option value="outstation">{{$t("outstation")}}</option>
                        </select>
                        <span v-for="(error, index) in errors.service_type" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="transport_type" class="form-label">{{$t("transport_type")}}
                          <span class="text-danger">*</span>
                        </label>
                        <select id="transport_type" class="form-select" v-model="form.transport_type">
                          <option disabled value="">{{$t("choose_transport_type")}}</option>
                          <option value="taxi">{{$t("taxi")}}</option>
                          <option value="delivery">{{$t("delivery")}}</option>
                        </select>
                        <span v-for="(error, index) in errors.transport_type" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="order_by" class="form-label">{{$t("order_by")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="any" class="form-control":placeholder="$t('enter_order_by')" id="order_by" v-model.number="form.order_by">
                        <span v-for="(error, index) in errors.order_by" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="short_description" class="form-label">{{$t("short_description")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" :placeholder="$t('enter_short_description')" id="short_description"
                          v-model="form.short_description" />
                        <span v-for="(error, index) in errors.short_description" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="description" class="form-label">{{$t("description")}}
                          <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="description" rows="3" :placeholder="$t('enter_description')" v-model="form.description"></textarea>
                        <span v-for="(error, index) in errors.description" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="mb-3">
                            <label for="mobile_menu_icon" class="form-label d-flex">{{$t("mobile_menu_icon")}} 
                              <span><h5 class="text-muted mt-1 mb-0 fs-13">(512px x 512px)</h5></span>
                              <span class="text-danger">*</span>
                            </label>
                            <ImageUpload  :imageType="'types'" :initialImageUrl="form.mobile_menu_icon"   :flexStyle="'0 0 calc(50% - 20px)'" :aspectRatio="'5 / 5'"   
                              @image-selected="(file) => handleImageSelected(file, 'mobile_menu_icon')" @image-removed="() => handleImageRemoved('mobile_menu_icon')"   @change="onFileChange">
                            </ImageUpload>
                            <span v-for="(error, index) in errors.mobile_menu_icon" :key="index" class="text-danger">
                              {{ error }}
                            </span>    
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-12">
                    <div class="text-end">
                      <button type="submit" class="btn btn-primary"> {{ setting ?  $t('update') : $t('save') }}</button>
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

<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import '@vueform/multiselect/themes/default.css';
import FormValidation from "@/Components/FormValidation.vue";
import { useSharedState } from '@/composables/useSharedState'; // Import the composable
import { useI18n } from 'vue-i18n';
import ImageUpload from '@/Components/ImageUpload.vue';

export default {
  components: {
    Layout,
    PageHeader,
    Head,
    Pagination,
    Multiselect,
    FormValidation,
    ImageUpload
  },
  props: {
    successMessage: String,
    alertMessage: String,
    setting: Object,
    validate: Function,
    languages: Array,
    settings: Array,

  },
  setup(props) {
    const { t } = useI18n();
    const { languages, fetchData } = useSharedState(); // Destructure the shared state
    const activeTab = ref('English');
    const setActiveTab = (tab) => {
      activeTab.value = tab;
    }

    const form = useForm({
      name: props.setting ? props.setting.name || "" : "",
      transport_type: props.setting ? props.setting.transport_type || "" : "",
      short_description: props.setting ? props.setting.short_description || "" : "",
      description: props.setting ? props.setting.description || "" : "",
      service_type: props.setting ? props.setting.service_type || "" : "",
      mobile_menu_icon: props.setting ? props.setting.mobile_menu_icon || "" : "",
      // mobile_menu_cover_image: props.setting ? props.setting.mobile_menu_cover_image || "" : "",
      order_by: props.setting ? props.setting.order_by || "" : "",

    });
    
    const validationRules = {
      name: { required: true },
      transport_type: { required: true },
      description: { required: true },
      short_description: { required: true },
      service_type: { required: true },
      order_by: { required: true },
      mobile_menu_icon: { required: props.setting ? false : true },
      // mobile_menu_cover_image: { required: props.setting ? false : true }
    };

const categories = ref([]); // Holds fetched category data

    const validationRef = ref(null);
    const errors = ref({});
    const successMessage = ref(props.successMessage || '');
    const alertMessage = ref(props.alertMessage || '');

    const dismissMessage = () => {
      successMessage.value = "";
      alertMessage.value = "";
    };
    const handleSubmit = async () => {
      errors.value = validationRef.value.validate();
        if (Object.keys(errors.value).length > 0) {
          console.log(errors.value);
        return;
      }
        try {
        var formData = form.data();
        let response;
        if (props.setting && props.setting.id) {
          response = await axios.post(`/app_modules/update/${props.setting.id}`, formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          });

        } else {
          response = await axios.post('/app_modules/store', formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          });
        }

        if (response.status === 201) {
          successMessage.value = t('app_modules_created_successfully');
          form.reset();
          router.get('/app_modules');
        } else {
          alertMessage.value = t('failed_to_create_app_modules');
        }
      } catch (error) {
        if (error.response && error.response.status === 403) {
          alertMessage.value = error.response.data.alertMessage;
          setTimeout(()=>{
            router.get('/app_modules');
          },5000);
        } else {
          console.error(t('error_creating_app_modules'), error);
        }
      }
    };



    onMounted(async () => {
      if (Object.keys(languages).length == 0) {
        await fetchData();
      }
    });

      // Construct the full URL for the vehicle type mobile_menu_icon
      const handleImageSelected = (file, fieldName) => {
          form[fieldName] = file;
        };

        const handleImageRemoved = (fieldName) => {
          form[fieldName] = null;
        };

    return {
      form,
      successMessage,
      alertMessage,
      handleSubmit,
      dismissMessage,
      validationRules,
      validationRef,
      errors,
      setting: props.setting,
      setActiveTab,
      activeTab,
      categories,
      languages,
      handleImageSelected,
      handleImageRemoved,
    };
  },
};
</script>

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

.rtl .form-check {
    position: relative;
    text-align: right;
}
/* Container for the card with relative positioning */
.position-relative{
  position: relative;
}

/* First image styling */
.map-view-image {
  width: 45%;
  /* width: 500px; */
  height: auto;
  display: block;
  /* Makes the first image responsive */
}

/* Overlay container for the second image */
.overlay-icon-container {
  position: absolute;
  top: 50%;
  left: 25%;
  transform: translate(-50%, -50%);
  width: 60px; /* Adjust size of overlay image */
  height: 60px; /* Adjust size of overlay image */
  display: flex;
  justify-content: center;
  align-items: center;
  pointer-events: none; /* Ensures the image won't interfere with clicks */
}

/* Second image styling (icon) */
.icon-image {
  max-width: 80%;
  height: auto;
  object-fit: cover; /* Ensures the image retains its aspect ratio */
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .overlay-icon-container {
    width: 75px; /* Adjust size for smaller screens */
    height: 75px;
  }
}

@media (max-width: 576px) {
  .overlay-icon-container {
    width: 50px; /* Further adjust size for mobile */
    height: 50px;
  }
}
</style>
