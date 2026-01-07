<script>
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import Swal from "sweetalert2";
import { ref,watch, onMounted } from "vue";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import searchbar from "@/Components/widgets/searchbar.vue";
import { mapGetters } from 'vuex';
import { layoutComputed } from "@/state/helpers";
import { useI18n } from 'vue-i18n';
import { useSharedState } from '@/composables/useSharedState';
import { object } from '@amcharts/amcharts5';

export default {
    data() {
        return {
            SearchQuery: '',
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
        searchbar,
    },
    props: {
        successMessage: String,
        alertMessage: String,
        zonetype: {
            type: Object,
            required: true,
        },
    },

    setup(props) {
        const { t } = useI18n();
        const searchTerm = ref("");
        const filter = useForm({
            service_location_id: null,
            zone_type: props.zonetype?.id,
            limit:10
        });
        const results = ref([]);
        const paginator = ref({});
        const modalShow = ref(false);
        const modalFilter = ref(false);
        const deleteItemId = ref(null);
        const paginatorOption = ref({}); // Spread the results to make them reactive
        const { selectedLocation } = useSharedState();

        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');


        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        // const toggleActiveStatus = async (id, status) => {
        //     try {
        //         await axios.post(`/farefix/update-status`, { id, status });
        //         const index = results.value.findIndex(item => item.id === id);
        //         if (index !== -1) {
        //             results.value[index].active = status; // Update the active status locally
        //         }
        //     } catch (error) {
        //         if (error.response && error.response.status == 403) {
        //             alertMessage.value = error.response.data.alertMessage;
        //             setTimeout(()=>{
        //                 alertMessage.value = "";
        //             },5000)
        //         }
        //         console.error(t('error_updating_status'), error);
        //     }
        // };


        watch(selectedLocation, (value)=> {
            filter.service_location_id = value;
            fetchDatas();
        });
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
                        await axios.post(`/farefix/update-status`, { id: id, status: status });
                        const index = results.value.findIndex(item => item.id === id);
                        if (index !== -1) {
                            results.value[index].active = status; // Update the active status locally
                        }
                        Swal.fire(t('changed'), t('status_updated_successfully'), "success");
                    } catch (error) {
                        console.error(t('error_updating_status'), error);
                        Swal.fire(t('error'), t('failed_to_update_status'), "error");
                    }
                }
            });
        };

        const deleteData = async (result) => {
            try {
                await axios.delete(`/farefix/delete/${result}`);
                const index = results.value.findIndex(data => data.id === result);
                if (index !== -1) {
                    results.value.splice(index, 1);
                }
                modalShow.value = false;
                Swal.fire(t('success'), t('vehicle_price_deleted_successfully'), 'success');
            } catch (error) {
                if (error.response && error.response.status == 403) {
                    alertMessage.value = error.response.data.alertMessage;
                    setTimeout(()=>{
                        alertMessage.value = "";
                    },5000)
                }
                Swal.fire(t('error'), t('failed_to_delete_vehicle_price'), 'error');
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
                        await deleteData(itemId);
                    } catch (error) {
                        console.error(t('error_deleting_data'), error);
                        Swal.fire(t('error'), t('failed_to_delete_the_data'), "error");
                    }
                }
            });
        };

        const fetchSearch = async (value) => {
            searchTerm.value = value;
            fetchDatas();
        };

        const fetchDatas = async (page = 1) => {
            try {
                const params = filter.data();
                params.page = page;
                params.include = 'vehicleType,zone'; // Add this line to eager-load relationships

                // Only include status parameter if it's not empty (to show all)
                if (filter.status !== "") {
                    params.status = filter.status;
                }
                if(searchTerm.value.length > 0){
                    params.search = searchTerm.value;
                }
                const response = await axios.get(`/farefix/list`, { params });
                console.log("list-data");
                console.log(response.data.results);

                results.value = response.data.results;
                paginator.value = response.data.paginator;
                updatePaginatorOptions(paginator.value.total);// Update paginator options dynamically
                modalFilter.value = false;
            } catch (error) {
                console.error(t('error_fetching_set_prices'), error);
            }
        };

        const handlePageChanged = async (page) => {
            localStorage.setItem("farefix/list", page); // Save to localStorage
            fetchDatas(page);
        };

        const editData = async (result) => {
            router.get(`/farefix/edit/${result.id}`);
        };

        const updatePaginatorOptions = () => {
            paginatorOption.value = [10, 25, 50, 100,200,500]; // Default static options
        };
        // **Handle per-page changes**
        const changeEntriesPerPage = () => {
            fetchDatas(); // Fetch new data
        };

        return {
            results,
            modalShow,
            deleteItemId,
            successMessage,
            alertMessage,
            deleteModal,
            dismissMessage,
            searchTerm,
            fetchSearch,
            paginator,
            modalFilter,
            fetchDatas,
            filter,
            handlePageChanged,
            editData,
            toggleActiveStatus,
            paginatorOption,
            selectedLocation,
            changeEntriesPerPage
        };
    },
    computed: {
    ...layoutComputed,
    ...mapGetters(['permissions']),
  },
    mounted() {
        this.filter.service_location_id = this.selectedLocation;
        this.fetchDatas();
        const savedPage = localStorage.getItem("farefix/list");
        if(savedPage){
            this.handlePageChanged(Number(savedPage));
        }
        else{
            this.handlePageChanged(1);
        }
    },
};
</script>

