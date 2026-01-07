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
import { mapGetters } from 'vuex';
import { layoutComputed } from "@/state/helpers";
import { useI18n } from 'vue-i18n';
import { useSharedState } from '@/composables/useSharedState';
import { result } from 'lodash';
import searchbar from "@/Components/widgets/searchbar.vue";

export default {
    data() {
        return {
            rightOffcanvas: false,
            showUsers: false,
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

        promo: {
            type: Object,
            required: true,
        }


    },
    setup(props) {
        const { t } = useI18n();
        const searchTerm = ref("");
        const { selectedLocation } = useSharedState();
        const filter = useForm({
            promo_id: props.promo?.id,
            status: null,
            transport_type: null,
            service_location_id: 'all',
            limit:10
        });
        const results = ref([]); // Spread the results to make them reactive
        const paginator = ref({}); // Spread the results to make them reactive
        const modalShow = ref(false);
        const modalFilter = ref(false);
        const deleteItemId = ref(null);
        const paginatorOption = ref({}); // Spread the results to make them reactive

        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');

        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        // const toggleActiveStatus = async (id, status) => {
        //     try {
        //         await axios.post(`/promo-code/update-status`, { id: id, status: status });
        //         const index = results.value.findIndex(item => item.id === id);
        //         if (index !== -1) {
        //             if(results.value[index].active === 1){                       
        //                 successMessage.value = t('promo_code_inactive_successfully');
        //                 results.value[index].active = status; // Update the active status locally
        //             }
        //             else{
        //                 successMessage.value = t('promo_code_active_successfully');
        //                 results.value[index].active = status; // Update the active status locally
        //             }// Update the active status locally
        //         }
        //         // Optionally, you may want to re-fetch all data to ensure consistency
        //         // fetchDatas(); 
        //     } catch (error) {
        //         console.error(t('error_updating_status'), error);
        //     }
        // };

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
                        await axios.post(`/promo-code/update-status`, { id: id, status: status });
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
        const capitalizeFirstLetter = (value) => {
            return value.charAt(0).toUpperCase() + value.slice(1);
        };

        const rightOffcanvas = ref(false);
        const filterData = () => {
            fetchDatas();
            modalFilter.value = true;
            rightOffcanvas.value = false;
        };


        const clearFilter = () => {
            filter.reset();
            fetchDatas();
            // modalFilter.value = false;
            rightOffcanvas.value = false;
        };


        const closeModal = () => {
            modalShow.value = false;
        };
        const deleteData = async (dataId) => {
            try {
                await axios.delete(`/promo-code/delete/${dataId}`);
                const index = results.value.findIndex(data => data.id === dataId);
                if (index !== -1) {
                    results.value.splice(index, 1);
                }
                modalShow.value = false;
                Swal.fire(t('success'), t('promo_code_deleted_successfully'), 'success');
            } catch (error) {
                Swal.fire(t('error'), t('failed_to_delete_promo_code'), 'error');
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

        const rideStatus = (trip) => {
            if(trip.is_cancelled){
                return 'Cancelled';
            }else if(trip.is_completed){
                return 'Completed';
            }else if(trip.is_trip_start){
                return 'On Trip';
            }else if(trip.is_driver_arrived){
                return 'Driver Arrived';
            }else if(trip.is_later && trip.is_driver_started){
                return 'Driver Started';
            }else if(trip.is_driver_started){
                return 'Accepted';
            }else if(!trip.is_later){
                return 'Searching';
            }else{
                return 'Upcoming'
            }
        };
         const fetchSearch = async (value) => {
            searchTerm.value = value;
            fetchDatas();
        };
        const fetchDatas = async (page = 1) => {
            try {
                const params = filter.data();
                params.search = searchTerm.value;
                params.page = page;
                const response = await axios.get(`/promo-code/historyList`, { params });
                results.value = response.data.results;
                paginator.value = response.data.paginator;
                updatePaginatorOptions(paginator.value.total);// Update paginator options dynamically

                modalFilter.value = false;
            } catch (error) {
                console.error(t('error_fetching_promo_codes'), error);
            }
        };

        const handlePageChanged = async (page) => {
            localStorage.setItem("promo-code/historyList", page); // Save to localStorage
            fetchDatas(page);
        };

        const editData = async (result) =>  {
            router.get(`/rides-request/view/${result.id}`); 
        };

        const updatePaginatorOptions = () => {
            paginatorOption.value = [10, 25, 50, 100,200,500]; // Default static options
        };

          watch(selectedLocation, (value)=> {
            filter.service_location_id = value;
            fetchDatas();
        });

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
            filterData,
            deleteModal,
            closeModal,
            deleteData,
            dismissMessage,
            paginator,
            modalFilter,
            clearFilter,
            fetchDatas,
            filter,
            handlePageChanged,
            rideStatus,
            editData,
            capitalizeFirstLetter,
            toggleActiveStatus,
            rightOffcanvas,
            paginatorOption,
            changeEntriesPerPage,
            selectedLocation,
            searchTerm,
            fetchSearch,

        };
    },
    computed: {
    ...layoutComputed,
    ...mapGetters(['permissions']),
  },
  mounted() {
        this.filter.service_location_id = this.selectedLocation;
        this.fetchDatas();
        const savedPage = localStorage.getItem("promo-code/historyList");
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

        <Head title="Promo Code" />
        <PageHeader :title="$t('promo_code')" :pageTitle="$t('promo_code')" />
        <BRow>
            <BCol lg="12">
                <BCard no-body id="tasksList">

                    <BCardHeader class="border-0">
                        <BRow>
                            <div class="d-flex flex-wrap gap-3">
                            <BCard class="flex-fill equal-card" no-body id="tasksList">
                                <BCardBody class="border-0">
                                    <div class=" profile-border fs-18" >
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("code")}}</b></h5> <br>
                                            <h5>{{ promo.code }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("service_location")}}</b></h5> <br>
                                            <h5>{{ promo.area_name }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("transport_type")}}</b></h5> <br>
                                            <h5>{{ $t(promo.transport_type) }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("discount_percentage")}}</b></h5> <br>
                                            <h5>{{ promo.discount_percent ?? 0 }} % </h5>
                                        </div>
                                    </div>
                                </BCardBody>
                            </BCard>
                            <BCard class="flex-fill equal-card" no-body id="tasksList">
                                <BCardBody class="border-0">
                                    <div class=" profile-border fs-18" >
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("from")}}</b></h5>
                                            <h5>{{ promo.from_date }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("to")}}</b></h5>
                                            <h5>{{ promo.to_date }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("discount_used")}}</b></h5> <br>
                                            <h5> {{ promo.area_unit }} {{ promo.cummulative_maximum_discount_amount - promo.available_balance }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("available_balance")}}</b></h5> <br>
                                            <h5> {{ promo.area_unit }} {{ promo.available_balance }} </h5>
                                        </div>
                                    </div>
                                </BCardBody>
                            </BCard>
                            <BCard class="flex-fill equal-card" no-body id="tasksList">
                                <BCardBody class="border-0">
                                    <div class=" profile-border fs-18" >
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("minimum_amount")}}</b></h5> <br>
                                            <h5> {{ promo.area_unit }} {{ promo.minimum_trip_amount }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("maximum_discount")}}</b></h5> <br>
                                            <h5> {{ promo.area_unit }} {{ promo.maximum_discount_amount }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("uses_per_user")}}</b></h5>
                                            <h5>{{ promo.uses_per_user }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5><b>{{$t("cummulative_maximum_discount")}}</b></h5>
                                            <h5> {{ promo.area_unit }} {{ promo.cummulative_maximum_discount_amount }}</h5>
                                        </div>
                                    </div>
                                </BCardBody>
                            </BCard>
                            <BCard class="flex-fill equal-card" v-if="promo.user_specific && promo.users?.length" no-body id="tasksList">
                                <h5 class="flex-grow-1 text-start text-muted badge bg-secondary-subtle fs-18"><b>{{$t("users")}}</b></h5>
                                <BCardBody class="border-0 overflow-auto users-scroll">
                                    <div class="card mb-0" v-for="(user, index) in promo.users" :key="index">
                                        <div class=" d-flex mb-2 align-items-center justify-content-between">
                                            <h5>{{ user.user_name }}</h5>
                                        </div>
                                    </div>
                                </BCardBody>
                            </BCard>
                            </div>
                        </BRow>
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
                                    <BButton variant="danger" @click="rightOffcanvas = true"><i
                                            class="ri-filter-2-line me-1 align-bottom"></i> {{ $t("filters") }}</BButton>

                                </div>
                            </BCol>
                        </BRow>
                    </BCardHeader>
                    <BCardBody class="border border-dashed border-end-0 border-start-0">
                        <div class="table-responsive">
                            <table class="table align-middle position-relative table-nowrap">
                                <thead class="table-active">
                                    <tr>
                                        <th scope="col"> {{$t("request_id")}}</th>
                                        <th scope="col"> {{$t("date")}}</th>
                                        <th scope="col"> {{$t("user_name")}}</th>
                                        <th scope="col"> {{$t("transport_type")}}</th>
                                        <th scope="col"> {{$t("discount")}}</th>
                                        <th scope="col"> {{$t("action")}}</th>
                                    </tr>
                                </thead>
                                <tbody v-if="results.length > 0">
                                    <tr v-for="(result, index) in results" :key="index">
                                        <td>{{ result.request_number}}</td> 
                                        <td>{{ result.is_later ? result.converted_trip_start_time : result.converted_created_at }}</td> 
                                        <td>{{ result.user_name ? result.user_name : '----' }}</td>       
                                        <td>{{ result.transport_type=="taxi" ? $t("taxi") : $t("delivery")}} {{ result.is_bid_ride ? $t('bidding') : '' }} - {{ result.vehicle_type_name }}</td> 
                                        <td>{{ result.requested_currency_symbol }} {{ result.promo_discount ?? 0 }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <a class="text-reset" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="text-muted fs-18"><i class="mdi mdi-dots-vertical"></i></span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <div class="dropdown-item" type="button" @click.prevent="editData(result)">
                                                        <i class=" bx bx-show-alt align-center text-muted me-2"></i>  {{$t("view")}}
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </td>
                                     </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <img src="@assets/images/search-file.gif" alt="Loading..." style="width:100px" />
                                            <h5> {{$t("no_data_found")}}</h5>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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

        <!-- filter -->
        <BOffcanvas v-model="rightOffcanvas" placement="end" :title="$t('promo_filters')" header-class="bg-light"
            body-class="p-0 overflow-hidden" footer-class="border-top p-3 text-center">
            <BFrom action="" class="d-flex flex-column justify-content-end h-100">
                <div class="offcanvas-body">

                    <div class="mb-4">
                        <label for="datepicker-range"
                            class="form-label text-muted text-uppercase fw-semibold mb-3">{{ $t("status") }}</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                    id="WithoutinlineRadio2" value=1 v-model="filter.status">
                                <label class="form-check-label" for="WithoutinlineRadio2">{{ $t("active") }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                    id="WithoutinlineRadio3" value=0 v-model="filter.status">
                                <label class="form-check-label" for="WithoutinlineRadio3">{{ $t("inactive") }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="datepicker-range"
                            class="form-label text-muted text-uppercase fw-semibold mb-3">{{ $t("transport_type") }}</label>
                        <select class="form-control" data-choices data-choices-search-false name="choices-select-status"
                            id="choices-select-status" v-model="filter.transport_type">
                            <option value="taxi">{{ $t("taxi") }}</option>
                            <option value="delivery">{{ $t("delivery") }}</option>
                            <option value="both">{{ $t("both") }}</option>
                        </select>
                    </div>
                                 
                </div>
                <!--end offcanvas-body-->
                <div class="offcanvas-footer border-top p-3 text-center hstack gap-2">
                    <BButton variant="light" @click="clearFilter()" class="w-100">{{ $t("clear_filter") }}</BButton>
                    <BButton type="submit" @click="filterData"  variant="success" class="w-100">
                        {{ $t("apply") }}
                    </BButton>
                </div>
                <!--end offcanvas-footer-->
            </BFrom>
        </BOffcanvas>
        <!--end offcanvas-->
        <!-- filter end -->

        <!-- Pagination -->
        <Pagination :paginator="paginator" @page-changed="handlePageChanged" />
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

.users-scroll {
  max-height: 120px; /* adjust height as needed */
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #999 transparent;
}

/* Optional: custom scrollbar look (for webkit browsers) */
.users-scroll::-webkit-scrollbar {
  width: 6px;
}
.users-scroll::-webkit-scrollbar-thumb {
  background-color: #bbb;
  border-radius: 4px;
}
.users-scroll::-webkit-scrollbar-thumb:hover {
  background-color: #888;
}
</style>
