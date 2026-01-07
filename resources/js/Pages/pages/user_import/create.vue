<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import { ref } from "vue";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import FormValidation from "@/Components/FormValidation.vue";
import { useI18n } from 'vue-i18n';
import ImageUpload from '@/Components/ImageUpload.vue';
import Swal from "sweetalert2";


export default {
  components: {
    Layout,
    PageHeader,
    Head,
    Pagination,
    Multiselect,
    FormValidation,
    ImageUpload,
  },
  props: {
    successMessage: String,
    alertMessage: String,
    UserImported :{
      type: Object,
      required: true,
    },
    validate: Function, // Define the prop to receive the method
  },
  setup(props) {
    const { t } = useI18n();
    const form = useForm({
      user_file: null,
    });
    const validationRules = {
      user_file: { required: true },
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
      errors.value = validationRef.value.validate();
      console.log("errors.value",errors.value);
      if (Object.keys(errors.value).length > 0) {
        return;
      }try{        
        console.log("form.user_file",form.user_file);
          const formData = new FormData();
        formData.append("file", form.user_file);

        let response;
        console.log("UserImported",props.UserImported);
        if (props.UserImported) {
          response = await axios.post(`/user-import/reupload-invalid-file-store/${props.UserImported.id}`, formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          });
        } else {
          response = await axios.post(`/user-import/import-file`, formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          });
        }      
        console.log("response",response);
        if (response.status === 201) {
           Swal.fire({
             title: 'Success!',
             text: t('files_imported_successfully'),
             icon: 'success',
           }).then(async (result) => {
                if (result.isConfirmed) {
                  form.reset();
                  router.get('/user-import');
                }
            });
          // successMessage.value = t('user_imported_files_created_successfully');
          // form.reset();
          // router.get('/user-import');
        } else {
           Swal.fire({
             title: 'Error!',
             text: t('failed_to_create_user_imported_files.'),
             icon: t('error'),
           })
          // alertMessage.value = t('failed_to_create_user_imported_files');
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          errors.value = error.response.data.errors;
        } else if (error.response && error.response.status === 403) {
          alertMessage.value = error.response.data.alertMessage;
          setTimeout(()=>{
            router.get('/user-import');
          },5000)
        } else {
          console.error(t('error_creating_user_imported_files'), error);
          alertMessage.value = t('failed_to_create_user_imported_files_catch');
        }
      }

    };

    const handleImageSelected = (file, fieldName) => {
      form[fieldName] = file;
    };

    const handleImageRemoved = (fieldName) => {
      form[fieldName] = null;
    };
  const userFileError = ref(false);
      const handleFileUpload = async(e) => {
        if(props.app_for == "demo"){
            Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
            return;
        }
        const file = e.target.files[0];
        const allowedTypes = [
          "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", // .xlsx
          "application/vnd.ms-excel", // .xls
          "text/csv" // .csv
        ];
        
        // Validate file type
        if (file && !allowedTypes.includes(file.type)) {
            userFileError.value = true;
            form.user_file = null; // Clear the file input
            return;
        }
        form.user_file = file;        
      };

  
    const iconUrl = props.bannerimage ? props.bannerimage.image :null;

    return {
      form,
      successMessage,
      alertMessage,
      handleSubmit,
      dismissMessage,
      selectedCountry: ref(null),
      selectedTimezone: ref(null),
      validationRules,
      validationRef,
      errors,
      handleImageSelected,
      handleImageRemoved,
      iconUrl,
      handleFileUpload,
      userFileError
    };
  }
};
</script>

<template>
  <Layout>

    <Head title="Banner Image" />
    <PageHeader :title="bannerimage ? $t('edit') : $t('create')" :pageTitle="$t('user-import')" pageLink="/banner-image"/>
    <BRow>
      <BCol lg="12">
        <BCard no-body id="tasksList">
          <BCardHeader class="border-0"></BCardHeader>
          <BCardBody class="border border-dashed border-end-0 border-start-0">

            <form @submit.prevent="handleSubmit">
              <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                <div class="row">
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="firebase_json" class="form-label">{{$t("User Files")}}
                          <span class="text-danger">*</span>
                      </label>
                      <input :disabled="app_for === 'demo'" type="file" class="form-control" @change="handleFileUpload" accept=".xlsx,.xls,.csv" />
                    </div>
                  </div>                           
                  <div class="col-lg-12">
                    <div class="text-end">
                      <button type="submit" class="btn btn-primary"> {{ UserImported ? $t('update') : $t('save') }}</button>
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
      <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" role="alert"
        id="alertMsg">
        <div class="alert-content">
          <i class="ri-notification-off-line me-3 align-middle"></i>
          <strong>Success</strong> - {{ successMessage }}
          <button type="button" class="btn-close btn-close-success" @click="dismissMessage"
            aria-label="Close Success Message"></button>
        </div>
      </div>

      <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" role="alert"
        id="alertMsg">
        <div class="alert-content">
          <i class="ri-notification-off-line me-3 align-middle"></i>
          <strong>Alert</strong> - {{ alertMessage }}
          <button type="button" class="btn-close btn-close-danger" @click="dismissMessage"
            aria-label="Close Alert Message"></button>
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
