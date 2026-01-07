<script>
import { Link, Head, useForm } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Swal from "sweetalert2";
import { ref, watch, onMounted } from "vue";
import axios from "axios";
import { mapGetters } from 'vuex';
import { layoutComputed } from "@/state/helpers";
import { useI18n } from 'vue-i18n';
import ImageUpload from '@/Components/ImageUpload.vue';

export default {
    components: {
        Layout, PageHeader, Link, Head, ImageUpload,
    },
    computed: {
    ...layoutComputed,
    ...mapGetters(['permissions']),
  },

    props: {
        app_for: String,
    },
    setup() {
        const form = useForm({
            name: null,
            icon: null,
        });
        const { t } = useI18n();
        const selectedPreference = ref(null);
        const preferences = ref([]);
        const paginator = ref({});
        const successMessage = ref('');
        const alertMessage = ref('');
        const searchQuery = ref('');
        const perPage = ref(10); // Number of items per page
        const currentPage = ref(1);
        const rows = ref(0);

        const fetchPreferences = async () => {
            try {
                const response = await axios.get('/preferences/list', {
                    params: {
                        page: currentPage.value,
                        perPage: perPage.value,
                        searchQuery: searchQuery.value,
                    }
                });
                preferences.value = response.data.results;
                paginator.value = {
                    currentPage: response.data.paginator.current_page,
                    lastPage: response.data.paginator.last_page,
                    from: response.data.paginator.from,
                    to: response.data.paginator.to,
                    total: response.data.paginator.total,
                };
                rows.value = response.data.paginator.total;
            } catch (error) {
                console.error(t('error_fetching_preferences'), error);
            }
        };

        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        const createPreference = () => {
            form.reset();
            selectedPreference.value = null;
        };

        const editPreference = (preference) => {
            selectedPreference.value = preference;
            form.icon = preference.icon;
            form.name = preference.name;
            form.clearErrors();
        };

        const validateForm = () => {
            const { name,icon } = form;
            const errors = {};

            const validationRules = {
                name: { test: () => !name, message: t('required') },
                icon: { test: () => !icon, message: t('required') },
            };

            Object.keys(validationRules).forEach(fieldName => {
                if (validationRules[fieldName].test()) {
                    errors[fieldName] = validationRules[fieldName].message;
                }
            });

            return errors;
        };

        const handleSubmit = async () => {
            const errors = validateForm();
            if (Object.keys(errors).length === 0) {
                try {
                    let response;
                    if (selectedPreference.value) {
                        response = await axios.post(`/preferences/${selectedPreference.value.id}`, form.data(), {
                            headers: {
                            'Content-Type': 'multipart/form-data',
                            },
                        });
                    } else {
                        response = await axios.post("/preferences", form.data(), {
                            headers: {
                            'Content-Type': 'multipart/form-data',
                            },
                        });
                    }

     
                    successMessage.value = response.data.successMessage;
                    alertMessage.value = response.data.alertMessage;

                    setTimeout(() => {
                        successMessage.value = "";
                        alertMessage.value = "";
                    }, 2000);

                    fetchPreferences();
                    form.reset();
                    createPreference();

                } catch (error) {
                    console.error(t('error_handling_form_submission'), error);
                    if (error.response && error.response.status === 422) {
                        const errors = error.response.data.errors;
                        form.errors = errors;
                    } else {
                        console.error(t('an_unexpected_error_occurred'), error);
                    }
                }
            } else {
                form.errors = errors;
            }
        };

        const hideError = (field) => {
            form.errors[field] = '';
        };

        const deletePreference = async (preferenceId) => {
            try {
                await axios.delete(`/preferences/${preferenceId}`);
                Swal.fire('Success', 'Preference deleted successfully', 'success');
                fetchPreferences();
            } catch (error) {
                console.error(t('error_deleting_preference'), error);
                Swal.fire( t('error'), t('failed_to_delete_preference'), 'error');
            }
        };

        const deleteModal = async (itemId) => {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#34c38f",
                cancelButtonColor: "#f46a6a",
                confirmButtonText: "Yes, delete it!",
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        await deletePreference(itemId);
                    } catch (error) {
                        console.error(t('error_deleting_preference'), error);
                        Swal.fire( t('error'), t('failed_to_delete_preference'), "error");
                    }
                }
            });
        };

        const toggleActiveStatus = async (id, status) => {
            Swal.fire({
                title: t('are_you_sure'),
                text: t('you_are_about_to_change_status'),
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#34c38f",
                cancelButtonColor: "#f46a6a",
                confirmButtonText: t('yes'),
                cancelButtonText: t('cancel')
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        await axios.post(`/preferences/update-status/${id}`, { id: id, status: status });
                        const index = preferences.value.findIndex(item => item.id === id);
                        if (index !== -1) {
                            preferences.value[index].active = status; // Update the active status locally
                        }
                        Swal.fire(t('changed'), t('status_updated_successfully'), "success");
                    } catch (error) {
                        console.error(t('error_updating_status'), error);
                        Swal.fire(t('error'), t('failed_to_update_status'), "error");
                    }
                }
            });
        };


        watch(currentPage, () => {
            fetchPreferences();
        });

        watch(searchQuery, () => {
            fetchPreferences();
        });

        onMounted(() => {
            fetchPreferences();
        });

      // Construct the full URL for the vehicle type icon
      const handleImageSelected = (file, fieldName) => {
          form[fieldName] = file;
        };

        const handleImageRemoved = (fieldName) => {
          form[fieldName] = null;
        };
        return {
            form, preferences, selectedPreference, paginator, successMessage, alertMessage, searchQuery, perPage, currentPage, rows,
            createPreference, editPreference, handleSubmit, deleteModal, deletePreference, dismissMessage, hideError, fetchPreferences,
            handleImageSelected,handleImageRemoved,toggleActiveStatus,
        };
    },
};
</script>