<template>
    <Layout>
        <Head :title="$t('farefix')" />
        <PageHeader :title="$t('farefix')" :pageTitle="$t('farefix')" />
        <BRow>
            <BCol lg="12">
                <BCard no-body id="tasksList">
                    <BCardHeader class="border-0">
                        <BRow class="g-2">
                            <BCol md="3">
                                <div class="d-flex align-items-center mt-3">
                                    <label class="me-2 text-muted">{{$t("show")}}</label>
                                    <select v-model="filter.limit" @change="changeEntriesPerPage" class="form-select form-select-sm w-auto">
                                    <option v-for="option in paginatorOption" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                    </select>
                                    <label class="ms-2 text-muted">{{$t("entries")}}</label>
                                </div>
                            </BCol>
                            <BCol md="3">
                            </BCol>
                            <BCol md="auto" class="ms-auto">
                                <div class="d-flex align-items-center gap-2">
                                    <searchbar @search="fetchSearch"></searchbar>
                                    <Link :href="`/farefix/create/${zonetype?.id}`" v-if="permissions.includes('add-price')">
                                        <BButton variant="primary" class="float-end"> <i
                                                class="ri-add-line align-bottom me-1"></i> {{$t("add_farefix")}}</BButton>
                                    </Link>
                                </div>
                            </BCol>
                        </BRow>
                    </BCardHeader>
                    <BCardBody class="border border-dashed border-end-0 border-start-0">
                        <div class="table-responsive">
                            <table class="table align-middle position-relative table-nowrap">
                                <thead class="table-active">
                                    <tr>
                                        <th scope="col"> {{$t("zone")}}</th>
                                        <th scope="col"> {{$t("vehicle_type")}}</th>
                                        <th scope="col"> {{$t("drop_zone")}}</th>
                                        <th scope="col"> {{$t("status")}}</th>
                                        <th scope="col" class="mx-5"> {{$t("action")}}</th>
                                    </tr>
                                </thead>
                                <tbody v-if="results.length > 0">
                                    <tr v-for="(result, index) in results" :key="index">
                                        <td>{{ result.zone_name }}</td>
                                        <td>{{ result.vehicle_type_name }}</td>
                                        <td>{{ result.drop_zone_name }}</td>
                                        <td v-if="permissions.includes('toggle-price')">
                                            <div :class="{
                                                    'form-check': true,
                                                    'form-switch': true,
                                                    'form-switch-lg': true,
                                                    'form-switch-success': result.active,
                                                }">
                                                <input class="form-check-input" type="checkbox" role="switch" @click.prevent="toggleActiveStatus(result.id, !result.active)" :id="'status_' + result.id" :checked="result.active">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <BButton class="btn btn-soft-warning btn-sm m-2" @click.prevent="editData(result)" v-if="permissions.includes('edit-price')"
                                                    data-bs-toggle="tooltip" v-b-tooltip.hover
                                                    :title="$t('edit')">
                                                    <i class='bx bxs-edit-alt bx-xs'></i>
                                                </BButton>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <img src="@assets/images/search-file.gif" alt="Loading..." style="width:100px" />
                                            <h5>{{ $t("no_data_found") }}</h5>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </BCardBody>
                </BCard>
                <pagination :paginator="paginator" @page-changed="handlePageChanged" />
            </BCol>
        </BRow>
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
        <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" data="alert" id="alertMsg">
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
a{
    cursor: pointer;
}


</style>
