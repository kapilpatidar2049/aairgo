<script>
import { Link,Head, useForm } from '@inertiajs/vue3';
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
import useVuelidate from "@vuelidate/core";
import { UsersIcon } from '@zhuowenli/vue-feather-icons';
import getChartColorsArray from "@/common/getChartColorsArray";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Autoplay } from "swiper/modules";
import "swiper/css";
import "swiper/css/autoplay";
import { useI18n } from 'vue-i18n';
import { useSharedState } from '@/composables/useSharedState';

export default {
  props: {
    firebaseSettings: Array,
    driverIds: Array,

  },
  data() {
    return {};
  },
  components: {
    Layout,
    PageHeader,
    Head,
    Pagination,
    Multiselect,
    flatPickr,
    Link,
    UsersIcon,
    Swiper,
    SwiperSlide,
  },
  setup(props) {
    const driverIds = props.driverIds;
    const { selectedLocation } = useSharedState();
    const { t } = useI18n();
        // Reactive references for counting
    const onLine = ref(0);
    const offLine = ref(0);
    const blockedFleetDrivers = ref(0);
    console.log("earningsData",props.earningsData);


    const total_drivers = ref('');
    const total_owners = ref('');
    const total_fleets = ref('');

    const earningsData = ref('');
    const currencySymbol = ref('');
    const todayEarnings = ref('');
    const overallEarnings = ref('');
    const todayTrips = ref('');
    const fleetsEarnings = ref('');
    const fleetDriverEarnings = ref('');
    const overall = ref('');
    const overallChartOptions = ref('');


    const fetchOwnerDashboardData = async () => {
      try {
        const response = await axios.get('/owner-dashboard/data',{ params:{service_location_id : selectedLocation.value}});
        total_drivers.value = response.data.total_drivers;
        total_owners.value = response.data.total_owners;
        total_fleets.value = response.data.total_fleets;
        console.log("response",response);

      } catch (error) {
        console.error(t('error_fetching_today_earnings'), error);
      }
    }

    const fetchOwnerEarnings = async () => {
      try {
        const response = await axios.get('/owner-dashboard/earnings',{ params:{service_location_id : selectedLocation.value}});
          // Overall earnings chart options
      overall.value = [
        {
          name: t('overall_earnings'),
          data: response.data.earningsData.earnings.values,
        },
      ],
      overallChartOptions.value = {
        chart: {
          height: 100,
          type: "area",
          toolbar: "false",
        },
        dataLabels: {
          enabled: false,
        },
        stroke: {
          curve: "smooth",
          width: 3,
        },
        xaxis: {
        categories: response.data.earningsData.earnings.months, //x Axis months
        },
        yaxis: {
          labels: {
            formatter: function (value) {
              return value;
            },
          },
          tickAmount: 5,
          min: 0,
          max: Math.max(...response.data.earningsData.earnings.values) * 1.1, // Adjust max value dynamically
        },
        colors: getChartColorsArray('["--vz-success"]'),
        fill: {
          opacity: 0,
          colors: ["#0AB39C", "#F06548"],
          type: "solid",
        },
      },
          currencySymbol.value = response.data.currencySymbol;
          todayEarnings.value = response.data.todayEarnings;
          overallEarnings.value = response.data.overallEarnings;
          todayTrips.value = response.data.todayTrips;
          fleetsEarnings.value = response.data.fleetsEarnings
          fleetDriverEarnings.value = response.data.fleetDriverEarnings;
        console.log("response-earning",response);

      } catch (error) {
        console.error(t('error_fetching_today_earnings'), error);
      }
    }
    const fetchAllData = async() => {
        await fetchOwnerDashboardData();
        await fetchOwnerEarnings();
    }

    watch (()=>selectedLocation.value, (value) => {
      if(value){
          fetchAllData();
      }
    })
    // Firebase initialization
 onMounted(async () => {
      // Firebase initialization
      await fetchAllData();
      const firebaseConfig = {
        apiKey: props.firebaseSettings['firebase_api_key'],
        authDomain: props.firebaseSettings['firebase_auth_domain'],
        databaseURL: props.firebaseSettings['firebase_database_url'],
        projectId: props.firebaseSettings['firebase_project_id'],
        storageBucket: props.firebaseSettings['firebase_storage_bucket'],
        messagingSenderId: props.firebaseSettings['firebase_messaging_sender_id'],
        appId: props.firebaseSettings['firebase_app_id'],
      };

      if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
      }

      const driversRef = firebase.database().ref('drivers');
      const driverIds = props.driverIds;

      let totalFleetDrivers = driverIds.length;

      const promises = driverIds.map(driverId => {
        return driversRef.orderByChild('id').equalTo(driverId).once('value').then(snapshot => {
          snapshot.forEach(childSnapshot => {
            const driverData = childSnapshot.val();
            if (driverData.is_active === 1) {
              onLine.value++;
            } else if (driverData.is_active === 0) {
              offLine.value++;
            }
          });
        });
      });

      Promise.all(promises).then(() => {
        blockedFleetDrivers.value = totalFleetDrivers - (onLine.value + offLine.value);
        // console.log(`Online Count: ${onLine.value}`);
        // console.log(`Offline Count: ${offLine.value}`);
        // console.log(`Total Fleet Drivers: ${totalFleetDrivers}`);
        // console.log(`Blocked Drivers: ${blockedFleetDrivers.value}`);
      }).catch(error => {
        console.error(t('error_fetching_data'), error);
      });
    });

    return {
      onLine,
      offLine,
      blockedFleetDrivers,
      total_drivers,
      total_owners,
      total_fleets,
      overall,
      overallChartOptions,
      // Today earnings chart options
      series: [
        Number(todayTrips.value.today_completed),
        Number(todayTrips.value.today_cancelled),
        Number(todayTrips.value.today_scheduled),    
      ],
      chartOptions: {
        labels: [t('completed'), t('cancelled'), t('scheduled')],
        chart: {
          type: "donut",
          height: 219,
        },
        plotOptions: {
          pie: {
            size: 100,
            donut: {
              size: "76%",
            },
          },
        },
        dataLabels: {
          enabled: false,
        },
        legend: {
          show: false,
          position: "bottom",
          horizontalAlign: "center",
          offsetX: 0,
          offsetY: 0,
          markers: {
            width: 20,
            height: 6,
            radius: 2,
          },
          itemMargin: {
            horizontal: 12,
            vertical: 0,
          },
        },
        stroke: {
          width: 0,
        },
        yaxis: {
          labels: {
            formatter: function (value) {
              return value;
            },
          },
          tickAmount: 4,
          min: 0,
        },
        colors: getChartColorsArray('["--vz-primary", "--vz-warning", "--vz-info"]'),
      },
    earningsData,
    currencySymbol,
    todayEarnings,
    overallEarnings,
    todayTrips,
    fleetsEarnings,
    fleetDriverEarnings,

    };
  },

  methods: {},
};
</script>

