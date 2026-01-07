<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import { ref, onMounted } from "vue";
import axios from "axios";
import { useSharedState } from '@/composables/useSharedState';
import { useI18n } from 'vue-i18n';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import 'leaflet-draw/dist/leaflet.draw.css'; // Import Leaflet Draw CSS
import 'leaflet-draw'; // Import Leaflet Draw JS

export default {
    components: {
        Layout,
        PageHeader,
        Head,
    },
    props: {
        zone: Object, // Passed from controller
        successMessage: String,
        alertMessage: String,
        existingZones: Array,
        enable_maximum_distance_feature: Boolean,
        settings: Object,
    },
    setup(props) {
        const { zone } = props;
        const { t } = useI18n();
        const { languages, fetchData } = useSharedState();
        const activeTab = ref('English');
        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');
        const enable_peak_zone_feature = ref(props.settings.enable_peak_zone_feature == 1);

        const form = useForm({
            service_location_id: zone.service_location_id,
            languageFields: zone ? zone.languageFields || {} : {},
            unit: zone.unit,
            coordinates: zone.coordinates || [], // Initialize coordinates from zone data
            maximum_outstation_distance: zone.maximum_outstation_distance ?? (props.enable_maximum_distance_feature ? '' : 0),
            maximum_distance: zone.maximum_distance ?? (props.maximum_distance ? '' : 0),
            peak_zone_radius: zone.peak_zone_radius ?? (props.peak_zone_radius ? '' : 0),
            peak_zone_duration: zone.peak_zone_duration ?? (props.peak_zone_duration ? '' : 0),
            peak_zone_history_duration: zone.peak_zone_history_duration ?? (props.peak_zone_history_duration ? '' : 0),
            peak_zone_ride_count: zone.peak_zone_ride_count ?? (props.peak_zone_ride_count ? '' : 0),
            distance_price_percentage: zone.distance_price_percentage ?? (props.distance_price_percentage ? '' : 0),
        });

        const serviceLocations = ref([]);
        let map, currentPolygon;
        let polygons = [];

        const bounds = L.latLngBounds();
        const fetchServiceLocations = async () => {
            const response = await axios.get('/zones/list');
            serviceLocations.value = response.data.results;
        };

        const initializeForm = () => {
            form.service_location_id = zone.service_location_id ? zone.service_location_id.toString() : '';
            form.name = zone.name || '';
            form.unit = zone.unit === 1;
        };

        const initializeMap = () => {
            if (zone && zone.coordinates) {
                map = L.map('map').setView([0, 0], 10); // Initialize map

                // Set up OSM tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap',
                }).addTo(map);

                // Add drawing functionality
                const drawnItems = new L.FeatureGroup();
                map.addLayer(drawnItems);


                props.existingZones.forEach((polygon) => {
                    const currentPolygon = polygon.map((coordinates)=>[coordinates.lat,coordinates.lng])

                    // 🔒 Add a non-editable and non-deletable polygon
                    L.polygon(currentPolygon, {
                        color: 'red',
                        interactive: false // disables mouse events (edit/delete prevention)
                    }).addTo(map);
                });

                zone.coordinates.forEach(polygon => {
                   
                const polygonCoordinates = polygon[0].map(point => [point.coordinates[1], point.coordinates[0]]);

                currentPolygon = L.polygon(polygonCoordinates, {
                    color: 'blue',
                    weight: 3,
                    fillOpacity: 0.2,
                }).addTo(drawnItems);

                // Adjust map view to fit the polygon
                bounds.extend(currentPolygon.getBounds());

                polygons.push(currentPolygon);

                });

                map.fitBounds(bounds);
                initializeDrawing(drawnItems);
            }
        };

        const initializeDrawing = (drawnItems) => {

            // Create a draw control
            const drawControl = new L.Control.Draw({
                edit: {
                    featureGroup: drawnItems,
                    remove: true,
                },
                draw: {
                    polygon: {
                        allowIntersection: false, // Restrict shapes to simple polygons
                    },
                    rectangle: false,
                    circle: false,
                    polyline: false,
                    marker: false,
                },
            });
            map.addControl(drawControl);

            // Event listener for when a shape is drawn
            map.on(L.Draw.Event.CREATED, function (event) {
                const layer = event.layer;
                drawnItems.addLayer(layer);
                polygons.push(layer);

            });

            // Handle shape delete
            map.on(L.Draw.Event.DELETED, function (event) {
                const layers = event.layers;
                layers.eachLayer(function (layer) {
                    // Remove layer from your polygons array
                    polygons = polygons.filter(p => p !== layer);
                });
            });
        };

         const handleSubmit = async () => {
            const errors = validateForm();
            if (Object.keys(errors).length === 0) {
                try {
                    if (polygons.length === 0) {
                        alertMessage.value = t('at_least_one_completed_polygon_is_required');
                        return;
                    }
                    const coordinates = polygons.map(polygon =>
                        polygon.getLatLngs()[0].map(latLng => [
                            latLng.lng,
                            latLng.lat
                        ])
                    );

                    const formData = {
                        ...form.data(),
                        coordinates: JSON.stringify(coordinates)
                    };

                    const response = await axios.post(`/zones/update/${zone.id}`, formData);

                    if (response.status === 200) {
                        successMessage.value = t('zone_created_successfully');
                        form.reset();
                        router.get('/zones');
                    } else {
                        console.error(t('failed_to_update_zone'));
                    }
                } catch (error) {
                    if (error.response && error.response.status === 403) {
                        alertMessage.value = error.response.data.alertMessage;
                        setTimeout(()=>{
                            router.get('/zones');
                        },5000)
                    }else{
                        console.error(t('error_updating_zone'), error);
                    }
                }
            } else {
                form.errors = errors;
            }
        };

        const validateForm = () => {
            const { service_location_id, unit, } = form;
            const errors = {};
            if (!unit) {
                errors.unit = 'Unit is required';
            } else {
                delete errors.unit;
            }
            if (!service_location_id) {
                errors.service_location_id = t('service_location_is_required');
            } else {
                delete errors.service_location_id;
            }

            if(props.enable_maximum_distance_feature){
                if (!maximum_distance) {
                    errors.maximum_distance = t('required');
                } else {
                    delete errors.maximum_distance;
                }
                if (!maximum_outstation_distance) {
                    errors.maximum_outstation_distance = t('required');
                }else{
                    delete errors.maximum_outstation_distance;
                }
            }
            return errors;
        };

        const setActiveTab = (tab) => {
            activeTab.value = tab;
        };

        onMounted(async () => {
            if (Object.keys(languages).length == 0) {
                await fetchData();
            }
            initializeMap();
            fetchServiceLocations();
        });

        return {
            form,
            serviceLocations,
            handleSubmit,
            setActiveTab,
            languages,
            activeTab,
            successMessage,
            alertMessage,
            enable_peak_zone_feature,
        };
    },
};
</script>

