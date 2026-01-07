<script>
import { ref, onMounted, watch } from "vue";
import { GoogleMap, AdvancedMarker, Polyline, InfoWindow } from 'vue3-google-map';

export default {
    name: 'googleMap',
    components: {
        GoogleMap,
        Polyline,
        AdvancedMarker,
        InfoWindow,
    },

    data() {
  return {
    geocoder: null,
   
  };
},

    props: {
        pick_location:Object,
        default_location:Object,
        drop_location:Object,
        polyline: String,
        map_key: String,
        baseUrl: String,
        current_location:Object,
        libraries:Array,
        driver:Object,
        stops:{
            type:Array,
            default:[],
        },
        nearbyDrivers:{
            type:Object,
            default: {},
        },
        draggable:{
            type:Boolean,
            default:false,
        },
    },
    setup(props,{emit}){
        const mapRef = ref(null);
        const default_location = ref(props.default_location);
        const pick_location = ref(props.pick_location);
        const drop_location = ref(props.drop_location);
        const stops = ref(props.stops);
        const drivers = ref(props.nearbyDrivers);
        const currentdriver = ref(props.driver);
        const currentdriverOption = ref(null);
        
        const driverOptions = ref([]);


        const pickOption = ref(null);
        const dropOption = ref(null);
        const stopOptions = ref([]);

        const bounds = ref(null);

        const polyLine = ref(props.polyline);
        const pathCoordinates = ref([]);

        const currentDriverMarker = ref(null);
        const previousLatLng = ref(null);
        const driverAnimation = (newVal) =>{
            const GoogleMap = window.google.maps;

            const newLatLng = new GoogleMap.LatLng(newVal.lat, newVal.lng);
            const iconType = newVal.type_icon;

            // Create or update marker
            if (!currentDriverMarker.value) {
                const driverIcon = document.createElement('img');
                driverIcon.src = props.baseUrl + `/image/map/${iconType}.png`;
                driverIcon.style.width = '30px';
                driverIcon.style.height = '30px';

                currentDriverMarker.value = {
                    marker: new GoogleMap.marker.AdvancedMarkerElement({
                        position: newLatLng,
                        content: driverIcon,
                        map: mapRef.value?.map
                    }),
                    icon: driverIcon
                };

                previousLatLng.value = newLatLng;
            } else {
                animateDriverMovement(
                    currentDriverMarker.value,
                    previousLatLng.value,
                    newLatLng,
                );
                previousLatLng.value = newLatLng;
            }
        }

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
        const animateDriverMovement = (markerObj, prevLatLng, currentLatLng) => {
            const marker = markerObj.marker;
            const icon = markerObj.icon;
            const numSteps = 60;
            const duration = 500; // ms

            let step = 0;

            const startLat = prevLatLng.lat();
            const startLng = prevLatLng.lng();
            const endLat = currentLatLng.lat();
            const endLng = currentLatLng.lng();

            const animateStep = () => {
                step++;
                const t = step / numSteps;
                const lat = startLat + (endLat - startLat) * t;
                const lng = startLng + (endLng - startLng) * t;

                marker.position = new google.maps.LatLng(lat, lng);

                const interpolatedBearing = calculateBearing(startLat, startLng, endLat, endLng);
                icon.style.transform = `rotate(${interpolatedBearing}deg)`;

                if (step < numSteps) {
                    requestAnimationFrame(animateStep);
                }
            };

            animateStep();
        };


        const onMapLoad = async () =>{

            if (!window.google || !window.google.maps  || !window.google.maps.LatLng) {
                console.warn("Google Maps API not yet loaded");
                return;
            }
            const GoogleMap = window.google.maps;

            stopOptions.value = [];
            driverOptions.value = [];
            pathCoordinates.value = [];
            bounds.value = new GoogleMap.LatLngBounds();

            if(pick_location.value && drop_location.value){
                bounds.value = new GoogleMap.LatLngBounds();
            }

            if(pick_location.value) {

                const pickupIcon = document.createElement('img');
                pickupIcon.src = props.baseUrl+'/image/map/pickup.png';
                pickupIcon.style.width = '30px';
                pickupIcon.style.height = '30px';

                const pickPosition = props.pick_location;
                
                pickOption.value = {
                    content:pickupIcon,
                    position: pickPosition,
                    gmpDraggable:props.draggable,
                };
                
                if(pick_location.value){
                    bounds.value.extend(pickPosition);
                }
                
            }else{
                pickOption.value = null;
            }
            console.log("drop_location.value",drop_location.value);

            if(drop_location.value) {

                const dropIcon = document.createElement('img');
                dropIcon.src = props.baseUrl+'/image/map/drop.png';
                dropIcon.style.width = '30px';
                dropIcon.style.height = '30px';

                const dropPosition = drop_location.value;

                if(drop_location.value){
                    bounds.value.extend(dropPosition);
                }

                dropOption.value = {
                    content:dropIcon,
                    position: dropPosition,
                    gmpDraggable:props.draggable,
                };
            }else{
                dropOption.value = null;
            }
            
            if(stops.value){
                stops.value.forEach((stop,index) => {


                    const stopIcon = document.createElement('img');
                    stopIcon.src = props.baseUrl+'/image/map/'+index+'.png';
                    stopIcon.style.width = '30px';
                    stopIcon.style.height = '30px';
                    
                    const markerOption = {
                        position: { lat: stop.lat, lng: stop.lng },
                        content:stopIcon,
                        gmpDraggable:props.draggable,
                    }


                    if(stop.lat && stop.lng){
                        bounds.value.extend({ lat: stop.lat, lng: stop.lng });
                    }

                    stopOptions.value.push({options: markerOption});

                });

            }
            if(polyLine.value){
                
                // Decode polyline -> returns array of [lat, lng] pairs
                const decodedPath = new GoogleMap.geometry.encoding.decodePath(polyLine.value);

                // Convert to array of { lat, lng } objects
                pathCoordinates.value = decodedPath.map((path) => ({ lat: path.lat(), lng:path.lng() }));


                if(pick_location.value && drop_location.value){
                    pathCoordinates.value.forEach(coord => bounds.value.extend(coord));
                }


            }
            if (drivers.value && Object.keys(drivers.value).length > 0) {
                Object.values(drivers.value).forEach((driver) => {
                    const driverIcon = document.createElement('img');
                    driverIcon.src = props.baseUrl + `/image/map/${driver.type_icon}.png`;
                    driverIcon.style.width = '30px';
                    driverIcon.style.height = '30px';

                    if (driver.rotation !== undefined) {
                        driverIcon.style.transform = `rotate(${driver.rotation}deg)`;
                    }

                    const markerOption = {
                        position: { lat: driver.lat, lng: driver.lng },
                        content: driverIcon,
                    };

                    let driverInfo = ``;

                    if(driver.name){
                        driverInfo = `<div><strong>${driver.name}</strong><br>Mobile: ${driver.mobile}</div>`;
                    }

                    driverOptions.value.push({ options: markerOption, showInfo: false, info: driverInfo });
                });
            }

            if (currentdriver.value) {
                const driverIcon = document.createElement('img');
                driverIcon.src = props.baseUrl + `/image/map/${currentdriver.value.type_icon}.png`;
                driverIcon.style.width = '30px';
                driverIcon.style.height = '30px';

                if (currentdriver.value.rotation !== undefined) {
                    driverIcon.style.transform = `rotate(${currentdriver.value.rotation}deg)`;
                }

                const markerOption = {
                    position: { lat: currentdriver.value.lat, lng: currentdriver.value.lng },
                    content: driverIcon,
                };
                if(currentdriver.value.lat){
                    bounds.value.extend({ lat: currentdriver.value.lat, lng: currentdriver.value.lng })
                }

                currentdriverOption.value = { options: markerOption, showInfo: false, };
            }

            if((pick_location.value || drop_location.value)&& mapRef.value.map){
                mapRef.value.map.fitBounds(bounds.value);
            }

        }
        watch(()=> mapRef.value?.ready, async (ready)=>{
            if(ready){
                await onMapLoad();
            }
        })
        watch(() => props.pick_location, (newVal) => {
            pick_location.value = newVal;
            onMapLoad()
        });

        watch(() => props.drop_location, (newVal) => {
            drop_location.value = newVal;
            onMapLoad()
        });

        watch(() => props.stops, (newVal) => {
            stops.value = newVal;
            onMapLoad()
        });

        watch(() => props.nearbyDrivers, (newVal) => {
            drivers.value = newVal;
            onMapLoad()
        }, { deep: true });

        watch(() => props.driver, async(newVal) => {
            if (!window.google || !window.google.maps || !window.google.maps.LatLng) return;

            if(!currentdriverOption.value){

                if (currentdriver.value) {
                    const driverIcon = document.createElement('img');
                    driverIcon.src = props.baseUrl + `/image/map/${currentdriver.value.type_icon}.png`;
                    driverIcon.style.width = '30px';
                    driverIcon.style.height = '30px';

                    if (currentdriver.value.rotation !== undefined) {
                        driverIcon.style.transform = `rotate(${currentdriver.value.rotation}deg)`;
                    }

                    const markerOption = {
                        position: { lat: currentdriver.value.lat, lng: currentdriver.value.lng },
                        content: driverIcon,
                    };

                    currentdriverOption.value = { options: markerOption, showInfo: false, };
                }
            }
            if(props.pick_location){

                if(newVal && currentdriver.value && newVal.id == currentdriver.value.id){
                    currentdriver.value = newVal;
                    driverAnimation(newVal);
                    mapRef.value.map.setCenter({ lat: newVal.lat, lng: newVal.lng });
                    mapRef.value.map.setZoom(15);
                }else{
                    if(newVal && newVal.lat){
                        currentdriver.value = newVal;
                        mapRef.value.map.setCenter({ lat: newVal.lat, lng: newVal.lng });
                        mapRef.value.map.setZoom(15);
                    }
                }
            }else{
                if(newVal){
                    currentdriver.value = newVal;
                    mapRef.value.map.setCenter({ lat: newVal.lat, lng: newVal.lng });
                    mapRef.value.map.setZoom(15);
                }else{
                    mapRef.value.map.setCenter(default_location.value);
                    mapRef.value.map.setZoom(15);
                }
            }
        });
        watch(() => props.polyline, () => {
            polyLine.value = props.polyline;
            onMapLoad()
        });



        const handleDragEnd = (event,type,index=null) => {
            const lat = event.latLng.lat();
            const lng = event.latLng.lng();
            const position = {lat:lat,lng:lng};

            reverseGeocode(position,type,index);

            if(type == 'pickup'){
                pickOption.value.position= position;

            }else if(type == 'drop'){
                dropOption.value.position= position;
            }else{
                stopOptions.value[index].position = position;
            }
        }

        const reverseGeocode =(position,type,index=null)=>{
            
            const geocoder = new google.maps.Geocoder();

            geocoder.geocode({ location: position }, (results, status) => {

                if (status === 'OK' && results[0]) {
                const address = results[0].formatted_address;
                
                emit('update-pickup-address', address,type,position,index);
            

                }

            });
            

        }
        return {
            default_location,
            pickOption,
            dropOption,
            stopOptions,
            pathCoordinates,
            currentdriverOption,
            driverOptions,
            handleDragEnd,
            reverseGeocode,
            mapRef,
        }
    }
};
</script>

