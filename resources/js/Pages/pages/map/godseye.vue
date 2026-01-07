<script>
import { Head } from '@inertiajs/vue3';
import { ref, watch, onMounted } from "vue";
import { useI18n } from 'vue-i18n';
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import googleMap from '@/Components/googleMap.vue';
import { useSharedState } from '@/composables/useSharedState';

export default {
    components: {
        Head,
        Layout,
        PageHeader,
        Multiselect,
        googleMap,
    },
    props: {
        firebaseSettings: Object,
        map_key: String,
        service_location: Array,
        vehicle_type: Array,
        baseUrl: String,
        default_location:Object,
        app_for:String,
    },
    setup(props) {
        const { t } = useI18n();
        // Reactive data for filters
        const selectedServiceLocations = ref(null);
        const selectedVehicleTypes = ref(null);
        const selectedDriverModes = ref([]);
        const { selectedLocation } = useSharedState();

        // Options for filters
        const serviceLocations = ref(props.service_location.map(loc => ({ value: loc.id, label: loc.name })));
        const vehicleTypes = ref(props.vehicle_type.map(type => ({ value: type.id, label: type.name })));

        // Map and related variables
        const driverMarkers = ref({});

        const clearFilters = () => {
            selectedVehicleTypes.value = null;
            selectedDriverModes.value = [];
            fetchNearbyDrivers();
        }
        const applyFilters = () => {
            fetchNearbyDrivers();
        }

        // Fetch drivers and update markers
        const fetchNearbyDrivers = async() => {
            const driversRef = firebase.database().ref('drivers');

            driversRef.once('value', (snapshot) => {
                const drivers = snapshot.val();

                if (!drivers) return;

                driverMarkers.value = {};

                Object.keys(drivers).forEach(driverId => {
                    const driver = drivers[driverId];
                    const driverGeoHash = driver.g;
                    const decodedDriver = decodeGeohash(driverGeoHash);

                    if (decodedDriver && driver.service_location_id) {
                        // Filter based on selected service location and vehicle type
                        let matchesServiceLocation = false;
                        if(serviceLocations.value.find(loc=>loc.value == driver.service_location_id)){
                            matchesServiceLocation = true;
                        }
                        if(selectedServiceLocations.value){
                            matchesServiceLocation = selectedServiceLocations.value == driver.service_location_id ?? false;
                        }

                        let matchesVehicleType = Array.isArray(driver.vehicle_types);
                        if(selectedVehicleTypes.value && Array.isArray(driver.vehicle_types) && driver.vehicle_types.length>0){
                            matchesVehicleType = (Array.isArray(driver.vehicle_types) && driver.vehicle_types.some(type => selectedVehicleTypes.value === type)) ?? false;
                        }

                        let active = selectedDriverModes.value.length>0 ? selectedDriverModes.value.includes("online") : true;
                        let onride = selectedDriverModes.value.length>0 ? selectedDriverModes.value.includes("onride") : true;
                        let offline = selectedDriverModes.value.length>0 ? selectedDriverModes.value.includes("offline") : true;

                        let flag=false;
                        if(driver.hasOwnProperty('is_active') && driver.hasOwnProperty('is_available')){
                            if (driver.is_active ==1 && driver.is_available==true && active) {
                                flag = true;
                            } else if (driver.is_active == 1 && driver.is_available==false && onride) {
                                flag = true;
                            } else {
                                if(offline){
                                    flag = true;
                                }
                            }
                        }
                        if (matchesServiceLocation && matchesVehicleType && flag) {

                            const mobile = props.app_for == 'demo' ? "*********" : driver.mobile;

                            driverMarkers.value[driver.id] = {
                                lat: decodedDriver.lat,
                                lng: decodedDriver.lon,
                                type_icon: driver.vehicle_type_icon,
                                name: driver.name,
                                mobile: mobile,
                            };

                        }
                    }
                });
            });
        };

        // Decode geohash to latitude and longitude
        const decodeGeohash = (geohash) => {
            const BASE32 = '0123456789bcdefghjkmnpqrstuvwxyz';
            const BITS = [16, 8, 4, 2, 1];
            let isEven = true;
            let latMin = -90,
                latMax = 90;
            let lonMin = -180,
                lonMax = 180;
            let lat, lon;

            if (geohash) {
                for (let i = 0; i < geohash.length; i++) {
                    let c = geohash.charAt(i);
                    let cd = BASE32.indexOf(c);
                    for (let j = 0; j < 5; j++) {
                        let mask = BITS[j];
                        if (isEven) {
                            let lonMid = (lonMin + lonMax) / 2;
                            if (cd & mask) {
                                lonMin = lonMid;
                            } else {
                                lonMax = lonMid;
                            }
                        } else {
                            let latMid = (latMin + latMax) / 2;
                            if (cd & mask) {
                                latMin = latMid;
                            } else {
                                latMax = latMid;
                            }
                        }
                        isEven = !isEven;
                    }
                }
                lat = (latMin + latMax) / 2;
                lon = (lonMin + lonMax) / 2;
                return { lat: lat, lon: lon };
            }
        };



        watch(selectedLocation, (value)=> {
            if(value!== 'all'){
                selectedServiceLocations.value = value;
            }
            fetchNearbyDrivers();
        });
        
        // Watch filters and update map when they change
        watch([selectedVehicleTypes, selectedDriverModes], () => {
            fetchNearbyDrivers();
        }),
        onMounted(async() => {

            if(selectedLocation.value!== 'all'){
                selectedServiceLocations.value = selectedLocation.value;
            }
            var firebaseConfig = {
                apiKey: props.firebaseSettings['firebase_api_key'],
                authDomain: props.firebaseSettings['firebase_auth_domain'],
                databaseURL: props.firebaseSettings['firebase_database_url'],
                projectId: props.firebaseSettings['firebase_project_id'],
                storageBucket: props.firebaseSettings['firebase_storage_bucket'],
                messagingSenderId: props.firebaseSettings['firebase_messaging_sender_id'],
                appId: props.firebaseSettings['firebase_app_id'],
            };
            if(!firebase.apps.length){
                firebase.initializeApp(firebaseConfig);
            }

            fetchNearbyDrivers();
            const refresh = localStorage.getItem('refreshMethod') ?? 0; 
            refresh_Method.value = refresh;

            applyRefreshMethod(refresh);

        });

        const refresh_Method = ref();
        const refresh_btn = ref(false);
        const myInterval = ref(null);

        const refreshMethod  = async(refreshvalue) =>{            
            localStorage.setItem('refreshMethod', refreshvalue);
            applyRefreshMethod(refreshvalue);

        }
        const refreshBtn = async ()=>{
            await fetchNearbyDrivers();            
        }
        const applyRefreshMethod = (refresh) => {

            // clear existing interval
            if (myInterval.value) {
                clearInterval(myInterval.value);
                myInterval.value = null;
            }

            if (refresh == 0) {
                refresh_btn.value = false;

                // start interval
                myInterval.value = setInterval(async () => {
                    await fetchNearbyDrivers();
                }, 1000);

            } else {
                refresh_btn.value = true;                
                clearInterval(myInterval.value);
                myInterval.value = null;
            }
        };


        return {
            serviceLocations,
            vehicleTypes,
            selectedServiceLocations,
            selectedDriverModes,
            clearFilters,
            applyFilters,
            selectedVehicleTypes,
            driverMarkers,
            refreshMethod,
            refresh_Method,
            refresh_btn,
            refreshBtn
        };
    },
};
</script>