<template>
    <Layout>
        <Head title="Edit Zone" />
        <PageHeader :title="$t('edit')" :pageTitle="$t('zone')" pageLink="/zones" />
        <div v-if="zone" class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <form @submit.prevent="handleSubmit">
                            <div class="mb-3">
                                <label for="service_location" class="form-label">{{$t("service_location")}}</label>
                                <select class="form-select" id="service_location" v-model="form.service_location_id">
                                    <option value="" disabled>{{$t('select_service_location')}}</option>
                                    <option v-for="location in serviceLocations" :key="location.id" :value="location.id">{{ location.name }}</option>
                                </select>
                                <span v-if="form.errors.service_location_id" class="text-danger">{{ form.errors.service_location_id }}</span>
                            </div>
                            <ul class="nav nav-tabs nav-tabs-custom nav-success nav-justified" role="tablist">
                                <BRow v-for="language in languages" :key="language.code">
                                    <BCol lg="12">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" @click="setActiveTab(language.label)"
                                                :class="{ active: activeTab === language.label }" role="tab" aria-selected="true">
                                                {{ language.label }}
                                            </a>
                                        </li>
                                    </BCol>
                                </BRow>
                            </ul>
                            <div class="tab-content text-muted" v-for="language in languages" :key="language.code">
                                <div v-if="activeTab === language.label" class="tab-pane active show" :id="`${language.label}`" role="tabpanel">
                                    <div class="col-sm-6 mt-3">
                                        <div class="mb-3">
                                            <label :for="`name-${language.code}`" class="form-label">{{$t("name")}}</label>
                                            <input type="text" class="form-control" :placeholder="`Enter Name in ${language.label}`"
                                                :id="`name-${language.code}`" v-model="form.languageFields[language.code]"
                                                :required="language.code === 'en'">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="select_unit" class="form-label">{{$t("select_unit")}}</label>
                                <select id="select_unit" class="form-select" v-model="form.unit">
                                    <option disabled value="">{{$t("choose_unit")}}</option>
                                    <option value=1>{{$t("kilo_meter")}}</option>
                                    <option value=2>{{$t("miles")}}</option>
                                </select>
                                <span v-if="form.errors.unit" class="text-danger">{{ form.errors.unit }}</span>
                            </div>
                            <div class="mb-3" v-if="enable_maximum_distance_feature">
                                <label for="maximum_distance" class="form-label">{{$t("maximum_distance")}}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="any" class="form-control" :placeholder="$t('enter_maximum_distance')" id="maximum_distance" v-model.number="form.maximum_distance">
                                <span v-if="form.errors.enter_maximum_distance" class="text-danger">{{ form.errors.enter_maximum_distance }}</span>
                            </div>
                            <div class="mb-3" v-if="enable_maximum_distance_feature">
                                <label for="maximum_outstation_distance" class="form-label">{{$t("maximum_outstation_distance")}}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="any" class="form-control" :placeholder="$t('enter_maximum_outstation_distance')" id="maximum_outstation_distance" v-model.number="form.maximum_outstation_distance">
                                <span v-if="form.errors.maximum_outstation_distance" class="text-danger">{{ form.errors.maximum_outstation_distance }}</span>
                            </div>
                            <div class="row" v-if="enable_peak_zone_feature">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                    <label for="peak_zone_ride_count" class="form-label">{{$t("peak_zone_ride_count")}}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" :readonly="app_for === 'demo'" class="form-control" 
                                        :placeholder="$t('enter_peak_zone_ride_count')" id="peak_zone_ride_count"
                                        v-model="form.peak_zone_ride_count"/>
                                        <span v-if="form.errors.peak_zone_ride_count" class="text-danger">{{ form.errors.peak_zone_ride_count }}</span>
                                    </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                    <label for="peak_zone_radius" class="form-label">{{$t("peak_zone_radius")}}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                    <input type="number" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_peak_zone_radius')" id="peak_zone_radius" 
                                    v-model="form.peak_zone_radius"
                                    />
                                    <span v-if="form.errors.peak_zone_radius" class="text-danger">{{ form.errors.peak_zone_radius }}</span>
                                    </div>
                                </div>
                                </div> 
                            </div> 
                            <div class="row" v-if="enable_peak_zone_feature">
                            <div class="col-sm-6">
                                <div class="mb-3">
                                <label for="peak_zone_history_duration" class="form-label">{{$t("peak_zone_history_duration")}}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_peak_zone_history_duration')" id="peak_zone_history_duration" 
                                v-model="form.peak_zone_history_duration"
                                />
                                <span v-if="form.errors.peak_zone_history_duration" class="text-danger">{{ form.errors.peak_zone_history_duration }}</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-3">
                                <label for="peak_zone_duration" class="form-label">{{$t("peak_zone_duration")}}
                                    <span class="text-danger">*</span>
                                    </label>
                                <div class="input-group">
                                <input type="number" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_peak_zone_duration')" id="peak_zone_duration" 
                                v-model="form.peak_zone_duration"
                                />
                                <span v-if="form.errors.peak_zone_duration" class="text-danger">{{ form.errors.peak_zone_duration }}</span>
                                </div>
                            </div>
                            </div> 
                            <div class="row" v-if="enable_peak_zone_feature">

                            <div class="col-sm-6">
                                <div class="mb-3">
                                <label for="distance_price_percentage" class="form-label">{{$t("distance_price_percentage")}}
                                    <span class="text-danger">*</span>
                                    <a href="" class="text-success" data-bs-toggle="modal" data-bs-target="#surge">{{$t("how_it_works")}}</a>
                                </label>
                                <div class="input-group">
                                <input type="number" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_distance_price_percentage')" id="distance_price_percentage" 
                                v-model="form.distance_price_percentage"
                                />
                                <span v-if="form.errors.distance_price_percentage" class="text-danger">{{ form.errors.distance_price_percentage }}</span>
                                </div>
                            </div>
                            </div> 
                            </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">{{$t("update")}}</button>
                            </div>
                            <div class="mb-3">
                                <span v-if="form.errors.coordinates" class="text-danger">{{ form.errors.coordinates }}</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div id="map" style="height: 400px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

        <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" role="alert"
            id="alertMsg">
            <div class="alert-content">
            <i class="ri-notification-off-line me-3 align-middle"></i>
            <strong>Alert</strong> - {{ alertMessage }}
            <button type="button" class="btn-close btn-close-danger" @click="dismissMessage"
                aria-label="Close Alert Message"></button>
            </div>
        </div>
                <!-- surge % Modals -->
<div id="surge" class="modal fade" tabindex="-1" aria-labelledby="surgeLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <h5 class="fs-15">
                  Peak Zone Surge Percentage
                </h5>
                <p class="text-muted"> The percentage with which the price per distance increases when the ride is created within the peakzone </p>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
    </Layout>
</template>

<style scoped>
#map {
    width: 100%;
}
</style>
