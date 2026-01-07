<script>
import { Link, Head, router, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/dispatchmain.vue";
import PageHeader from "@/Components/page-header.vue";
import Swal from "sweetalert2";
import { ref, watch, reactive, computed, onMounted, nextTick } from "vue";
import axios from "axios";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Scrollbar } from "swiper/modules";
import FormValidation from "@/Components/FormValidation.vue";
import debounce from "lodash/debounce";
import { mapGetters } from "vuex";
import { useI18n } from "vue-i18n";
import googleMap from '@/Components/googleMap.vue';
import Multiselect from "@vueform/multiselect";

export default {
    data() {
        return {
            rightOffcanvas: false,
            isPocSame: false, // checkbox state
            form: {
                is_later: 0,
                name: "",
                mobile: "",
                drop_poc_name: "",
                drop_poc_mobile: "",
            },
            dateTimeConfig: {
                enableTime: true,
                dateFormat: "Y-m-d H:i:ss",
                minDate: this.getMinTime(),
            },
        };
    },

    watch: {
        isPocSame(newVal) {
            if (newVal) {
                this.form.drop_poc_name = this.form.name;
                this.form.drop_poc_mobile = this.form.mobile;
            } else {
                this.form.drop_poc_name = "";
                this.form.drop_poc_mobile = "";
            }
        },
        // Watch for changes in personal info to update POC if the checkbox is checked
        "form.name"(newVal) {
            if (this.isPocSame) {
                this.form.drop_poc_name = newVal;
            }
        },
        "form.mobile"(newVal) {
            if (this.isPocSame) {
                this.form.drop_poc_mobile = newVal;
            }
        },
    },

    components: {
        Layout,
        PageHeader,
        Head,
        googleMap,
        Multiselect,
        Link,
        Scrollbar,
        FormValidation,
        flatPickr,
    },
    computed: {
        ...mapGetters(["permissions"]),
    },
    props: {
        countries: Array,
        default_flag: String,
        default_dial_code: String,
        successMessage: String,
        alertMessage: String,
        validate: Function,
        ride_type_for_ride: Array,
        goodsTypes: Array,
        firebaseSettings: Object,
        map_key: String,
        app_modules: Array,
        schedule_a_ride: Number,
        enable_ride_without_destination: Boolean,
        baseUrl: String,
        default_location:Object,
    },

    methods: {
        timer() {
            let timerInterval;
            Swal.fire({
                title: "Booking alert!",
                html: "Your Ride has been Booked <b></b> Successfully.",
                timer: 2000,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                    timerInterval = setInterval(() => {
                        Swal.getContent().querySelector("b").textContent =
                            Swal.getTimerLeft();
                    }, 100);
                },
                didClose: () => {
                    clearInterval(timerInterval);
                },
            }).then((result) => {
                if (
                    /* Read more about handling dismissals below */
                    result.dismiss === Swal.DismissReason.timer
                ) {
                    console.log("I was closed by the timer"); // eslint-disable-line
                }
            });
        },
        getMinTime() {
            const now = new Date(); //get the today date
            const scheduleMinutes = this.schedule_a_ride
                ? parseInt(this.schedule_a_ride)
                : 0; //get the schedule_a_ride
            now.setMinutes(now.getMinutes() + scheduleMinutes); //add the schedule_a_ride to the today date
            now.setSeconds(0);

            const formattedTime =
                now.getFullYear() +
                "-" +
                String(now.getMonth() + 1).padStart(2, "0") +
                "-" +
                String(now.getDate()).padStart(2, "0") +
                " " +
                String(now.getHours()).padStart(2, "0") +
                ":" +
                String(now.getMinutes()).padStart(2, "0") +
                ":" +
                "00"; // Always set seconds to 00

            console.log("Formatted Time:", formattedTime);
            return formattedTime;
            // return now;
        },
    },

    computed: {

        dateTimeConfigReturnTime() {
            return {
                enableTime: true,
                dateFormat: "Y-m-d H:i:ss",
                minDate: this.getMinReturnTime(), // Call the method here (minDate and Time for the return time)
            };
        },
    },

    setup(props) {
        const { t } = useI18n();
        const selectedCountry = ref({
            dial_code: props.default_dial_code || "",
            flag: props.default_flag || "",
        });
        const scrollContainer = ref(null);
        const enableBooking = ref(false);
        const showInfo = ref('personal');

        const form = useForm({
            mobile: "",
            name: null,
            user_id: null,
            pick_address: "",
            drop_address: "",
            terminal: null,
            enterance: null,
            pick_lat: "",
            pick_lng: "",
            drop_lat: null,
            drop_lng: null,
            vehicle_type: null,
            distance: 0,
            duration: 0,
            payment_opt: 1,
            poly_line: '',
            goods_type_id: null,
            transport_type: "",
            pickup_poc_name: null,
            drop_poc_name: null,
            goods_type_quantity: null,
            is_later: 0,
            assign_method: 0,
            stops: "[]",
            return_time: null,
            is_round_trip: 0,
            is_out_station: 0,
            requested_eta_amount:0,
            is_rental: 0,
            rental_package_id: null,
            ride_type: "regular",
            trip_start_time: null,
            country: props.default_dial_code,
            drop_poc_mobile: null,
            is_pet_available: 0,
            is_luggage_available: 0,
            module: '',
            preferences:[],
            module_name: '',
            location: false,
            miscellaneous: false,
        });

        const unit = ref('-');
        // For Airport
        const showAiportTerminal = ref(false);
        const showTrainStationEnterance = ref(false);

        // const recentSearches = ref([]);
        const manageStopMarker = () => {

            stopovers.forEach((stop,index)=>{
                if(stop.latitude && stop.longitude) {
                    stopMarkers.value[index] = { lat: stop.latitude, lng:stop.longitude };
                }else{
                    delete stopMarkers.value[index];
                }
            });

        };

        const validationRules = ref({});
        const errors = ref({});
        const validationRef = ref(null);
        const vehicleTypes = ref([]);
        const packageTypes = ref([]);
        const rentalVehicleTypes = ref([]);
        const stopMarkers = ref([]);
        const pickupMarker = ref(null);
        const dropMarker = ref(null);
        const preferences = ref({});

        const etaParam = useForm({
            pick_lat: "",
            pick_lng: "",
            drop_lat: "",
            drop_lng: "",
            distance: 0,
            distance_in_unit: 0,
            duration: 0,
        });

        // Reactive ref to store formatted time
        const formattedDuration = ref("0m");

        // Watch for changes in etaParam.duration and update formattedDuration
        watch(() => etaParam.duration, (newDuration) => {
            const hours = Math.floor(newDuration / 60);
            const minutes = newDuration % 60;
            formattedDuration.value = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
        });

        const serviceVerify = async () => {
            const address = [];
            const pick_location = { latitude: etaParam.pick_lat, longitude: etaParam.pick_lng,};
            address.push(pick_location);
            
           /* const drop_location = {latitude: eteParam.drop_lat, longitude: etaParam.drop_lng};
            address.push(drop_location); */

            if (Array.isArray(stopovers)) {
                stopovers.forEach((stop) => {
                    const stop_location = { latitude: stop.latitude, longitude: stop.longitude, };
                    address.push(stop_location);
                });
            }

            if (etaParam.drop_lat) {
                const drop_location = { latitude: etaParam.drop_lat, longitude: etaParam.drop_lng, };
                address.push(drop_location);
            }

            const payload = { address: address, ride_type: form.ride_type, };

            try {
                await axios.post("/dispatch/serviceVerify", payload );
                return true;
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    errors.value = error.response.data.errors;
                } else if (error.response && error.response.status === 500) {
                    if ( error.response.data.message == "service not available with this location" ) {
                        errors.value.pick_address = [t("service_unavailable")];
                    } else if ( error.response.data.message == "Outstation service not available within this location" ) {
                        delete errors.value.pick_address;
                        errors.value.drop_address = [t("outstation_service_unavailable")];
                    } else if ( error.response.data.message == "Pick up and Drop should not be in the same zone") {
                        delete errors.value.pick_address;
                        errors.value.drop_address = [t("same_zone_outstation_unavailable")];
                    } else {
                        const data = error.response.data;
                        delete errors.value.pick_address;
                        delete errors.value.drop_address;
                        address.forEach((location, key) => {
                            if (!errors.value.stop) {
                                errors.value.stop = {};
                            }
                            if (key !== 0) {
                                if (key !== address.length) {
                                    if (data[key]["available"]) {
                                        errors.value.stop[key] = [
                                            t("ride_service_unavailable"),
                                        ];
                                    } else {
                                        delete errors.value.stop[key];
                                    }
                                } else {
                                    if (data[key]["available"]) {
                                        errors.value.drop_address = [
                                            t("ride_service_unavailable"),
                                        ];
                                    } else {
                                        delete errors.value.drop_address;
                                    }
                                }
                            }
                        });
                    }
                    if(errors.value?.stop && Object.keys(errors.value.stop).length == 0){
                        delete errors.value.stop;
                    }
                } else {
                    console.error(t("error_creating_vehicle_price"), error);
                    form.location = false;
                    alertMessage.value = t(
                        "failed_to_make_booking_contact_admin"
                    );
                }
                return false;
            }
        };
        const service = ref(false);
        const route = ref(false);
        const locationData = ref({});
        const areLocationsEqual = (prevLocationData,currentLocationData) => {

            if (!prevLocationData || !currentLocationData) return false;

            // Compare pickup
            if (prevLocationData.pickup?.lat !== currentLocationData.pickup?.lat || prevLocationData.pickup?.lng !== currentLocationData.pickup?.lng) return false;

            // Compare drop
            if (prevLocationData.drop?.lat !== currentLocationData.drop?.lat || prevLocationData.drop?.lng !== currentLocationData.drop?.lng) return false;

            // Compare stops (length + each lat/lng)
            const stopsA = prevLocationData.stop || {};
            const stopsB = currentLocationData.stop || {};


            if (stopsA.length !== stopsB.length) return false;

            for (let i = 0; i < stopsA.length; i++) {
                if (stopsA[i].lat !== stopsB[i].lat || stopsA[i].lng !== stopsB[i].lng) {
                return false;
                }
            }

            return true;
        }
        const shouldHideMiscellaneous = ref(false);

        const bookValidate = async (info,confirmLocation = false) => {
            if(!info){
                info = showInfo.value;
            }else{
                if(showInfo.value != info){
                    showInfo.value = info;
                    return false;
                }
            }
            errors.value = {};

            
            if(info == 'personal'){
                if(!form.mobile) {
                    errors.value.mobile = [t("required")];
                } else {
                    delete errors.value.mobile;
                }
                if(!form.name) {
                    errors.value.name = [t("required")];
                } else {
                    delete errors.value.name;
                }
            }

            if(info == 'personal'){
                if(Object.keys(errors.value).length > 0){
                    console.log('personal',errors.value);
                    showInfo.value = 'personal';
                    return false;
                }else{
                    showInfo.value = 'ride';
                    return false;
                }
            }

            if (!form.is_later != 0 && !form.is_later != 1) {
                errors.value.is_later = [t("required")];
            } else {
                delete errors.value.is_later;
            }

            if (!form.module) {
                errors.value.module = [t("required")];
            } else {
                delete errors.value.module;
            }

            if(info == 'ride'){
                
                const selectedModule = props.app_modules.find(app_mod => app_mod.id === form.module);
                if(selectedModule){
                    const type = selectedModule.service_type;
                    if (type == "outstation") {
                        form.is_out_station = 1;
                        form.is_later = 1;
                        form.is_rental = 0;
                    } else if (type == "rental") {
                        form.is_out_station = 0;
                        form.is_rental = 1;
                    } else {
                        form.is_out_station = 0;
                        form.is_rental = 0;
                    }
                    if (type == "normal") {
                        form.ride_type = 'regular';
                    } else {
                        form.ride_type = type;
                    }
                    form.module_name = selectedModule.name;
                    form.transport_type = selectedModule.transport_type;
                }
                if(Object.keys(errors.value).length > 0){
                    console.log('ride',errors.value);
                    showInfo.value = 'ride';
                    return false;
                }else{
                    showInfo.value = 'location';
                    return false;
                }
            }

            let currentLocationData = {};
            
            if(info == 'location'){

                if(!etaParam.pick_lat){
                    if (!form.pick_address && form.pick_address.length == 0) {
                        errors.value.pick_address = [t("required")];
                    } else {
                        delete errors.value.pick_address;
                    }
                }else{
                    currentLocationData.pickup = pickupMarker.value;
                }
                if (!form.is_rental) {
                    if ((!props.enable_ride_without_destination || form.is_out_station) && !form.drop_address && form.drop_address.length == 0) {
                        errors.value.drop_address = [t("required")];
                    } else {
                        delete errors.value.drop_address;
                    }
                    if(etaParam.drop_lat && !errors.value.drop_address){
                        currentLocationData.drop = dropMarker.value;
                    }
                } else {
                    form.drop_lat = "";
                    form.drop_lng = "";
                    etaParam.drop_lat = "";
                    etaParam.drop_lng = "";
                    etaParam.distance = 0;
                    etaParam.duration = 0;
                    form.drop_address = "";

                    dropMarker.value=null;
                    form.poly_line='';
                    driverMarkers.value={};

                    if (stopovers.length > 0) {
                        stopovers.forEach((item, index) => {
                            delete stopMarkers.value[index];
                        });
                        stopovers.length = 0;
                    }
                }
                if (stopovers.length > 0) {
                    if(!form.drop_address && form.drop_address.length == 0) {
                        errors.value.drop_address = [t("required")];
                    } else {
                        delete errors.value.drop_address;
                    }
                    stopovers.forEach((item, index) => {
                        if (!errors.value.stop) {
                            errors.value.stop = {};
                        }
                        if (!item.latitude || item.latitude == null) {
                            errors.value.stop[index] = [t("required")];
                        } else {
                            if (errors.value.stop[index]) {
                                delete errors.value.stop[index];
                            }
                        }
                    });

                }
                if(errors.value?.stop && Object.keys(errors.value.stop).length == 0){
                    delete errors.value.stop;
                }

                if(Object.keys(errors.value).length){
                    console.log('location',errors.value);
                    form.location = false;
                    return false;
                }

                if (stopovers.length){
                    currentLocationData.stop = stopovers;
                }

                if(form.is_rental || props.enable_ride_without_destination){
                    route.value = true;
                }
                if(!areLocationsEqual(currentLocationData, locationData.value)){
                    locationData.value = JSON.parse(JSON.stringify(currentLocationData));
                    if(etaParam.pick_lat){
                        service.value = await serviceVerify();
                        if(service.value){
                            await updateRoute();
                            if(form.is_rental){
                                route.value = await loadRentalPack();
                            }
                        }
                    }
                }

                if(etaParam.drop_lat){
                    route.value = form.poly_line?.length ? true : false;
                }

                if(form.location == true || (confirmLocation === true && etaParam.pick_lat && service && route && Object.keys(errors.value).length == 0)){
                    form.location = true;
                    showInfo.value = 'vehicle';
                    return false;
                }else{
                    console.log('location',errors.value);
                    form.location = false;
                    showInfo.value = 'location';
                    return false;
                }
            }

            if(form.location == false){
                showInfo.value = 'location';
                return false;
            }

            if (info == 'vehicle' && !form.vehicle_type) {
                errors.value.vehicle_type = [t("required")];
            } else {
                delete errors.value.vehicle_type;
            }
            if(Object.keys(errors.value).length || !form.location){
                return false;
            }

            // preferences.value = vehicleTypes.value.find(type => type.zone_type_id == form.vehicle_type)?.preference ?? [];
            shouldHideMiscellaneous.value = form.transport_type == 'taxi' && !form.is_later && !form.is_rental && Object.keys(preferences.value)?.length == 0;

            const selectedVehicle = vehicleTypes.value.find(type => type.zone_type_id == form.vehicle_type);

            form.requested_eta_amount = selectedVehicle?.total ?? 0;
            form.vehicleTypeName = selectedVehicle?.name ?? "";

            if(shouldHideMiscellaneous.value ){
                form.miscellaneous = true;
            }else {
                if(info == 'vehicle' && form.miscellaneous == false){
                    showInfo.value = 'miscellaneous';
                    return false;
                }
            }
            if(info == 'miscellaneous' && form.miscellaneous == false){

                if (form.is_later) {
                    if (!form.trip_start_time) {
                        errors.value.trip_start_time = [t("required")];
                    } else {
                        delete errors.value.trip_start_time;
                    }
                }
                if (form.is_out_station) {
                    if (!form.trip_start_time) {
                        errors.value.trip_start_time = [t("required")];
                    } else {
                        delete errors.value.trip_start_time;
                    }
                }
                if (form.is_out_station && form.is_round_trip) {
                    if (!form.return_time) {
                        errors.value.return_time = [t("required")];
                    } else {
                        delete errors.value.return_time;
                    }
                }
                if (form.transport_type == "delivery") {
                    if (!form.goods_type_id) {
                        errors.value.goods_type_id = [t("required")];
                    } else {
                        delete errors.value.goods_type_id;
                    }
                    if (!form.goods_type_quantity) {
                        errors.value.goods_type_quantity = [t("required")];
                    } else {
                        delete errors.value.goods_type_quantity;
                    }
                    if (!form.drop_poc_name) {
                        errors.value.drop_poc_name = [t("required")];
                    } else {
                        delete errors.value.drop_poc_name;
                    }
                    if (!form.drop_poc_mobile) {
                        errors.value.drop_poc_mobile = [t("required")];
                    } else {
                        delete errors.value.drop_poc_mobile;
                    }
                }
                if (form.is_rental) {
                    if (!form.rental_package_id) {
                        errors.value.rental_package_id = [t("required")];
                    } else {
                        delete errors.value.rental_package_id;
                    }
                }
                if(Object.keys(errors.value).length){
                    console.log('miscellaneous',errors.value);
                    showInfo.value = 'miscellaneous';
                    form.miscellaneous = false;
                    return false;
                }else{
                    form.miscellaneous = true;
                }
            }
            console.log('book');
            
            showInfo.value = 'booking';
            enableBooking.value = true;
            return true;
        };

        const modalShow = ref(false);

        const successMessage = ref(props.successMessage || "");
        const alertMessage = ref(props.alertMessage || "");

        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        const stopovers = reactive([]);
        const maxStopovers = 2;

        const addStopover = () => {
            form.location = false;
            if (stopovers.length < maxStopovers) {
                stopovers.push({
                    address: "",
                    latitude: null,
                    longitude: null,
                });
                manageStopMarker();
            } else {
                Swal.fire(
                    t("maximum_stopovers_reached"),
                    t("you_can_add_up_to_5_stopovers_only"),
                    "warning"
                );
            }
        };

        const removeStopover = (index) => {

            // Remove stopover from array
            stopovers.splice(index, 1);

            // Reinitialize autocomplete for remaining stops
            manageStopMarker();
            form.location = false;
            bookValidate('location');
        };
        const driversRef = ref(null);

        const drawRoute = async () => {

            if (etaParam.pick_lat && etaParam.pick_lng) {
                if (form.poly_line.length) {
                    form.poly_line = '';
                }


                pickupMarker.value = {
                  lat:  etaParam.pick_lat,
                  lng:  etaParam.pick_lng
                };

                if (form.is_rental) {
                    return true;
                }
            }
            if (
                !pickupMarker.value ||
                !dropMarker.value
            ) {
                return false;
            }

            const url = 'https://routes.googleapis.com/directions/v2:computeRoutes';

            const requestBody = {
                origin: {
                    location: {
                    latLng: {
                            latitude: pickupMarker.value.lat,
                            longitude: pickupMarker.value.lng
                        }
                    },
                },
                destination: {
                    location: {
                    latLng:{
                            latitude: dropMarker.value.lat,
                            longitude: dropMarker.value.lng
                        }
                    },
                },
                travelMode: 'DRIVE',
                routingPreference: 'TRAFFIC_AWARE',
                computeAlternativeRoutes: false,
                routeModifiers: {
                    avoidTolls: false,
                    avoidHighways: false,
                    avoidFerries: false,
                },
                languageCode: 'en-US',
                units: 'IMPERIAL',
            };

            requestBody.intermediates = stopovers.map((stop) => ({
                location: {latLng: {latitude: stop.latitude,longitude: stop.longitude}},
            }));

            const headers = {
                'Content-Type': 'application/json',
                'X-Goog-Api-Key': props.map_key,
                'X-Goog-FieldMask': 'routes.duration,routes.distanceMeters,routes.polyline.encodedPolyline,routes.viewport',
            };

            axios.post(url, requestBody, { headers })
            .then((response) => {
                const route = response.data.routes?.[0];
                if(route) {

                    etaParam.distance = route.distanceMeters;
                    etaParam.duration = Math.round(parseFloat(route.duration.slice(0,-1)) / 60);

                    if (form.poly_line.length) {
                        form.poly_line = '';
                    }

                    if(route.polyline?.encodedPolyline) {

                        form.poly_line = route.polyline.encodedPolyline;

                    }

                    if (errors.value.map) {
                        delete errors.value.map;
                    }
                    loadVehicleTypes();
                    return true;
                }
                return false;
            })
            .catch(error => {
                form.location = false;
                errors.value.location = t("service_not_available");
                errors.value.map = t("service_not_available");
                console.error('Error:', error);
                return false;
            });
        };

        const pickSuggestions = ref([]);
        const dropSuggestions = ref([]);
        const stopSuggestions = ref([]);

        const handleInput = (input,index=null) => {
            if (input == "pickup") {
                if (form.pick_address.length < 3) {
                    pickSuggestions.value = [];
                } else {
                    setTimeout(() => {
                        fetchAutocompleteResults(form.pick_address, input);
                    }, 300);
                }
            } else if (input == "drop") {
                if (form.drop_address.length < 3) {
                    dropSuggestions.value = [];
                } else {
                    setTimeout(() => {
                        fetchAutocompleteResults(form.drop_address, input);
                    }, 300);
                }
            } else if (input == "stop") {
                if (stopovers[index]?.address.length < 3) {
                    stopSuggestions.value[index] = [];
                } else {
                    setTimeout(() => {
                        fetchAutocompleteResults(stopovers[index]?.address, input,index);
                    }, 300);
                }
            }
        };
        const fetchAutocompleteResults = (search, type, index = null) => {
            const apiUrl = `https://places.googleapis.com/v1/places:autocomplete`;
            const headers = {
                "Content-Type": "application/json",
                "X-Goog-Api-Key": props.map_key,
                "X-Goog-FieldMask":
                    "suggestions.placePrediction.placeId,suggestions.placePrediction.place,suggestions.placePrediction.text",
            };
            const requestData = {
                input: search,
            };

            fetch(apiUrl, {
                method: "POST",
                headers: headers,
                body: JSON.stringify(requestData),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.suggestions?.length > 0) {
                        if (type == "pickup") {
                            pickSuggestions.value = data.suggestions
                                .filter(
                                    (suggestion) => suggestion.placePrediction
                                )
                                .map((suggestion) => ({
                                    placeId: suggestion.placePrediction.placeId,
                                    formattedAddress: suggestion.placePrediction.text.text,
                                }));
                        } else if (type == "drop") {
                            dropSuggestions.value = data.suggestions
                                .filter(
                                    (suggestion) => suggestion.placePrediction
                                )
                                .map((suggestion) => ({
                                    placeId: suggestion.placePrediction.placeId,
                                    formattedAddress: suggestion.placePrediction.text.text,
                                }));
                        } else if (type == "stop") {
                            stopSuggestions.value[index] = data.suggestions
                                .filter(
                                    (suggestion) => suggestion.placePrediction
                                )
                                .map((suggestion) => ({
                                    placeId: suggestion.placePrediction.placeId,
                                    formattedAddress: suggestion.placePrediction.text.text,
                                }));
                        }
                    }
                })
                .catch((error) => {
                    console.error(
                        "Error fetching autocomplete results:",
                        error
                    );
                });
        };
        const selectSuggestion = async (suggestion, input, index = null) => {
            const headers = {
                "X-Goog-Api-Key": props.map_key,
                "X-Goog-FieldMask": "location",
            };
            fetch(
                `https://places.googleapis.com/v1/places/${suggestion.placeId}?fields=location`,
                {
                    headers: headers,
                }
            )
                .then((response) => response.json())
                .then((data) => {
                    if(input == 'pickup'){
                      etaParam.pick_lat = data.location.latitude;
                      etaParam.pick_lng = data.location.longitude;
                      form.pick_address = suggestion.formattedAddress;

                      const position = {lat: data.location.latitude, lng: data.location.longitude };
                      pickupMarker.value = position;
                      

                      pickSuggestions.value  = [];

                    }else if(input == 'drop') {
                      etaParam.drop_lat = data.location.latitude;
                      etaParam.drop_lng = data.location.longitude;
                      form.drop_address = suggestion.formattedAddress;

                      const position = {lat: data.location.latitude, lng: data.location.longitude };
                      dropMarker.value = position;
                      dropSuggestions.value = [];

                    }else if(input == 'stop') {

                      stopSuggestions.value[index] = [];
                      stopovers[index].address = suggestion.formattedAddress;
                      stopovers[index].latitude = data.location.latitude;
                      stopovers[index].longitude = data.location.longitude;

                    }
                    form.location = false;
                    bookValidate('location',false);
                })
                .catch((error) => {
                    console.error(
                        "Error fetching autocomplete results:",
                        error
                    );
                });
        };


        

        let route_time = true;
        const updateRoute = async () => {
            
            if (route_time) {
                setTimeout(async() => {
                    route_time = true;
                    return await drawRoute();
                }, 1000);
            }
            route_time = false;
        }; // Adjust delay as needed
        // Initialize Map
        const driverMarkers = ref({});

        watch( stopovers, (newVal) => {
          var flag = false;
          newVal.forEach((stop, index) => {
              if (stop.latitutde) {
                  flag = true;
              }
              manageStopMarker();
          });

        if (flag) {
            form.location = false;
            bookValidate('location');
        }
        }, { deep: true } );


        const loadVehicleTypes = async () => {
            rentalVehicleTypes.value = [];
            vehicleTypes.value = [];

            const {
                pick_lat,
                pick_lng,
                drop_lat,
                drop_lng,
                distance,
                duration,
            } = etaParam.data();
            const payload = {
                pick_lat,
                pick_lng,
                distance,
                duration,
            };

            payload.transport_type = form.transport_type;

            if (drop_lat) {
                payload.drop_lat = drop_lat;
            }

            if (drop_lng) {
                payload.drop_lng = drop_lng;
            }
            if (form.is_out_station) {
                payload.is_out_station = 1;

                if (form.trip_start_time) {
                    payload.trip_start_time = form.trip_start_time;
                }
                if (form.return_time) {
                    payload.return_time = form.return_time;
                }
            }
            payload.dispatch = 1;

            if (stopovers.length > 0) {
                payload.stops = JSON.stringify(stopovers);
            }

            try{
              const response = await axios.post(
                  `/dispatch/request/eta`,
                  payload
              );
              vehicleTypes.value = response.data.data;
              if(vehicleTypes.value.length>0){
                  if(vehicleTypes.value[0].unit_in_words == 'MILES'){
                      etaParam.distance_in_unit = etaParam.distance * 0.621371;
                      unit.value = t('miles');
                  }else{
                      etaParam.distance_in_unit = etaParam.distance;
                      unit.value = t('km');
                  }
              }
              showInfo.value = 'location';

              fetchNearbyDrivers(pickupMarker.value);
            }catch(error){
                console.error(errors);
            }
        };

    const handlePickupAddress = (address,type,position,index) => {
        
        if(type == 'pickup'){
        etaParam.pick_lat = position.lat;
        etaParam.pick_lng = position.lng;

        form.pick_address = address;
        pickupMarker.value = position;
        

        form.location = false;
        bookValidate('location');

        }else if(type == 'drop'){
        form.drop_address = address;
        dropMarker.value = position;

        form.location = false;
        bookValidate('location');
        
        }else{
                        
        stopovers[index].address = address;
        stopovers[index].latitude = position.lat;
        stopovers[index].longitude = position.lng;

        form.location = false;
        bookValidate('location');

        }

  };

        const loadRentalPack = async () => {
            rentalVehicleTypes.value = [];
            vehicleTypes.value = [];

            const { pick_lat, pick_lng } = etaParam.data();

            const payload = {
                pick_lat,
                pick_lng,
                transport_type: form.transport_type,
            };

            const response = await axios.post(
                `/dispatch/request/list_packages`,
                payload
            );

            packageTypes.value = response.data.data;
        };
        
        // Helper to calculate bearing between two lat/lngs
        function calculateBearing(lat1, lng1, lat2, lng2) {
            const toRad = deg => deg * Math.PI / 180;
            const toDeg = rad => rad * 180 / Math.PI;

            const dLng = toRad(lng2 - lng1);
            const y = Math.sin(dLng) * Math.cos(toRad(lat2));
            const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) -
                        Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(dLng);

            const angle = Math.atan2(y, x);
            return (toDeg(angle) + 360) % 360; // Normalize to [0, 360)
        }
        const fetchNearbyDrivers = (pickupMarker) => {
          vehicleTypes.value.forEach((vehicleType) => {
            vehicleType.driver_time = null;
          });

          const driversRef = firebase.database().ref("drivers");

          driversRef.on("value", (snapshot) => {
            snapshot.forEach((childSnapshot) => {
              const driver = childSnapshot.val();
              const driverGeoHash = driver.g;
              const driverLocation = decodeGeohash(driverGeoHash);

              if (driverLocation) {
                const distance = calculateDistance(
                  pickupMarker.lat,
                  pickupMarker.lng,
                  driverLocation.lat,
                  driverLocation.lon
                );

                var flag = true;
                if (form.vehicle_type) {
                  flag = false;
                  var type_id = vehicleTypes.value?.find((type) => {
                    return type.zone_type_id === form.vehicle_type;
                  })?.type_id;
                  if (
                    type_id &&
                    driver.vehicle_types &&
                    driver.vehicle_types.includes(type_id)
                  ) {
                    flag = true;
                  }
                }

                if (distance <= 10 && driver.is_available && driver.is_active && flag) {
                  setEtaTime(driver, distance);
                  const prev = driverMarkers.value[driver.id];

                  if (prev) {
                    if (
                      prev.lat !== driverLocation.lat &&
                      prev.lng !== driverLocation.lon
                    ) {
                        driverMarkers.value[driver.id] = {
                            lat: driverLocation.lat,
                            lng: driverLocation.lon,
                            rotation: calculateBearing(prev.lat,prev.lng,driverLocation.lat,driverLocation.lng),
                            type_icon: driver.vehicle_type_icon,
                        };
                    }
                  } else {
                    driverMarkers.value[driver.id] = {
                        lat: driverLocation.lat,
                        lng: driverLocation.lon,
                        rotation: driver.bearing,
                        type_icon: driver.vehicle_type_icon,
                    };
                  }
                } else if (driverMarkers.value[driver.id]) {
                  delete driverMarkers.value[driver.id];
                }
              }
            });
          });
        };


        watch(
            () => form.rental_package_id,
            async (value) => {
                if (value) {
                    const rentalpack = packageTypes.value.find((pack) => {
                        return pack.id === value;
                    });
                    const types = rentalpack.typesWithPrice.data;

                    rentalVehicleTypes.value = types;
                }
            }
        );
        watch(
            () => form.name,
            async (name) => {
                if (form.pick_address) {
                    bookValidate('location');
                }
            }
        );
        watch(
            () => etaParam.pick_lat,
            async (pickLat) => {
                if (pickLat) {
                    form.location = false;
                    bookValidate('location');
                }
            }
        );

        watch(
            () => etaParam.drop_lat,
            async (value) => {
                if (value && etaParam.pick_lat) {
                    form.location = false;
                    bookValidate('location');
                }
            }
        );
        const setEtaTime = (driver, distance) => {
            const time = calculateTime(distance);
            vehicleTypes.value.forEach((vehicleType) => {
                if (
                    driver.vehicle_types &&
                    driver.vehicle_types.includes(vehicleType.type_id) &&
                    (!vehicleType.driver_distance ||
                        vehicleType.driver_distance > distance)
                ) {
                    vehicleType.driver_time = time;
                }
            });
            return true;
        };
        const calculateTime = (distance) => {
            if (distance < 2) {
                return "3 Mins";
            } else if (distance > 2 && distance < 5) {
                return "5 Mins";
            } else if (distance > 5 && distance < 7) {
                return "10 Mins";
            } else {
                return "15 mins";
            }
        };

        const decodeGeohash = (geohash) => {
            const BASE32 = "0123456789bcdefghjkmnpqrstuvwxyz";
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

        const calculateDistance = (lat1, lon1, lat2, lon2) => {
            const R = 6371;
            const dLat = ((lat2 - lat1) * Math.PI) / 180;
            const dLon = ((lon2 - lon1) * Math.PI) / 180;
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos((lat1 * Math.PI) / 180) *
                    Math.cos((lat2 * Math.PI) / 180) *
                    Math.sin(dLon / 2) *
                    Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c;
            return distance;
        };
        // Submit a Booking

        const makeBooking = async () => {
            if (!bookValidate()) {
                return false;
            }

            enableBooking.value = false;

            const { pick_lat, pick_lng, drop_lat, drop_lng, distance, duration, } = etaParam.data();

            if (pick_lat) {
                form.pick_lat = pick_lat;
            }
            if (pick_lng) {
                form.pick_lng = pick_lng;
            }
            if (drop_lat) {
                form.drop_lat = drop_lat;
            }
            if (drop_lng) {
                form.drop_lng = drop_lng;
            }
            if (distance) {
                form.distance = distance;
            }
            if (duration) {
                form.duration = duration;
            }

            form.preferences = JSON.stringify(form.preferences);

            if (stopovers.length > 0) {
                form.stops = JSON.stringify(stopovers);
            }

            try {
                let response;
                
                response = await axios.post( `/dispatch/create-request`, form.data() );
                if (response.data.success === true) {
                    let timerInterval;
                    Swal.fire({
                        title: "Booking Successfull",
                        html: "Your Ride has been Booked Successfully.",
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: () => {
                            Swal.showLoading();
                            timerInterval = setInterval(() => {
                                Swal.getContent().querySelector(
                                    "b"
                                ).textContent = Swal.getTimerLeft();
                            }, 100);
                        },
                        didClose: () => {
                            clearInterval(timerInterval);
                        },
                    }).then((result) => {
                        if ( result.dismiss === Swal.DismissReason.timer ) {
                            console.log("I was closed by the timer");
                        }
                        enableBooking.value = true;
                    });

                    if (form.assign_method == 1) {
                        router.get(
                            "ongoing_request/assign/" + response.data.data.id
                        );
                    }
                    form.reset();
                    etaParam.reset();
                    vehicleTypes.value = [];
                    rentalVehicleTypes.value = [];
                    stopovers.length = 0;
                    pickupMarker.value=null;
                    dropMarker.value=null;
                    form.poly_line='';
                    driverMarkers.value={};
                    route.value = false;
                    service.value = false;
                    showInfo.value='personal';
                } else {
                    alertMessage.value = t( "failed_to_make_booking_contact_admin" );
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    errors.value = error.response.data.errors;
                } else {
                    console.error(t("error_creating_vehicle_price"), error);
                    alertMessage.value = t( "failed_to_make_booking_contact_admin" );
                }
            }
        };

        onMounted(() => {
            var firebaseConfig = {
                apiKey: props.firebaseSettings["firebase_api_key"],
                authDomain: props.firebaseSettings["firebase_auth_domain"],
                databaseURL: props.firebaseSettings["firebase_database_url"],
                projectId: props.firebaseSettings["firebase_project_id"],
                storageBucket: props.firebaseSettings["firebase_storage_bucket"],
                messagingSenderId: props.firebaseSettings["firebase_messaging_sender_id"],
                appId: props.firebaseSettings["firebase_app_id"],
            };
            if (firebase.apps.length == 0) {
                firebase.initializeApp(firebaseConfig);
            }
            driversRef.value = firebase.database().ref("drivers");

        });

        const mobile = ref("");

        const searchQuery = ref("");

        const filteredCountries = computed(() => {
            return props.countries.filter((country) =>
                country.name.toLowerCase().includes(searchQuery.value.toLowerCase())
            );
        });

        const selectCountry = (country) => {
            selectedCountry.value = country;
            form.country = country.dial_code;
        };

        const validateNumber = debounce(async (event) => {
            event.target.value = event.target.value.replace(/[^0-9.]/g, "").replace(/(\..*?)\..*/g, "$1");
            const response = await axios.get(`/dispatch/fetch-user-detail`, { params: { mobile: form.mobile } });

            if (response.data.data) {
                if (form.pick_address) {
                    bookValidate();
                }
                form.name = response.data.data.name;
                form.user_id = response.data.data.id;
            } else {
                form.user_id = null;
                form.name = null;
            }
        }, 300);

        const getMinReturnTime = () => {
            const durationInMinutes = etaParam.duration; // eta duration in minutes
            const trip_start_time = form.trip_start_time; // getting trip_start_time

            // Convert trip_start_time to a Date object
            const startTime = new Date(trip_start_time);

            // Calculate return time by adding trip_start_time + durationInMinutes
            startTime.setMinutes(startTime.getMinutes() + durationInMinutes); // Add durationInMinutes to the start time

            return startTime;
        };

        const preferenceIconUrl = (preference_id) => {
            return preferences.value.find(pref => pref.preference_id == preference_id)?.icon ?? '-';
        }

        const preferenceName = (preference_id) => {
            return preferences.value.find(pref => pref.preference_id == preference_id)?.name ?? '-';
        }
        return {
            form,
            modalShow,
            successMessage,
            alertMessage,
            dismissMessage,
            scrollContainer,
            validationRef,
            validationRules,
            errors,
            mobile,
            showInfo,
            selectedCountry,
            searchQuery,
            filteredCountries,
            selectCountry,
            validateNumber,
            showAiportTerminal,
            showTrainStationEnterance,
            etaParam,
            vehicleTypes,
            makeBooking,
            bookValidate,
            maxStopovers,
            enableBooking,
            stopovers,
            pickupMarker,
            dropMarker,
            driverMarkers,
            stopMarkers,
            // recentSearches,
            packageTypes,
            rentalVehicleTypes,
            addStopover,
            removeStopover,
            getMinReturnTime,
            handleInput,
            selectSuggestion,
            pickSuggestions,
            dropSuggestions,
            stopSuggestions,
            formattedDuration,
            unit,
            handlePickupAddress,
            preferences,
            preferenceIconUrl,
            preferenceName,
        };
    },
};
</script>

