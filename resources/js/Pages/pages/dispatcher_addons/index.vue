<script>
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import Swal from "sweetalert2";
import { ref, watch, onMounted } from "vue";
import axios from "axios";
import { debounce } from 'lodash';
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import search from "@/Components/widgets/search.vue";
import searchbar from "@/Components/widgets/searchbar.vue";
import { mapGetters } from 'vuex';
import { layoutComputed } from "@/state/helpers";
import { useI18n } from 'vue-i18n';
import FormValidation from "@/Components/FormValidation.vue";

export default {
    data() {
        return {
            rightOffcanvas: false,
            dispatcher_addons: false,
        };
    },
    components: {
        Layout,
        PageHeader,
        Head,
        Pagination,
        Multiselect,
        flatPickr,
        Link,
        search,
        searchbar,
        FormValidation,

    },
    props: {
        successMessage: String,
        alertMessage: String,  
        app_for:String,     


    },
    setup(props) {
        const searchTerm = ref("");
        const { t } = useI18n();
        const filter = useForm({
            all: "",
            locked: "",
            limit:10
        });

        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');
        const validationRef = ref(null);
        const errors = ref({});

        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        const form = useForm({
            purchase_code: null,
            dispatcher_zip_file: null,
            key:null,
        });

        const validationRules = {
            purchase_code: { required: true },
        }
        const zipFileError = ref(false);

        const handleFileUpload = (e) => {
            if(props.app_for == "demo"){
                Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
                return;
            }
            const file = e.target.files[0];
            
            // Validate file type
            if (file && file.type === 'application/zip' || file.type === 'application/x-zip-compressed' || file.name.endsWith('.zip')) {
                zipFileError.value = false;
                form.dispatcher_zip_file = file; // store the file input
                return;
            }
            else{
                // If Invalid zip file,
                zipFileError.value = true;
                form.dispatcher_zip_file = null;
            }
        };
        
        const uploadButtonDisabled = ref(false);

        const handleSubmit = async (event) => {
            
            event?.preventDefault(); 
            if(props.app_for == "demo"){
                Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
                return;
            }
            if (form.dispatcher_zip_file && !validateJsonFile(form.dispatcher_zip_file)) {
                zipFileError.value = true;
                return;
            }

            try {
                let formData = new FormData();
                if (form.dispatcher_zip_file) {
                    formData.append('dispatcher_zip_file', form.dispatcher_zip_file);
                }
                uploadButtonDisabled.value = true;
                
                let response = await axios.post('/dispatcher-addons/dipathcer-files', formData , {
                    headers: {
                    'Content-Type': 'multipart/form-data',
                    },
                });
                console.log("response",response);

                if (response.status === 201) {
                    successMessage.value = t('files_uploaded_successfully');
                } else {
                    alertMessage.value = t('failed_to_files_uploaded')
                    uploadButtonDisabled.value = false;
                }
            } catch (error) {
                console.error(t('error_files_uploaded'), error);
                alertMessage.value =t('failed_to_files_uploaded_catch');
                uploadButtonDisabled.value = false;
            }
        };

        const validateJsonFile = (file) => {
            return file && file.type === 'application/zip' || file.type === 'application/x-zip-compressed' || file.name.endsWith('.zip');
        };
        const showUploadOption =  ref(false);
        const codeSuccesss = ref(false);
        const showHideForm =  ref(true);
        const codeError = ref(false);
        const isButtonDisabled = ref(false);

        const verifyPurchaseCode = async() =>{
            errors.value = validationRef.value.validate();
            if (Object.keys(errors.value).length > 0) {
                return;
            }
            try {

                const formData = new FormData();
                 if (form.purchase_code) {
                   formData.append('purchase_code', form.purchase_code);
                    formData.append('key', 'Restart');
                }
                isButtonDisabled.value = true;
                 
                let response;
                response = await axios.post('dispatcher-addons/verfication-submit', formData);
                console.log("response",response.data.success);
                if (response.data.success == true) {
                successMessage.value = t('purchase_code_verified_successfully');
                setTimeout(()=>{
                    successMessage.value = "";
                },5000)
                form.reset();
                showUploadOption.value = true;
                codeSuccesss.value = true;
                showHideForm.value = false;
                codeError.value = false;
                } else {
                alertMessage.value = t('failed_to_verify_the_purchase_code');
                codeError.value = true;
                 isButtonDisabled.value = false;
                setTimeout(()=>{
                    alertMessage.value = "";
                },5000)
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
                isButtonDisabled.value = false;
                } else if (error.response && error.response.status === 403) {
                alertMessage.value = error.response.data.alertMessage;
                isButtonDisabled.value = false;
                } else {
                isButtonDisabled.value = false;
                console.error(t('error_verify_the_purchase_code'), error);
                alertMessage.value = t('failed_to_verify_the_purchase_code_catch');
                }
            }
        };

        return {
            successMessage,
            alertMessage,
            form,            
            validationRules,
            validationRef,
            errors,
            zipFileError,
            handleFileUpload,
            handleSubmit,
            verifyPurchaseCode,
            showUploadOption,
            codeSuccesss,
            showHideForm,
            codeError,
            isButtonDisabled,
            uploadButtonDisabled
        };
    },
    computed: {
    ...layoutComputed,
    ...mapGetters(['permissions']),
  },
    mounted() {
    },
};
</script>