<template>
    <Layout>

        <Head title="Preferences" />
        <PageHeader :title="$t('preferences')" :pageTitle="$t('preferences')"/>
        <BRow>
            <BCol lg="12">
                <BCard no-body id="tasksList">
                    <BCardHeader class="border-0">
                        <form @submit.prevent="handleSubmit" @keydown="hideError">
                            <BRow>
                                <BCol md="4">
                                    <div class="form-group mt-3">
                                        <label for="name" class="form-label">
                                            {{$t('name')}}<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="name" v-model="form.name" class="form-control" :readonly="app_for == 'demo' || !permissions.includes('create_preference')"
                                            :class="{ 'is-invalid': form.errors.name }" @focus="hideError('name')" />
                                        <div v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</div>
                                    </div>
                                </BCol>
                                <BCol class="ps-5" md="4">
                                    <div class="form-group mt-3">
                                        <label class="form-label">
                                            {{$t('icon')}}
                                        </label>
                                        <ImageUpload  :imageType="'icon'" :initialImageUrl="form.icon" :key="form.icon" :flexStyle="'0 0 calc(30% - 20px)'" :aspectRatio="'1 / 1'"
                                            @image-selected="(file) => handleImageSelected(file, 'icon')" @image-removed="() => handleImageRemoved('icon')"   @change="onFileChange">
                                        </ImageUpload>
                                        <div v-if="form.errors.icon" class="text-danger">{{
                                            form.errors.icon }}</div>
                                    </div>
                                </BCol>
                                <BCol md="4">
                                    <div class="mt-4">
                                        <button :disabled="app_for == 'demo' || !permissions.includes('create_preference')" type="submit" class="btn btn-success m-2 float-start">
                                            {{ selectedPreference ? $t('update') : $t('create') }}
                                        </button>
                                        <button v-if="selectedPreference" type="button" @click="createPreference"
                                            class="btn btn-secondary m-2 float-start">
                                            {{$t("cancel")}}
                                        </button>
                                    </div>
                                </BCol>
                            </BRow>
                        </form>
                    </BCardHeader>
                </BCard>

                <BCard>
                    <BCardBody class="border border-dashed border-end-0 border-start-0 ">
                        <BRow>
                            <BCol md="12">
                                <div class="table-responsive table-card mb-4">
                                    <table class="table table-bordered align-middle table-nowrap mb-0 overflow-auto">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{$t("s_no")}}</th>
                                                <th>{{$t("name")}}</th>
                                                <th>{{$t("status")}}</th>
                                                <th>{{$t("icon")}}</th>
                                                <th>{{$t("action")}}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(preference, index) in preferences" :key="preference.id">
                                                <td>{{ (currentPage - 1) * perPage + index + 1 }}</td>
                                                <td>{{ preference.name }}</td>
                                                <td>
                                                    <div :class="{
                                                            'form-check': true,
                                                            'form-switch': true,
                                                            'form-switch-lg': true,
                                                            'form-switch-success': preference.active,
                                                        }">
                                                        <input class="form-check-input" :disabled="!permissions.includes('toggle_preference')" type="checkbox" role="switch" @click.prevent="toggleActiveStatus(preference.id,!preference.active)" :id="'status_'+preference.id" :checked="preference.active">
                                                    </div>
                                                </td>
                                                <td> <img :src="preference.icon"  alt="" class="avatar-xs rounded-circle me-2"></td>
                                                <td>
                                                    <div class="d-flex">
                                                        <BButton @click.prevent="editPreference(preference)" v-if="permissions.includes('edit_preference')"
                                                            class="btn btn-soft-warning btn-sm m-2" :disabled="app_for == 'demo'"
                                                            data-bs-toggle="tooltip" v-b-tooltip.hover :title="$t('edit')">
                                                            <i class='bx bxs-edit-alt bx-xs'></i>
                                                        </BButton>
                                                        <BButton class="btn btn-soft-danger btn-sm m-2" size="sm" v-if="app_for != 'demo' && permissions.includes('delete_preference')"
                                                            type="button" @click.prevent="deleteModal(preference.id)"
                                                            data-bs-toggle="tooltip" v-b-tooltip.hover :title="$t('delete')">
                                                            <i class='bx bx-trash bx-xs'></i>
                                                        </BButton>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <div class="d-flex justify-content-sm-end">
            <b-pagination v-model="currentPage" :total-rows="rows" :per-page="perPage" aria-controls="my-table"
                align="right"></b-pagination>
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" language="alert"
            id="alertMsg">
            <div class="alert-content">
                <i class="ri-notification-off-line me-3 align-middle"></i> <strong>Success</strong> - {{
                    successMessage }}
                <button type="button" class="btn-close btn-close-success" @click="dismissMessage"
                    aria-label="Close Success Message"></button>
            </div>
        </div>

        <!-- Alert Message -->
        <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" language="alert"
            id="alertMsg">
            <div class="alert-content">
                <i class="ri-notification-off-line me-3 align-middle"></i> <strong>Alert</strong> - {{ alertMessage
                }}
                <button type="button" class="btn-close btn-close-danger" @click="dismissMessage"
                    aria-label="Close Alert Message"></button>
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
    float: right;
    position: fixed;
    top: 100px;
    right: 80px;
}
</style>