<template>
    <Layout>
        <Head title="Taxi Ride" />
        <PageHeader :title="$t('book')" :pageTitle="$t('dispatch')" />
        <BRow>
            <BCol lg="12">
                <BCard no-body id="tasksList">
                    <BCardHeader class="border-0">
                        <BRow>
                            <BCol lg="6">
                            </BCol>
                            <BCol lg="6">
                                <div class="row mt-4">
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="p-0 border border-dashed rounded">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <div class="avatar-title rounded bg-transparent text-success fs-24" >
                                                        <i class="ri-map-pin-time-line" ></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                  <p class="text-muted mb-1">{{$t("duration")}} :<span class="fs-16 fw-bold ms-2"> {{ formattedDuration }}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end col -->
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="p-0 border border-dashed rounded">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <div class="avatar-title rounded bg-transparent text-success fs-24">
                                                      <i class=" ri-pin-distance-line"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <p class="text-muted mb-1">{{$t("distance")}} :<span class="fs-16 fw-bold ms-2">{{(etaParam.distance_in_unit/1000).toFixed(2)}} {{unit}}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end col -->
                                </div>
                            </BCol>
                        </BRow>
                    </BCardHeader>
                    <BCardBody class="border border-dashed border-end-0 border-start-0">
                      <form @submit.prevent="handleSubmit">
                        <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                            <div class="row">
                            <div class="col-12 col-lg-6" ref="scrollContainer" style="max-height:500px;overflow-y: auto;">
                                <div class="accordion accordion-flush" id="accordionform">
                                <div class="accordion-item border-0">
                                    <h2 class="card-title accordion-header" id="personal-one" @click="bookValidate('personal')">
                                        <button :class="{'accordion-button':true,'collapsed': showInfo != 'personal'}" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#personal-info" :aria-expanded="showInfo == 'personal'" aria-controls="personal-info">
                                            <h4 class="card-title flex-grow-1">{{$t("personal_info")}}<span v-if="errors.mobile" class="text-danger">*</span></h4>
                                            <span v-if="showInfo != 'personal'"><span class="mt-2 amount text-dark" v-if="form.mobile && showInfo != 'personal'">{{ form.name + ' - ' + form.mobile }}</span></span>
                                        </button>
                                    </h2>
                                    <div id="personal-info" :class="{'accordion-collapse':true, 'collapse': true, 'show': showInfo == 'personal'}" aria-labelledby="personal-info" data-bs-parent="#accordionform">
                                        <div class="row">
                                            <div class="col-6">
                                            <div>
                                                <label class="form-label">{{$t("mobile")}}</label>
                                                <div class="input-group" data-input-flag="">
                                                    <button class="btn btn-light border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <img :src="selectedCountry.flag" alt="flag" height="20" class="country-flagimg rounded">
                                                        <span class="ms-2 country-codeno">{{ selectedCountry.dial_code }}</span>
                                                    </button>
                                                    <input type="text" id="mobile" class="form-control rounded-end flag-input" v-model="form.mobile" @keydown="preventDefault" :placeholder="$t('enter_number')" @input="validateNumber">
                                                    <div class="dropdown-menu w-100">
                                                        <div class="p-2 px-3 pt-1 searchlist-input">
                                                            <input type="text" class="form-control form-control-sm border search-countryList" :placeholder="$t('search_country_name_or_country_code')" v-model="searchQuery">
                                                        </div>
                                                        <ul class="list-unstyled dropdown-menu-list mb-0">
                                                            <li v-for="country in filteredCountries" :key="country.id">
                                                                <a href="javascript:void(0);" class="dropdown-item notify-item language py-2" @click="selectCountry(country)">
                                                                    <img :src="country.flag" alt="flag" class="me-2 rounded" height="18">
                                                                    <span class="align-middle">{{ country.name }} {{ country.dial_code }}</span>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <span v-for="(error, index) in errors.mobile" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                            </div>
                                            <div class="col-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">{{$t("name")}}</label>
                                                <input type="text" class="form-control" v-model="form.name" :placeholder="$t('enter_name')" id="name" >
                                                <span v-for="(error, index) in errors.name" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="text-center">
                                                <button type="button" @click="bookValidate('personal')" class="btn btn-primary">{{$t("confirm")}}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <h2 class="card-title accordion-header" id="ride-one" @click="bookValidate('ride')">
                                        <button :class="{'accordion-button':true,'collapsed': showInfo != 'ride'}" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#ride-info" :aria-expanded="showInfo == 'ride'" aria-controls="ride-info">
                                            <h4 class="card-title flex-grow-1">{{$t("ride_info")}}<span v-if="errors.module || errors.is_later" class="text-danger">*</span></h4>
                                            <span v-if="showInfo != 'ride'"><span class="mt-2 amount text-dark" v-if="form.module && showInfo != 'ride'">{{ form.module_name }}</span></span>
                                        </button>
                                    </h2>
                                    <div id="ride-info" :class="{'accordion-collapse':true, 'collapse': true, 'show': showInfo == 'ride'}" aria-labelledby="ride-info" data-bs-parent="#accordionform">
                                        <div class="row">
                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                            <label for="type" class="form-label">{{$t("booking_type")}} <span v-if="errors.is_later" class="text-danger">*</span></label>
                                                <select id="type" class="form-select" v-model="form.is_later">
                                                <option disabled value="">{{$t("choose_type")}}</option>
                                                <option v-if="form.ride_type!=='outstation'" value=0>{{$t("instant_booking")}}</option>
                                                <option value=1>{{$t("book_later")}}</option>
                                                </select>
                                            <span v-for="(error, index) in errors.is_later" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                        </div>
                                        <!-- Module Select -->
                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                            <label for="ride_type" class="form-label">{{$t("ride_type")}}<span v-if="errors.module" class="text-danger">*</span></label>
                                                <select id="ride_type" class="form-select" v-model="form.module" v-if="app_modules.length > 0">
                                                    <option disabled value="">{{$t("choose_type")}}</option>
                                                    <option v-for="(enabledModule, index) in app_modules" :key="index" :value="enabledModule.id">{{ enabledModule.name }}</option>
                                                </select>
                                            <span v-for="(error, index) in errors.module" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="text-center">
                                            <button type="button" @click="bookValidate('ride')" class="btn btn-primary">{{$t("confirm")}}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <h2 class="card-title accordion-header" id="location-one" @click="bookValidate('location')">
                                        <button :class="{'accordion-button':true,'collapsed': showInfo != 'location'}" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#location-info" :aria-expanded="showInfo == 'location'"  aria-controls="location-info">
                                            <h4 class="card-title flex-grow-1">{{$t("location")}}<span v-if="etaParam.pick_lat && !form.location" class="text-danger">*</span></h4>
                                        </button>
                                    </h2>
                                    <div id="location-info" :class="{'accordion-collapse':true, 'collapse': true, 'show': showInfo == 'location'}" aria-labelledby="location-info" data-bs-parent="#accordionform">
                                        <div class="row">
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <label for="pickup" class="form-label">{{ $t("pickup_location") }}</label>
                                                    <a v-if="form.ride_type=='regular' && (stopovers.length < maxStopovers)" class="btn btn-primary mb-3" @click="addStopover">{{$t("add_stops")}}</a>
                                                </div>
                                                <div class="autocomplete-container">
                                                    <div class="input-group">
                                                        <input
                                                        type="text"
                                                        class="form-control"
                                                        v-model="form.pick_address"
                                                        :placeholder="$t('enter_pickup')"
                                                        id="pickup"
                                                        autocomplete="off"
                                                        @input="handleInput('pickup')"
                                                        />
                                                    </div>
                                                    <div v-if="pickSuggestions.length > 0" class="autocomplete-results">
                                                        <div
                                                        v-for="suggestion in pickSuggestions"
                                                        :key="suggestion.placeId"
                                                        class="autocomplete-item"
                                                        @click="selectSuggestion(suggestion,'pickup')"
                                                        >
                                                        {{ suggestion.formattedAddress }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <span v-for="(error, index) in errors.pick_address" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                        <div class="col-12" v-if="form.ride_type=='regular'" v-for="(stop, index) in stopovers" :key="index">
                                            <div class="mb-3">
                                            <label :for="`stop-${index}`" class="form-label">{{$t("stop_location")}}</label>
                                            <div class="d-flex align-items-center">
                                                <div class="autocomplete-container col-8">
                                                    <div class="input-group">
                                                        <input
                                                        type="text"
                                                        class="form-control"
                                                        v-model="stop.address"
                                                        :placeholder="'Enter stop ' + (index + 1)"
                                                        id="`stop-${index}`"
                                                        autocomplete="off"
                                                        @input="handleInput('stop',index)"
                                                        />
                                                    </div>
                                                    <div v-if="stopSuggestions?.[index]?.length > 0" class="autocomplete-results">
                                                        <div
                                                        v-for="suggestion in stopSuggestions?.[index]"
                                                        :key="suggestion.placeId"
                                                        class="autocomplete-item"
                                                        @click="selectSuggestion(suggestion,'stop',index)"
                                                        >
                                                        {{ suggestion.formattedAddress }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <i class="bx bx-trash text-danger fs-22 btn" @click="removeStopover(index)"></i>
                                            </div>
                                            <span v-for="(error, index) in errors.stop?.[index]" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="row">
                                        <div class="col-12" v-if="showAiportTerminal">
                                            <div class="mb-3">
                                            <label for="terminal" class="form-label">{{$t("aiport_terminal_info")}}</label>
                                            <input type="text" class="form-control" v-model="form.terminal" placeholder="$t('enter_terminal_info')" id="terminal" >
                                            </div>
                                        </div>
                                        </div>
                                        <div class="row">
                                        <div class="col-12" v-if="showTrainStationEnterance">
                                            <div class="mb-3">
                                            <label for="enterance" class="form-label">{{$t("station_enterance_info")}}</label>
                                            <input type="text" class="form-control" v-model="form.enterance" placeholder="$t('enter_enterance_info')" id="enterance" >
                                            </div>
                                        </div>

                                        </div>
                                        <div class="row">
                                        <div class="col-12" v-if="form.ride_type!=='rental'">
                                            <div class="mb-3">
                                            <label for="drop" class="form-label">{{$t("drop_location")}}</label>
                                                <div class="autocomplete-container">
                                                    <div class="input-group">
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        v-model="form.drop_address"
                                                        :placeholder="$t('enter_drop')"
                                                        id="drop"
                                                        autocomplete="off"
                                                        @input="handleInput('drop')"
                                                    />
                                                    </div>
                                                    <div v-if="dropSuggestions.length > 0" class="autocomplete-results">
                                                    <div
                                                        v-for="suggestion in dropSuggestions"
                                                        :key="suggestion.placeId"
                                                        class="autocomplete-item"
                                                        @click="selectSuggestion(suggestion,'drop')"
                                                    >
                                                        {{ suggestion.formattedAddress }}
                                                    </div>
                                                    </div>
                                                </div>
                                            <span v-for="(error, index) in errors.drop_address" :key="index" class="text-danger">{{ error }}</span>
                                            <div class="col-12" v-if="errors.map">
                                                <div class="mb-3">
                                                <span v-for="(error, index) in errors.map" :key="index" class="text-danger">{{ error }}</span>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="text-center">
                                            <button type="button" @click="bookValidate('location',true)" class="btn btn-primary">{{$t("confirm")}}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0" v-if="vehicleTypes.length > 0 || rentalVehicleTypes.length > 0">
                                    <h2 class="card-title accordion-header" id="vehicle-one" @click="bookValidate('vehicle')">
                                        <button :class="{'accordion-button':true,'collapsed': showInfo != 'vehicle'}" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#select-vehicle" aria-expanded="false" aria-controls="select-vehicle">
                                            <h4 class="card-title flex-grow-1">{{$t("select_vehicle")}}</h4>
                                                <span v-if="errors.vehicle_type" class="text-danger">*</span>
                                                <span class="mt-2 amount text-dark" v-if="form.vehicle_type && showInfo != 'vehicle'">{{ form.vehicleTypeName + ' - ' + form.requested_eta_amount }}</span>
                                        </button>
                                    </h2>
                                    <div id="select-vehicle" :class="{'accordion-collapse':true, 'collapse': true, 'show': showInfo == 'vehicle'}" aria-labelledby="select-vehicle"
                                        data-bs-parent="#accordionform">
                                        <div class="row">
                                        <!-- Vehicle Select -->
                                        <h5 v-if="vehicleTypes.length > 0" class=" mt-4">{{$t("select_vehicle")}}</h5>
                                        <div v-if="vehicleTypes.length > 0" style="max-width:600px; overflow-x: auto;" class="col-12">
                                            <div class="mb-0">
                                            <div class="d-flex mt-2">
                                                <div v-for="(vehicleType, index) in vehicleTypes" :key="index" class="select-checkbox-btn text-center">
                                                <label :for="'vehicle_' + vehicleType.zone_type_id" class="select-checkbox-btn-wrapper">
                                                    <input
                                                    :id="'vehicle_' + vehicleType.zone_type_id"
                                                    name="types"
                                                    type="radio"
                                                    :value="vehicleType.zone_type_id"
                                                    v-model="form.vehicle_type"
                                                    class="select-checkbox-btn-input"
                                                    />
                                                    <span class="select-checkbox-btn-content">
                                                    <a class="w-32 cursor-pointer">
                                                        <div class="text-center mt-2 ms-4 text-dark"><i class="bx bx-time-five mx-1"></i>{{ vehicleType.driver_time || '--' }}</div>
                                                        <div class="w-32 h-32 flex-none image-fit rounded-circle">
                                                        <img alt="" class="rounded-circle img-fluid" :src="vehicleType.vehicle_icon" />
                                                        </div>
                                                        <div class="text-center mt-2 amount text-dark">{{ vehicleType.currency }} {{ vehicleType.total }}</div>
                                                        <div class="text-center mt-2 text-dark">{{ vehicleType.name }}</div>
                                                    </a>
                                                    </span>
                                                </label>
                                                </div>
                                            </div>
                                            </div>
                                        </div>

                                        </div>
                                        <div class="row">
                                        <!-- Vehicle Select -->
                                        <h4 v-if="rentalVehicleTypes.length > 0" class="card-title flex-grow-1 mt-4">{{$t("select_vehicle")}}</h4>
                                        <div v-if="rentalVehicleTypes.length > 0" style="max-width:600px; overflow-x: auto;" class="col-12">
                                            <div class="mb-0">
                                            <div class="d-flex mt-5">
                                            <div v-for="(vehicleType, index) in rentalVehicleTypes" :key="index" class="select-checkbox-btn text-center">
                                                <label :for="'vehicle_' + vehicleType.zone_type_id" class="select-checkbox-btn-wrapper">
                                                <input
                                                :id="'vehicle_' + vehicleType.zone_type_id"
                                                name="types"
                                                type="radio"
                                                :value="vehicleType.zone_type_id"
                                                v-model="form.vehicle_type"
                                                class="select-checkbox-btn-input"
                                                />
                                                <span class="select-checkbox-btn-content">
                                                    <a class="w-32 cursor-pointer">
                                                    <div class="text-center mt-2 ms-4 text-dark"><i class="bx bx-time-five mx-1"></i>{{ vehicleType.driver_time || '--' }}</div>
                                                    <div class="w-32 h-32 flex-none image-fit rounded-circle">
                                                    <img alt="" class="rounded-circle img-fluid" :src="vehicleType.icon" />
                                                    </div>
                                                    <div class="text-center mt-2 amount text-dark">{{ vehicleType.fare_amount }}</div>
                                                    <div class="text-center mt-2 text-dark">{{ vehicleType.name }}</div>
                                                    </a>
                                                </span>
                                                </label>
                                            </div>
                                            </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="text-center">
                                            <button type="button" @click="bookValidate('vehicle')" class="btn btn-primary">{{$t("confirm")}}</button>
                                            </div>
                                        </div>

                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0" v-if="form.transport_type == 'delivery' || form.is_later || form.is_rental || preferences?.length">
                                    <h2 class="card-title accordion-header" id="miscellaneous-one" @click="bookValidate('miscellaneous')">
                                        <button :class="{'accordion-button':true,'collapsed': showInfo != 'miscellaneous'}" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#select-miscellaneous" :aria-expanded="showInfo == 'miscellaneous'" aria-controls="select-miscellaneous">
                                            <h4 class="card-title flex-grow-1">{{$t("miscellaneous")}}<span v-if="errors.miscellaneous" class="text-danger">*</span></h4>
                                                
                                        </button>
                                    </h2>
                                    <div id="select-miscellaneous" :class="{'accordion-collapse':true, 'collapse': true, 'show': form.vehicle_type && showInfo == 'miscellaneous'}" aria-labelledby="select-miscellaneous"
                                        data-bs-parent="#accordionform">
                                        <div class="row">
                                            <!-- POC info -->
                                            <h4 v-if="form.transport_type=='delivery'" class="card-title mb-2 flex-grow-1 mt-4">{{$t("poc_info")}}</h4>
                                            <div v-if="form.transport_type=='delivery'" class="px-2 mb-2">
                                            <span class="badge bg-dark-subtle text-dark p-1 fs-12 text-center">
                                                <div class="form-check m-2 mx-3">
                                                <input class="form-check-input" type="checkbox" id="poc" v-model="isPocSame">
                                                <label class="form-check-label mt-1" for="poc">
                                                    {{$t("check_if_poc_info_as_same_as_personal_info")}}
                                                </label>
                                                </div>
                                            </span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div v-if="form.transport_type=='delivery'" class="col-6">
                                            <div class="mb-3">
                                                <label for="poc_name" class="form-label">{{$t("drop_poc_name")}}</label>
                                                <input type="text" class="form-control" v-model="form.drop_poc_name" :placeholder="$t('enter_poc_name')" id="poc_name" :disabled="isPocSame" >
                                                <span v-for="(error, index) in errors.drop_poc_name" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                            </div>
                                            <div v-if="form.transport_type=='delivery'" class="col-6">
                                            <div class="mb-3">
                                                <label for="poc_mobile" class="form-label">{{$t("drop_poc_mobile")}}</label>
                                                <input type="text" class="form-control" v-model="form.drop_poc_mobile" :placeholder="$t('enter_poc_mobile')" id="poc_mobile" :disabled="isPocSame" >
                                                <span v-for="(error, index) in errors.drop_poc_mobile" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <h4 v-if="form.transport_type=='delivery'" class="card-title mb-2 flex-grow-1 mt-4">{{$t("select_goods")}}</h4>
                                            <div v-if="form.transport_type=='delivery'" class="col-6">
                                            <div class="mb-3">
                                                <label for="goods_type" class="form-label">{{$t("goods_type")}}</label>
                                                <select id="goods_type" class="form-select" v-model="form.goods_type_id">
                                                <option disabled value="">{{$t("choose_type")}}</option>
                                                <option v-for="goodsType in goodsTypes" :key="goodsType.id" :value="goodsType.id">{{ goodsType.goods_type_name }}</option>
                                                </select>
                                                <span v-for="(error, index) in errors.goods_type_id" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                            </div>
                                            <div v-if="form.transport_type=='delivery'" class="col-6">
                                            <div class="mb-3">
                                                <label for="goods_type_quantity" class="form-label">{{$t("goods_quantity")}}</label>
                                                <input type="text" class="form-control" v-model="form.goods_type_quantity" :placeholder="$t('enter_quantity')" id="goods_type_quantity" >
                                                <span v-for="(error, index) in errors.goods_type_quantity" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                            </div>
                                        </div>
                                        <div class="row">

                                            <!-- rental package -->
                                            <h4 v-if="form.is_rental == 1" class="card-title mb-3 flex-grow-1 mt-4">{{$t("select_pack")}}</h4>
                                            <div v-if="form.is_rental == 1" style="max-width:600px; overflow-x: auto;" class="col-12">
                                            <div class="mb-3">
                                                <label for="rental_package_id" class="form-label">{{$t("rental_package_id")}}</label>
                                                <select id="rental_package_id" class="form-select" v-model="form.rental_package_id">
                                                <option disabled value="">{{$t("choose_type")}}</option>
                                                <option v-for="pack in packageTypes" :key="pack.id" :value="pack.id">{{ pack.package_name }}</option>
                                                </select>
                                                <span v-for="(error, index) in errors.rental_package_id" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                            </div>

                                        </div>
                                        <div class="row">
                                        <div class="col-12" v-if="form.is_later == 1">
                                            <div class="mb-3">
                                            <label for="dispatch-datepicker" class="form-label">{{$t("date")}}</label>
                                            <flat-pickr :placeholder="$t('select_date')" v-model="form.trip_start_time" :config="dateTimeConfig"
                                            class="form-control flatpickr-input" id="dispatch-datepicker"></flat-pickr>
                                            <span v-for="(error, index) in errors.trip_start_time" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                        </div>


                                        </div>
                                        <div class="row">
                                        <div v-if="form.is_out_station == 1" class="mt-4 mb-4">
                                            <h4 class="card-title mb-3 flex-grow-1">{{$t("outstation")}}</h4>
                                            <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="is_round_trip"
                                                id="oneway" :value=0 v-model="form.is_round_trip">
                                                <label class="form-check-label" for="oneway">{{$t("one_way_outstation_trip")}}</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="is_round_trip"
                                                id="roundtrip" :value=1 v-model="form.is_round_trip">
                                                <label class="form-check-label" for="roundtrip">{{$t("round_trip_outstation_trip")}}</label>
                                            </div>
                                            </div>
                                        </div>

                                        </div>
                                        <div class="row">
                                        <div class="col-12" v-if="form.is_round_trip == 1 && form.is_out_station == 1">
                                            <div class="mb-3">
                                            <label for="dispatch-datepicker" class="form-label">{{$t("return_time")}}</label>
                                            <flat-pickr :placeholder="$t('return_time')" v-model="form.return_time" :config="dateTimeConfigReturnTime"
                                            class="form-control flatpickr-input" id="dispatch-datepicker"></flat-pickr>
                                            </div>
                                            <span v-for="(error, index) in errors.return_time" :key="index" class="text-danger">{{ error }}</span>
                                        </div>

                                        </div>
                                        <div class="row" v-if="preferences?.length">
                                            <div class="col-6">
                                            <div class="mb-3">
                                                <label for="preferences" class="form-label">{{$t("preferences_for_user ")}}</label>
                                                <div class="col-sm-auto">
                                                    <div class="avatar-group">
                                                        <div class="avatar-group-item" v-for="pref in  form.preferences">
                                                            <a href="javascript: void(0);" class="d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" :title="preferenceName(pref)" :data-bs-original-title="preferenceName(pref)">
                                                                <img :src="preferenceIconUrl(pref)" :alt="preferenceName(pref)" class="rounded-circle avatar-xs">
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                            <div class="col-6">
                                            <div class="mb-3">
                                                <label for="preferences" class="form-label">{{$t("preferences")}}</label>
                                                <Multiselect 
                                                    id="preferences" 
                                                    mode="tags" 
                                                    v-model="form.preferences" 
                                                    :close-on-select="false"
                                                    :searchable="true" 
                                                    :create-option="false"
                                                    :options="preferences.map(pref => ({ value: pref.preference_id, label: pref.name, }))"
                                                    :placeholder="$t('select_preferences')"
                                                />
                                                <span v-for="(error, index) in errors.goods_type_quantity" :key="index" class="text-danger">{{ error }}</span>
                                            </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-12">
                                            <div class="text-center">
                                            <button type="button" @click="bookValidate('miscellaneous')" class="btn btn-primary">{{$t("confirm")}}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0" v-if="form.vehicle_type">
                                    <h2 class="card-title accordion-header" id="preference-one" @click="bookValidate(' booking')">
                                        <button  :class="{'accordion-button':true,'collapsed': showInfo != 'booking'}" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#preference-info" aria-expanded="false" aria-controls="preference-info">
                                            <h4 class="card-title flex-grow-1">{{$t("booking")}}</h4>
                                        </button>
                                    </h2>
                                    <div id="preference-info" :class="{'accordion-collapse':true, 'collapse': true, 'show': showInfo == 'booking'}" aria-labelledby="preference-info"
                                        data-bs-parent="#accordionform">
                                        <div class="row">
                                        <!-- Asiign Driver -->
                                        <div class="mt-4 mb-4">
                                            <h4 class="card-title mb-3 flex-grow-1">{{$t("assign")}}</h4>
                                            <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="inlineRadioOptions" :checked="form.assign_method == 1"
                                                id="WithoutinlineRadio1" :value="1" v-model="form.assign_method">
                                                <label class="form-check-label" for="WithoutinlineRadio1">{{$t("manual_assign")}}</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                                id="WithoutinlineRadio2" value=0 v-model="form.assign_method" :checked="form.assign_method == 0">
                                                <label class="form-check-label" for="WithoutinlineRadio2">{{$t("automatic_assign")}}</label>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="text-center">
                                            <button type="submit" :disabled="!enableBooking"   @click="makeBooking" class="btn btn-primary">{{$t("make_booking")}}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                            </div>
                            <!-- map  -->
                          <div class="col-12 col-lg-6">
                            <div class="mb-3 text-center m-auto">
                                <div id="map" style="height: 500px;">
                                    <googleMap
                                        :baseUrl="baseUrl"
                                        :default_location="default_location"
                                        :pick_location="pickupMarker"
                                        :drop_location="dropMarker"
                                        :nearbyDrivers="driverMarkers"
                                        :stops="stopMarkers"
                                        :draggable="true"
                                        :polyline="form.poly_line"
                                        :map_key="map_key"
                                        :libraries="['marker','geometry','geocoding']"
                                        @update-pickup-address="handlePickupAddress"
                                    >
                                    {{$t("map_loading")}}
                                    </googleMap>
                                </div>
                            </div>
                          </div>
                        </div>
                        </FormValidation>
                      </form>
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
.text-danger {
padding-top: 5px;
}

:root {
--primary: #222222;
--primary-hover: {{ $side -> value }};
}
/* payments */
.select-form{
position: relative;
width: 100%;
margin-bottom: 18px;
}

.select-checkbox-group {
display: flex;
align-items: center;
position: relative;
}
.select-checkbox-btn {
margin-right: 15px;
margin-bottom: 15px;
}
.select-checkbox-btn-wrapper {
display: flex;
align-items: center;
justify-content: center;
width: fit-content;
position: relative;
}
.select-checkbox-btn-input {
clip: rect(0 0 0 0);
-webkit-clip-path: inset(100%);
clip-path: inset(100%);
height: 1px;
overflow: hidden;
position: absolute;
white-space: nowrap;
width: 1px;
}
.select-checkbox-btn-input:checked + .select-checkbox-btn-content {
border-color: var(--primary);
color: var(--primary);
}
.select-checkbox-btn-input:checked + .select-checkbox-btn-content:before {
transform: scale(1);
opacity: 1;
background-color: var(--primary);
border-color: var(--primary);
}
.select-checkbox-btn-input:checked
+ .select-checkbox-btn-content
.select-checkbox-btn-icon,
.select-checkbox-btn-input:checked
+ .select-checkbox-btn-content
.select-checkbox-btn-label {
color: var(--primary);
}
.select-checkbox-btn-input:focus + .select-checkbox-btn-content {
border-color: var(--primary);
}
.select-checkbox-btn-input:focus + .select-checkbox-btn-content:before {
transform: scale(1);
opacity: 1;
}
.col-12.col-lg-6 {
  scroll-behavior: smooth;
}

.select-checkbox-btn-content {
display: flex;
flex-direction: column;
align-items: center;
justify-content: center;
width: 140px;
min-height: 140px;
border-radius: 10px;
border: 0.1rem solid #dfe2e6;
background-color: #fff;
transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s,
-webkit-box-shadow ease-in-out 0.15s;
cursor: pointer;
position: relative;
user-select: none;
appearance: none;
}
.select-checkbox-btn-content:before {
content: "";
position: absolute;
width: 22px;
height: 22px;
border: 0.1rem solid #bbc1e1;
background-color: #fff;
border-radius: 9999px;
top: 5px;
left: 5px;
opacity: 0;
transform: scale(0);
transition: 0.25s ease;
background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='192' height='192' fill='%23FFFFFF' viewBox='0 0 256 256'%3E%3Crect width='256' height='256' fill='none'%3E%3C/rect%3E%3Cpolyline points='216 72.005 104 184 48 128.005' fill='none' stroke='%23FFFFFF' stroke-linecap='round' stroke-linejoin='round' stroke-width='32'%3E%3C/polyline%3E%3C/svg%3E");
background-size: 12px;
background-repeat: no-repeat;
background-position: 50% 50%;
display: flex;
align-items: center;
justify-content: center;
}
.select-checkbox-btn-content:hover {
border-color: var(--primary);
}
.select-checkbox-btn-content:hover:before {
transform: scale(1);
opacity: 1;
}

.select-checkbox-btn-icon {
transition: 0.375s ease;
color: #3c3c3cc7;
}
.select-checkbox-btn-icon svg {
width: 50px;
height: 50px;
}

.select-checkbox-btn-label {
color: #3c3c3cc7;
transition: 0.375s ease;
text-align: center;
}
.marker {
transition: transform 0.5s ease-out;
}

.amount {
font-family: Arial, sans-serif; /* Ensure the font supports the rupee symbol */
font-size: 16px; /* Adjust the size as needed */
white-space: nowrap; /* Prevent text from wrapping */
letter-spacing: 0.1em; /* Adjust spacing if needed */
}
/* end */
</style>
<style scoped>

.autocomplete-container {
  position: relative;
}

.autocomplete-results {
  border: 1px solid #ccc;
  max-height: 200px;
  overflow-y: auto;
  position: absolute;
  width: 100%;
  background-color: white;
  z-index: 1000;
}

.autocomplete-item {
  padding: 5px;
  cursor: pointer;
}
</style>