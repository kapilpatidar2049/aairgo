<script>
import Layout from "@/Layouts/main.vue";
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from "@/Components/page-header.vue";
import ApexCharts from "apexcharts";
import Warning from "@/Components/warning.vue";
import { onMounted, watch, computed } from "vue";
import { useI18n } from 'vue-i18n';
import { layoutComputed } from "@/state/helpers";
import { mapGetters } from 'vuex';
import { ref } from "vue";
import axios from "axios";
import { useSharedState } from '@/composables/useSharedState';

export default {

  computed: {
    ...layoutComputed,
    ...mapGetters(['permissions']),
    layoutType: {
      get() {
        return this.$store ? this.$store.state.layout.layoutType : {} || {};
      },
    },
  },
  components: {
    Layout,
    PageHeader,
    Head,
    Link,
    Warning
  },
  setup(props){
    const { selectedLocation } = useSharedState();
    const { t } = useI18n();
    const filter = useForm({
        service_location_id : selectedLocation.value,
    });

    const userData = ref({});
    const referData = ref({});
    const dashboardData = ref({});
    const driverData = ref({});

    const fetchAllData = async()=>{
      try {
        const params = filter.data();
        const response = await axios.get(`/referral-dashboard/fetch`, { params })
        userData.value = response.data.userData;
        driverData.value = response.data.driverData;
        referData.value = response.data.referralData;
        dashboardData.value = response.data.dashboardData;

        if(referData.value.total_drivers_rise <= 0){
          referData.value.total_drivers_change = `${referData.value.total_drivers_rise} %`;
        }else{
          referData.value.total_drivers_change = `+${referData.value.total_drivers_rise} %`;
        }

        if(referData.value.total_users_rise <= 0){
          referData.value.total_users_change = `${referData.value.total_users_rise} %`;
        }else{
          referData.value.total_users_change = `+${referData.value.total_users_rise} %`;
        }

        if(referData.value.referrals_rise <= 0){
          referData.value.referrals_change = `${referData.value.referrals_rise} %`;
        }else{
          referData.value.referrals_change = `+${referData.value.referrals_rise} %`;
        }
        
        if(referData.value.referral_earning_rise <= 0){
          referData.value.referral_earning_change = `${referData.value.referral_earning_rise} %`;
        }else{
          referData.value.referral_earning_change = `+${referData.value.referral_earning_rise} %`;
        }

      } catch (error) {
        console.error(error);
      }
    }

    const initialiseDashboardData = async() => {
      
        await fetchAllData();

        // ==== USER CHART ==== //
        const userDonutOptions = {
          series: [userData.value.non_referred_users ?? 0, userData.value.referred_users ?? 0],
          labels: ["Normal User", "Referral User"],
          chart: { type: "donut", height: 300 },
          plotOptions: { pie: { donut: { size: `${(userData.value.referred_users ?? 0)/(userData.value.non_referred_users ?? 1)}%` } } },
          dataLabels: { enabled: false },
          stroke: { width: 0 },
          legend: {
            position: "bottom",
            horizontalAlign: "center",
            markers: { width: 20, height: 6, radius: 2 },
            itemMargin: { horizontal: 12, vertical: 0 },
          },
          colors: ["#0d6efd", "#ffc107"],
        };
        new ApexCharts(document.querySelector("#userDonutChart"), userDonutOptions).render();

        const userAreaOptions = {
          series: [
            {
              name: "User Referrals",
              data: userData.value.referred_data,
            },
          ],
          chart: { type: "area", height: 300, toolbar: { show: false } },
          dataLabels: { enabled: false },
          stroke: { curve: "smooth", width: 3 },
          xaxis: {
            categories: [t("jan"),t("feb"),t("mar"),t("apr"),t("may"),t("jun"),t("jul"),t("aug"),t("sep"),t("oct"),t("nov"),t("dec")],
          },
          yaxis: {
            labels: { formatter: (value) => value.toFixed(0) },
            tickAmount: 5,
            min: 0,
            max: Math.max(...userData.value.referred_data),
          },
          colors: ["#198754"],
          fill: {
            type: "gradient",
            gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1, stops: [0, 90, 100] },
          },
          grid: { borderColor: "#f1f1f1" },
        };
        new ApexCharts(document.querySelector("#userAreaChart"), userAreaOptions).render();

        // ==== DRIVER CHART ==== //
        const driverDonutOptions = {
          series: [driverData.value.non_referred_users ?? 0, driverData.value.referred_users ?? 0],
          labels: ["Normal Driver", "Referral Driver"],
          chart: { type: "donut", height: 300 },
          plotOptions: { pie: { donut: { size: `${(driverData.value.referred_users ?? 0)/(driverData.value.non_referred_users ?? 1)}%` } } },
          dataLabels: { enabled: false },
          stroke: { width: 0 },
          legend: {
            position: "bottom",
            horizontalAlign: "center",
            markers: { width: 20, height: 6, radius: 2 },
            itemMargin: { horizontal: 12, vertical: 0 },
          },
          colors: ["#6610f2", "#20c997"],
        };
        new ApexCharts(document.querySelector("#driverDonutChart"), driverDonutOptions).render();
        const driverAreaOptions = {
          series: [
            {
              name: "Driver Referrals",
              data: driverData.value.referred_data,
            },
          ],
          chart: { type: "area", height: 300, toolbar: { show: false } },
          dataLabels: { enabled: false },
          stroke: { curve: "smooth", width: 3 },
          xaxis: {
            categories: [t("jan"),t("feb"),t("mar"),t("apr"),t("may"),t("jun"),t("jul"),t("aug"),t("sep"),t("oct"),t("nov"),t("dec")],
          },
          yaxis: {
            labels: { formatter: (value) => value.toFixed(0) },
            tickAmount: 5,
            min: 0,
            max: Math.max(...driverData.value.referred_data),
          },
          colors: ["#0dcaf0"],
          fill: {
            type: "gradient",
            gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1, stops: [0, 90, 100] },
          },
          grid: { borderColor: "#f1f1f1" },
        };
        new ApexCharts(document.querySelector("#driverAreaChart"), driverAreaOptions).render();
      };

      watch (()=>selectedLocation.value, (value) => {
        if(value){
            filter.service_location_id = value;
            fetchAllData();
        }
      })

      onMounted(() => {
        initialiseDashboardData();
      });
      return {
        referData,
        dashboardData,
      }

  },
  components: { Layout, PageHeader },
};
</script>