<template>
  <Layout>
    <PageHeader :title="$t('owner-dashboard')" :pageTitle="$t('owner-dashboard')" />
    <div class="row">
      <!-- owner cards -->
      <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
                                <i class="ri-user-2-fill"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 overflow-hidden ms-3">
                              <h4 class="text-uppercase fw-medium text-muted text-truncate mb-3"><span class="text-success">{{$t("registered")}}</span>  {{$t("owners")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="825">{{ total_owners.total }}</span></h4>
                                  <Link href="/manage-owners" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->
      <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
                                <i class="ri-user-2-fill"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 overflow-hidden ms-3">
                              <h4 class="text-uppercase fw-medium text-muted text-truncate mb-3"><span class="text-success">{{$t("approved")}}</span>  {{$t("owners")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="825">{{ total_owners.approved }}</span></h4>
                                  <Link href="/manage-owners" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->
          <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-danger-subtle text-danger rounded-2 fs-2">
                                <i class="ri-user-2-fill"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 ms-3">
                              <h4 class="text-uppercase fw-medium text-muted mb-3"><span class="text-danger">{{$t("owner")}}</span> {{$t("awaiting_review")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="7522">{{ total_owners.declined }}</span></h4>
                                  <Link href="/manage-owners" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->

 <!-- vehicles cards -->
      <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
                                <i class="ri-taxi-line"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 overflow-hidden ms-3">
                              <h4 class="text-uppercase fw-medium text-muted text-truncate mb-3"><span class="text-success">{{$t("registered")}}</span>  {{$t("fleets")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="825">{{ total_fleets.total }}</span></h4>
                                  <Link href="/manage-fleet" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->
      <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
                                <i class="ri-taxi-line"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 overflow-hidden ms-3">
                              <h4 class="text-uppercase fw-medium text-muted text-truncate mb-3"><span class="text-success">{{$t("approved")}}</span>  {{$t("fleets")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="825">{{ total_fleets.approved }}</span></h4>
                                  <Link href="/manage-fleet" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->
          <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-danger-subtle text-danger rounded-2 fs-2">
                                <i class="ri-taxi-line"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 ms-3">
                              <h4 class="text-uppercase fw-medium text-muted mb-3"><span class="text-danger">{{$t("fleets")}}</span> {{$t("awaiting_review")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="7522">{{ total_fleets.declined }}</span></h4>
                                  <Link href="/manage-fleet" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->

<!-- driver cards -->
      <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
                                <i class=" ri-steering-2-fill"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 overflow-hidden ms-3">
                              <h4 class="text-uppercase fw-medium text-muted text-truncate mb-3"><span class="text-success">{{$t("registered")}}</span>  {{$t("drivers")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="825">{{ total_drivers.total }}</span></h4>
                                  <Link href="/fleet-drivers" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->
      <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
                                <i class=" ri-steering-2-fill"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 overflow-hidden ms-3">
                              <h4 class="text-uppercase fw-medium text-muted text-truncate mb-3"><span class="text-success">{{$t("approved")}}</span>  {{$t("drivers")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="825">{{ total_drivers.approved }}</span></h4>
                                  <Link href="/fleet-drivers" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->
          <div class="col-xl-4">
              <div class="card card-animate">
                  <div class="card-body">
                      <div class="d-flex align-items-center">
                          <div class="avatar-sm flex-shrink-0">
                              <span class="avatar-title bg-danger-subtle text-danger rounded-2 fs-2">
                                <i class=" ri-steering-2-fill"></i>
                              </span>
                          </div>
                          <div class="flex-grow-1 ms-3">
                              <h4 class="text-uppercase fw-medium text-muted mb-3"><span class="text-danger">{{$t("drivers")}}</span> {{$t("awaiting_review")}}</h4>
                              <div class="d-flex align-items-center mb-3">
                                  <h4 class="fs-4 flex-grow-1 mb-0"><span class="counter-value" data-target="7522">{{ total_drivers.declined }}</span></h4>
                                  <Link href="/fleet-drivers/pending" class="text-decoration-underline">{{ $t("view_all") }}</Link>
                              </div>
                          </div>
                      </div>
                  </div><!-- end card body -->
              </div>
          </div><!-- end col -->
      </div>


<!-- revenue  -->
<!-- rides -->
  <BRow>
    <BCol xl="6" md="6">
          <BCard no-body class="card-height-100">
    <BCardHeader class="align-items-center d-flex py-0">
      <BCardTitle class="mb-0 flex-grow-1 p-3">{{ $t("today_trips") }}</BCardTitle>
    </BCardHeader>
    <BCardBody>
      <apexchart class="apex-charts" dir="ltr" height="219" :series="series" :options="chartOptions"></apexchart>

      <div class="table-responsive mt-3">
        <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-0">
          <tbody class="border-0">
            <tr>
              <td>
                <h4 class="text-truncate fs-14 fs-medium mb-0">
                  <i class="ri-stop-fill align-middle fs-18 text-primary me-2"></i>{{ $t("completed_rides") }}
                </h4>
              </td>
            </tr>
            <tr>
              <td>
                <h4 class="text-truncate fs-14 fs-medium mb-0">
                  <i class="ri-stop-fill align-middle fs-18 text-warning me-2"></i>{{ $t("cancelled_rides") }}
                </h4>
              </td>
            </tr>
            <tr>
              <td>
                <h4 class="text-truncate fs-14 fs-medium mb-0">
                  <i class="ri-stop-fill align-middle fs-18 text-info me-2"></i>{{ $t("scheduled_rides") }}
                </h4>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </BCardBody>
  </BCard>
</BCol>

<BCol xl="6" md="6">
<div class="row">
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("today_earnings") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.total }} </h2>
                            </div>
                      <div>
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-info icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_cash") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.cash }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-success icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
  <div class="row">
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_wallet") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.wallet }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_card") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.card }} </h2>                     
                           </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-danger icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
  <div class="row">
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("admin_commission") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.admin_commision }} </h2>                     
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-primary icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("drivers_earnings") }}</p>
                         <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.driver_commision }} </h2>                                           
                             </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-dark-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-dark icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
</Bcol>
</BRow>


<!-- total earnings -->
<BRow>
<BCol xl="6" md="6">
<div class="row">
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("overall_earnings") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.total }} </h2>  
                            </div>
                      <div>
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-danger icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_cash") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                           <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.cash }} </h2>                     
                           </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
  <div class="row">
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_wallet") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.wallet }} </h2>                    
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-success icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_card") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.card }} </h2>                     
                          </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-info icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
  <div class="row">
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("admin_commission") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.admin_commision }} </h2>                     
                          </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-dark-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-dark icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("owner_earnings") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.driver_commision }} </h2>                     
                          </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-primary icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
</Bcol>
<BCol xl="6" md="6">
    <BCard no-body>
    <BCardBody class="p-0">
      <BRow class="g-0">
        <BCol xxl="12">
          <div class="">
            <BCardHeader class="align-items-center d-flex">
              <BCardTitle class="mb-0 flex-grow-1">{{ $t("overall_earnings") }}</BCardTitle>
            </BCardHeader>
            <apexchart class="apex-charts" height="350" dir="ltr" :series="overall" :options="overallChartOptions"></apexchart>
          </div>
        </BCol>       
      </BRow>
    </BCardBody>
  </BCard>
  </BCol>
</BRow>
  </Layout>
</template>