<template>
    <Layout>

        <Head :title="$t('gods-eye')" />
        <PageHeader :title="$t('gods-eye')" :pageTitle="$t('map')" />
        <BRow>
    <BCol lg="12">
      <BCard no-body id="tasksList">
        <BCardHeader class="border-0">
          <h4>{{$t("filters")}}</h4>
          <div class="row">

            <div class="col-12 col-lg-4">
              <div class="mb-3">
                <label for="select_driver_mode" class="form-label">{{$t("drivers")}}</label>
                <Multiselect 
                  v-model="selectedDriverModes"
                  id="select_driver_mode" 
                  mode="tags" 
                  :close-on-select="false"
                  :searchable="true" 
                  @change="applyFilters"
                  :create-option="false"
                  :options="[
                    { value: 'offline', label: $t('offline') },
                    { value: 'online', label: $t('online') },
                    { value: 'onride', label: $t('onride') },
                  ]"
                  :placeholder="$t('select_mode')"
                />
              </div>
            </div>

            <div class="col-12 col-lg-4">
              <div class="mb-3">
                <label for="vehicle_type" class="form-label">{{$t("vehicle_types")}}</label>
                <Multiselect
                    v-model="selectedVehicleTypes"
                    :options="vehicleTypes"
                    label="label"
                    track-by="value"
                    multiple
                    close-on-select
                    :placeholder="$t('select_vehicle_types')"
                />
              </div>
            </div>            
            <div class="col-sm-2">
                <div>
                    <div class="mb-3">
                    <label for="type" class="form-label" >{{$t("refresh_method")}}
                        <span class="text-danger">*</span>
                    </label>
                    <select id="type" class="form-select" v-model="refresh_Method" @change="refreshMethod(refresh_Method)">
                        <option disabled value="">{{ $t("select_refresh_method") }}</option>
                        <option  value="0">{{ $t("automatic") }}</option>
                        <option  value="1">{{ $t("manual") }}</option>
                    </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-2" v-if="refresh_btn">
                <div class="mb-3 mt-4">
                    <BButton type="button" variant="primary" class="btn btn-md" @click="refreshBtn()">
                        <i class="bx bx-refresh"></i> {{$t("refresh")}}
                    </BButton>
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex gap-1">
              <BButton type="button" variant="success" class="btn btn-md" @click="applyFilters">{{$t("apply")}}</BButton>
              <BButton type="button" variant="danger" class="btn btn-md" @click="clearFilters">{{$t("clear")}}</BButton>
            </div>
          </div>
        </BCardHeader>
        <BCardBody class="border border-dashed border-end-0 border-start-0">
          <div id="map" style="height: 400px;">
            <googleMap
                :baseUrl="baseUrl"
                :default_location="default_location"
                :nearbyDrivers="driverMarkers"
                :map_key="map_key"
                :libraries="['marker']"
            >
            {{$t("map_loading")}}
            </googleMap>
          </div>
        </BCardBody>
      </BCard>
    </BCol>
  </BRow>
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