<template>
    <div class="map-container">
        <GoogleMap
            ref="mapRef"
            :api-key="map_key"
            mapId="DEMO_MAP_ID"
            style="width: 100%; height: 100%"
            :center="default_location"
            :zoom="12"
            :libraries="libraries"
        >
            

            <AdvancedMarker
                v-if="pickOption"
                :options="pickOption"
                @dragend="(e) => handleDragEnd(e, 'pickup')"
            />


            <AdvancedMarker
                v-if="dropOption"
                :options="dropOption"
                @dragend="(e) => handleDragEnd(e, 'drop')"
            />


            <AdvancedMarker
                v-if="currentdriverOption?.option"
                :options="currentdriverOption.option"
                @click="currentdriverOption.showInfo = !currentdriverOption.showInfo"
            />

            <!-- Stops -->
            <template v-for="(stop, i) in stopOptions" :key="i">
            <AdvancedMarker
                :options="stop.options"
                @dragend="e => handleDragEnd(e,'stop', i)"
            />
            </template>

            <!-- Nearby Drivers -->
            <template v-for="(drv, i) in driverOptions" :key="'drv'+i">
            <AdvancedMarker :options="drv.options" @click="drv.showInfo = !drv.showInfo">
                <InfoWindow
                    v-if="drv.info.length"
                    :options="{
                        content: drv.info
                    }"
                    :open="drv.showInfo"
                    @closeclick="drv.showInfo = false"
                />
            </AdvancedMarker>
            </template>

            
            <Polyline
                v-if="pathCoordinates.length"
                :options="{
                path: pathCoordinates,
                strokeColor: '#FF0000',
                strokeOpacity: 1.0,
                strokeWeight: 4
                }"
            />


        </GoogleMap>
    </div>
</template>

<style>
.map-container {
  width: 100%;
  height: 100%;
  position: relative;
}
</style>