<template>
  <Layout>
    <Head :title="$t('referral_dashboard')" />
    <PageHeader :title="$t('referral_dashboard')" pageTitle="Referral Dashboard" />

    <div class="container-fluid py-3">
      <!-- Cards -->
      <div class="row g-3">
        <div class="col-md-3">
          <div class="card card-animate">
            <div class="card-body">
              <p class="text-muted text-uppercase fw-medium mb-2">{{ $t('total_drivers') }}</p>
              <h4 class="fw-bold">{{ referData.total_drivers }}</h4>
              <p :class="{'text-success': referData.total_drivers_rise >= 0, 'text-danger': referData.total_drivers_rise < 0,'mb-0':true}">
                <i :class="(referData.total_drivers_rise < 0) ? 'ri-arrow-down-line' : 'ri-arrow-up-line'"></i>
                {{ referData.total_drivers_change }}
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-animate">
            <div class="card-body">
              <p class="text-muted text-uppercase fw-medium mb-2">{{ $t('total_users') }}</p>
              <h4 class="fw-bold">{{ referData.total_users }}</h4>
              <p :class="{'text-success': referData.total_users_rise >= 0, 'text-danger': referData.total_users_rise < 0,'mb-0':true}">
                <i :class="referData.total_users_rise < 0 ? 'ri-arrow-down-line' : 'ri-arrow-up-line'"></i>
                {{ referData.total_users_change }}
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-animate">
            <div class="card-body">
              <p class="text-muted text-uppercase fw-medium mb-2">{{ $t('active_referrals') }}</p>
              <h4 class="fw-bold">{{ referData.active_referrals }}</h4>
              <p :class="{'text-success': referData.referrals_rise >= 0, 'text-danger': referData.referrals_rise < 0,'mb-0':true}">
                <i :class="(referData.referrals_rise < 0) ? 'ri-arrow-down-line' : 'ri-arrow-up-line'"></i>
                {{ referData.referrals_change }}
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-animate">
            <div class="card-body">
              <p class="text-muted text-uppercase fw-medium mb-2">{{ $t('referral_earning') }}</p>
              <h4 class="fw-bold">{{ dashboardData.currency_symbol+' '+referData.referral_earning }}</h4>
              <p :class="{'text-success': referData.referral_earning_rise >= 0, 'text-danger': referData.referral_earning_rise < 0,'mb-0':true}">
                <i :class="referData.referral_earning_rise < 0 ? 'ri-arrow-down-line' : 'ri-arrow-up-line'"></i>
                {{ referData.total_users_change }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <BCard>
  <BcardBody>
    <h4>{{ $t('user_referral') }}</h4>
      </BcardBody>
    </BCard>

      <BCard v-if="dashboardData.user_leaderboard?.length">
         <BcardBody>
            <div class="row row-cols-xl-5 row-cols-lg-3 row-cols-md-2 row-cols-1 mt-5">
                <div class="col" v-for="(user, index) in dashboardData.user_leaderboard" :key="index">
                    <div class="card">
                        <div class="card-body text-center">
                            <p class="avatar-md rounded-circle object-fit-cover mt-n5 img-thumbnail border-success mx-auto d-block position-relative"><h2 class="rank">{{ index + 1 }}</h2></p>
                            <a class="mt-5">
                              <img :src="user.profile_picture" alt="" class="avatar-md rounded-circle object-fit-cover  img-thumbnail border-light mx-auto d-block">
                                <h5 class="mt-2 mb-1">{{ user.name }}</h5>
                            </a>
                            <p class="text-muted mb-2"> {{ user.referral_count }} {{ $t('referrals') }} </p>
                        </div>
                    </div>
                </div>
            </div>
          </BcardBody>
        </BCard>
     

      <!-- USER CHARTS -->
      <div class="row g-3 mt-3">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ $t('user_referral_overview') }}</h5>
            </div>
            <div class="card-body">
              <div id="userDonutChart"></div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ $t('user_referral') }}</h5>
            </div>
            <div class="card-body">
              <div id="userAreaChart"></div>
            </div>
          </div>
        </div>
      </div>
      <BCard>
  <BcardBody>
    <h4>{{ $t('driver_referral') }}</h4>
      </BcardBody>
    </BCard>
      <!-- DRIVER -->
      <BCard v-if="dashboardData.driver_leaderboard?.length">
         <BcardBody>
            <div class="row row-cols-xl-5 row-cols-lg-3 row-cols-md-2 row-cols-1 mt-5">
                <div class="col" v-for="(user, index) in dashboardData.driver_leaderboard" :key="index">
                    <div class="card">
                        <div class="card-body text-center">
                            <p class="avatar-md rounded-circle object-fit-cover mt-n5 img-thumbnail border-success mx-auto d-block position-relative"><h2 class="rank">{{ index + 1 }}</h2></p>
                            <a class="mt-5">
                              <img :src="user.profile_picture" alt="" class="avatar-md rounded-circle object-fit-cover  img-thumbnail border-light mx-auto d-block">
                                <h5 class="mt-2 mb-1">{{ user.name }}</h5>
                            </a>
                            <p class="text-muted mb-2"> {{ user.referral_count }} {{ $t('referrals') }} </p>
                        </div>
                    </div>
                </div>
            </div>
          </BcardBody>
        </BCard>

      <!-- DRIVER CHARTS -->
      <div class="row g-3 mt-3">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ $t('driver_referral') }}</h5>
            </div>
            <div class="card-body">
              <div id="driverDonutChart"></div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ $t('driver_referral') }}</h5>
            </div>
            <div class="card-body">
              <div id="driverAreaChart"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>