<template>
    <Layout>

        <Head title="Dispatcher Addons" />
        <PageHeader :title="$t('dispatcher_addons')" :pageTitle="$t('dispatcher_addons')" />
        <BRow>
            <BCol lg="12">
                <BCard no-body id="tasksList">

                    <BCardHeader class="border-0">   
                        <BLink @click="dispatcher_addons = !dispatcher_addons">
                            <h6 class="text-success float-end d-flex align-items-center me-3 text-decoration-underline text-decoration-underline-success">
                                <!-- <i class="bx bx-info-circle fs-20 me-1"></i> -->
                                {{$t('how_it_works')}}
                            </h6>
                        </BLink>                     
                    </BCardHeader>
                    <BCardBody class="border border-dashed border-end-0 border-start-0">
                        <form @submit.prevent>
                            <FormValidation ref="validationRef" :form="form" :rules="validationRules">
                                <div class="row mb-3">
                                    <div class="col-sm-6" v-if="showHideForm">
                                        <div class="mb-3">
                                            <label for="purchase_code" class="form-label">{{$t("purchase_code")}}<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" :placeholder="$t('enter_purchase_code')" id="purchase_code" v-model="form.purchase_code" />
                                            <span v-for="(error, index) in errors.purchase_code" :key="index" class="text-danger">{{ error }}</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 mt-4" v-if="showHideForm">
                                        <div class="text-end">
                                            <button type="button" class="btn btn-primary" @click.prevent="verifyPurchaseCode" :disabled="isButtonDisabled || app_for === 'demo'">{{ $t('verify') }}</button>
                                        </div>
                                    </div>
                                     <div v-if="codeSuccesss" class="text-success fs-16">
                                        <i class="ri-checkbox-circle-fill fs-14 align-middle"></i>
                                        {{$t("purchase_code_has_been_verified")}}
                                    </div>
                                    <div v-if="codeError" class="text-danger fs-14">
                                        <i class="ri-close-circle-fill fs-15 align-middle"></i>
                                        {{$t("code_has_invalid")}}
                                    </div>
                                </div>

                                <div class="row" v-if="showUploadOption">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label for="dispatcher_zip_file" class="form-label">{{$t("dispatcher_zip_file")}}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input :disabled="app_for === 'demo'" type="file" class="form-control" @change="handleFileUpload" />
                                            <div v-if="zipFileError" class="text-danger">{{$t("please_upload_a_dispatcher_zip_file")}}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="text-end">
                                            <button type="button" class="btn btn-primary" @click.prevent="handleSubmit(event)" :disabled="uploadButtonDisabled && app_for === 'demo' ">{{ $t('upload') }}</button>
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
            <!-- Success Message -->
            <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" data="alert"
                id="alertMsg">
                <div class="alert-content">
                    <i class="ri-notification-off-line me-3 align-middle"></i> <strong>Success</strong> - {{
                        successMessage }}
                    <button type="button" class="btn-close btn-close-success" @click="dismissMessage"
                        aria-label="Close Success Message"></button>
                </div>
            </div>

            <!-- Alert Message -->
            <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" data="alert"
                id="alertMsg">
                <div class="alert-content">
                    <i class="ri-notification-off-line me-3 align-middle"></i> <strong>Alert</strong> - {{ alertMessage
                    }}
                    <button type="button" class="btn-close btn-close-danger" @click="dismissMessage"
                        aria-label="Close Alert Message"></button>
                </div>
            </div>
        </div>
        <BModal v-model="dispatcher_addons" hide-footer :title="$t('dispatcher_addons')" class="v-modal-custom" size="md">
            <div class="container"> 
                <ul class="list-unstyled vstack gap-3">
                    <li>
                        <div class="d-flex">
                        <div class="flex-shrink-0 text-success me-1">
                            <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                        </div>
                        <div class="flex-grow-1">Enter the Purchase Code and Verify it</div>
                        </div>
                    </li>
                    <li>
                        <div class="d-flex">
                        <div class="flex-shrink-0 text-success me-1">
                            <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                        </div>
                        <div class="flex-grow-1">After, verify the Purchase Code it show the Zip file Uplaod Section.</div>
                        </div>
                    </li>
                    <li>
                        <div class="d-flex">
                        <div class="flex-shrink-0 text-success me-1">
                            <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                        </div>
                        <div class="flex-grow-1">Upload the Dispatcher Zip File in the Form and click the Upload Button.</div>
                        </div>
                    </li>
                    <li>
                    
                        <div class="d-flex">
                        <div class="flex-shrink-0 text-success me-1">
                            <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                        </div>
                        <div class="flex-grow-1">Once the files uploaded, then run 
                           <div class="d-flex mt-3">
                                <div class="flex-shrink-0 text-success me-1">
                                    <!-- <i class="ri-checkbox-circle-fill fs-15 align-middle"></i> --> - 
                                </div>
                                <div class="flex-grow-1">
                                    npm run build
                                </div>
                            </div>
                            <div class="d-flex mt-3">
                                <div class="flex-shrink-0 text-success me-1">
                                    <!-- <i class="ri-checkbox-circle-fill fs-15 align-middle"></i> --> - 
                                </div>
                                <div class="flex-grow-1">
                                    php artisan migrate
                                </div>
                            </div>
                            <div class="d-flex mt-3">
                                <div class="flex-shrink-0 text-success me-1">
                                    <!-- <i class="ri-checkbox-circle-fill fs-15 align-middle"></i> --> - 
                                </div>
                                <div class="flex-grow-1">
                                    php artisan db:seed
                                </div>
                            </div>
                        </div>
                        </div>
                    </li>
                </ul>              
            </div>
        </BModal>
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
