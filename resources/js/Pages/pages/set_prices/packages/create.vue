<template>
    <Layout>
      <Head title="Set Prices" />
      <PageHeader :title="zoneTypePackage ? $t('edit') : $t('create')" :pageTitle="$t('set_prices')" pageLink="/set-prices"/>
      <BRow>
        <BCol lg="12">
          <BCard no-body id="tasksList">
            <BCardHeader class="border-0">
              <a href="" class="text-success text-decoration-underline text-decoration-underline-success float-end heart"
               data-bs-toggle="modal" data-bs-target="#priceCalculation">{{$t("how_it_works")}}</a>
            </BCardHeader>
            <BCardBody class="border border-dashed border-end-0 border-start-0">
              <form @submit.prevent="handleSubmit">
                <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                  <div class="row">
                    <input type="hidden" class="form-control" id="zone_type_price_id" v-model.number="form.zone_type_price_id">
  
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="select_package_type" class="form-label">{{$t("package_type")}}
                          <span class="text-danger">*</span>
                        </label>
                        <select id="package_type" class="form-select" v-model="form.package_type_id">
                          <option disabled value="">{{$t("select_package_type")}}</option>
                          <option v-for="packageType in packageTypes" :key="packageType.id" :value="packageType.id">{{ packageType.name }}</option>
                        </select>
                        <span v-for="(error, index) in errors.package_type_id" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="base_price" class="form-label">{{$t("rental_base_price")}}
                          <span class="text-danger">* </span>
                          <!-- ({{$t("kilo_meter")}}) -->
                        </label>
                        <input type="number" step="any" class="form-control" :placeholder="$t('enter_rental_base_price')" id="base_price" v-model.number="form.base_price">
                        <span v-for="(error, index) in errors.base_price" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="base_distance" class="form-label">{{$t("free_distance")}}
                          <span class="text-danger">*</span>
                          <span v-if = "unit">({{ unit }})</span>
                        </label>
                        <input type="number" step="any" class="form-control" :placeholder="$t('enter_free_distance')" id="base_distance" v-model.number="form.base_distance">
                        <span v-for="(error, index) in errors.base_distance" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>                    
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="distance_price_per_km" class="form-label">{{$t("distance_price")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="any" class="form-control" :placeholder="$t('enter_price_per_distance')" id="distance_price_per_km" v-model.number="form.distance_price_per_km">
                        <span v-for="(error, index) in errors.distance_price_per_km" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="free_min" class="form-label">{{$t("free_time_in_minute")}}</label>
                        <input type="number" step="any" class="form-control" :placeholder="$t('enter_free_min')" id="free_min" v-model.number="form.free_min
                        ">
                        <span v-for="(error, index) in errors.free_min" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="time_price_per_min" class="form-label">{{$t("time_price")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="any" class="form-control" :placeholder="$t('enter_time_price')" id="time_price_per_min" v-model.number="form.time_price_per_min">
                        <span v-for="(error, index) in errors.time_price_per_min" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="admin_commission_type" class="form-label">{{$t("admin_commission_type_from_customer")}}
                          <span class="text-danger">*</span>
                        </label>
                        <select id="admin_commission_type" class="form-select" v-model="form.admin_commission_type">
                          <option disabled value="">{{$t('select_admin_commission_type_from_customer')}}</option>
                          <option value="1">{{$t('percentage')}}</option>
                          <option value="2">{{$t('fixed_amount')}}</option>
                        </select>
                        <span v-for="(error, index) in errors.admin_commission_type" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="admin_commission" class="form-label">{{$t("admin_commission_from_customer")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" min=0 step="any" class="form-control" :placeholder="$t('enter_admin_commission_from_customer')" id="admin_commission" v-model.number="form.admin_commission" :max="form.admin_commission_type == '1' ?  100: null ">
                        <span v-for="(error, index) in errors.admin_commission" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="admin_commission_type_from_driver" class="form-label">{{$t("admin_commission_type_from_driver")}}
                          <span class="text-danger">*</span>
                        </label>
                        <select id="admin_commission_type_from_driver" class="form-select" v-model="form.admin_commission_type_from_driver">
                          <option disabled value="">{{$t('select_admin_commission_type_from_driver')}}</option>
                          <option value="1">{{$t('percentage')}}</option>
                          <option value="2">{{$t('fixed_amount')}}</option>
                        </select>
                        <span v-for="(error, index) in errors.admin_commission_type_from_driver" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="admin_commission_from_driver" class="form-label">{{$t("admin_commission_from_driver")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" min=0 step="any" class="form-control" :placeholder="$t('enter_admin_commission_from_driver')" id="admin_commission_from_driver" v-model.number="form.admin_commission_from_driver" :max="form.admin_commission_type_from_driver == '1' ?  100: null ">
                        <span v-for="(error, index) in errors.admin_commission_from_driver" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="admin_commission_type_from_owner" class="form-label">{{$t("admin_commission_type_from_owner")}}
                          <span class="text-danger">*</span>
                        </label>
                        <select id="admin_commission_type_from_owner" class="form-select" v-model="form.admin_commission_type_from_owner">
                          <option disabled value="">{{$t('select_admin_commission_type_from_owner')}}</option>
                          <option value="1">{{$t('percentage')}}</option>
                          <option value="2">{{$t('fixed_amount')}}</option>
                        </select>
                        <span v-for="(error, index) in errors.admin_commission_type_from_owner" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="admin_commission_from_owner" class="form-label">{{$t("admin_commission_from_owner")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" min=0 step="any" class="form-control" :placeholder="$t('enter_admin_commission_from_owner')" id="admin_commission_from_owner" v-model.number="form.admin_commission_from_owner" :max="form.admin_commission_type_from_owner == '1' ?  100: null ">
                        <span v-for="(error, index) in errors.admin_commission_from_owner" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="mb-3">
                        <label for="service_tax" class="form-label">{{$t("service_tax")}}
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" min=0 max=100 class="form-control":placeholder="$t('enter_service_tax')" id="service_tax" v-model.number="form.service_tax">
                        <span v-for="(error, index) in errors.service_tax" :key="index" class="text-danger">{{ error }}</span>
                      </div>
                    </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="cancellation_fee" class="form-label">{{$t("cancellation_fee")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="number" class="form-control"  :placeholder="$t('cancellation_fee')"  id="cancellation_fee" v-model.number="form.cancellation_fee">
                      <span v-for="(error, index) in errors.cancellation_fee" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>

                    <div class="col-12 text-end">
                      <button type="submit" class="btn btn-success">{{$t("save")}}</button>
                    </div>
                  </div>
                </FormValidation>
              </form>
              <!-- <div v-if="successMessage" class="alert alert-success alert-dismissible mt-3" role="alert">
                <button type="button" class="btn-close" aria-label="Close" @click="dismissMessage"></button>
                {{ successMessage }}
              </div>
              <div v-if="alertMessage" class="alert alert-danger alert-dismissible mt-3" role="alert">
                <button type="button" class="btn-close" aria-label="Close" @click="dismissMessage"></button>
                {{ alertMessage }}
              </div> -->
              <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" role="alert" id="alertMsg">
                <div class="alert-content">
                  <i class="ri-notification-off-line me-3 align-middle"></i>
                  <strong>Success</strong> - {{ successMessage }}
                  <button type="button" class="btn-close btn-close-success" @click="dismissMessage" aria-label="Close Success Message"></button>
                </div>
              </div>
              <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" role="alert" id="alertMsg">
                <div class="alert-content">
                  <i class="ri-notification-off-line me-3 align-middle"></i>
                  <strong>Alert</strong> - {{ alertMessage }}
                  <button type="button" class="btn-close btn-close-danger" @click="dismissMessage" aria-label="Close Alert Message"></button>
                </div>
              </div>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>

      <!-- priceCalculation % Modals -->
      <div id="priceCalculation" class="modal fade" tabindex="-1" aria-labelledby="lowLabel" aria-hidden="true" style="display: none;">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="myModalLabel">Rental Commission</h5>
                      <button type="button" @click="showCommission = false" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
                  </div>
                  <div class="modal-body">
                      <h5 class="fs-15">
                        Rental Commission Calculation
                      </h5>
                      <p class="text-muted"> Subtotal = <strong>150</strong>
                        <span  v-if="showCommission"> <strong> = {{ calculatedPrice?.subtotal ?? 0 }}</strong></span>
                      </p>
                      <p class="text-muted"> {{$t("service_tax")}} = <strong>10 % = $15</strong>
                        <span  v-if="showCommission"> <strong> = {{ calculatedPrice?.tax ?? 0 }}</strong></span>
                      </p>
                      <p class="text-muted"> {{ calculatedPrice.forDriver ? $t("driver_convenience_fee") : $t("owner_convenience_fee") }} = <strong>15 % = $22.5</strong>
                        <span  v-if="showCommission"> <strong> = {{ calculatedPrice?.driver_commission ?? 0 }}</strong></span>
                      </p>
                      <p class="text-muted"> {{$t("convenience_fee")}} = <strong>15 % = $22.5</strong>
                        <span  v-if="showCommission"> <strong> = {{ calculatedPrice?.commission ?? 0 }}</strong></span>
                      </p>
                      <p class="text-muted"> {{$t("rental_base_price")}} = <strong>$210</strong>
                       <strong> = {{ form.base_price ?? 0 }}</strong>
                      </p>
                      <div class="form-check form-switch text-muted form-check-right">
                          <input class="form-check-input" type="checkbox" role="switch" @click="forDriver = !forDriver" id="toggle_for_driver" :checked="forDriver">
                          <label class="form-check-label" for="toggle_for_driver"> {{ $t('toggle_for_driver') }}</label>
                      </div>
                  </div>
                  <div class="modal-footer">
                      <button type="button" @click="showCommission = false" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                      <button type="button" v-if="showCommission" class="btn btn-info" @click="showCommission = false">Hide</button>
                      <button type="button" class="btn btn-info" @click="calculateRentalPrice">Calculate</button>
                  </div>

              </div><!-- /.modal-content -->
          </div><!-- /.modal-dialog -->
      </div><!-- /.modal -->
      <!-- modal end -->

    </Layout>
  </template>
  
  <script>
  import { Head, useForm, router } from '@inertiajs/vue3';
  import Layout from "@/Layouts/main.vue";
  import PageHeader from "@/Components/page-header.vue";
  import Pagination from "@/Components/Pagination.vue";
  import { ref, onMounted } from "vue";
  import axios from "axios";
  import FormValidation from "@/Components/FormValidation.vue";
  import { useI18n } from 'vue-i18n';

  export default {
    components: {
      Layout,
      PageHeader,
      Head,
      Pagination,
      FormValidation,
    },
    props: {
      successMessage: String,
      alertMessage: String,
      packageTypes: Array,
      zoneTypePrice: Object,
      zoneTypePackage: Object,
      zone_unit:Object,
    },
    setup(props) {
      const { t } = useI18n();
      const form = useForm({
        zone_type_price_id: props.zoneTypePrice?.id || "",
        package_type_id: props.zoneTypePackage?.package_type_id || "",
        base_price: props.zoneTypePackage?.base_price || "",
        distance_price_per_km: props.zoneTypePackage?.distance_price_per_km || "",
        free_min: props.zoneTypePackage?.free_min || "",
        time_price_per_min: props.zoneTypePackage?.time_price_per_min || "",
        base_distance: props.zoneTypePackage?.free_distance || "",
        cancellation_fee: props.zoneTypePackage?.cancellation_fee || "",
        admin_commission_type: props.zoneTypePackage ? props.zoneTypePackage.admin_commission_type || "" : "",
        admin_commission: props.zoneTypePackage ? props.zoneTypePackage.admin_commission ?? 0 : "",
        admin_commission_type_from_driver: props.zoneTypePackage ? props.zoneTypePackage.admin_commission_type_from_driver || "" : "",
        admin_commission_from_driver: props.zoneTypePackage ? props.zoneTypePackage.admin_commission_from_driver ||  0 : "",
        admin_commission_type_from_owner: props.zoneTypePackage ? props.zoneTypePackage.admin_commission_type_from_owner || "" : "",
        admin_commission_from_owner: props.zoneTypePackage ? props.zoneTypePackage.admin_commission_from_owner ||  0 : "",
        service_tax: props.zoneTypePackage ? props.zoneTypePackage.service_tax ||  0 : "",
      });
  
      const validationRules = {
        package_type_id: { required: true },
        base_price: { required: true },
        distance_price_per_km: { required: true },
        free_min: { required: true },
        time_price_per_min: { required: true },
        base_distance: { required: true },
        cancellation_fee: { required: true },
        admin_commission_type: { required: true },
        admin_commission: { required: true },
        admin_commission_type_from_driver: { required: true },
        admin_commission_from_driver: { required: true },
        admin_commission_type_from_owner: { required: true },
        admin_commission_from_owner: { required: true },
        service_tax: { required: true },
      };
  
      const validationRef = ref(null);
      const errors = ref({});
      const successMessage = ref(props.successMessage || '');
      const alertMessage = ref(props.alertMessage || '');
      const zone_unit = ref(props.zone_unit);
      const unit = ref();
  
      const handleSubmit = async () => {
        errors.value = validationRef.value.validate(form);
        if (Object.keys(errors.value).length > 0) {
          return;
        }
        try {
          let response;
          if (props.zoneTypePackage && props.zoneTypePackage.id) {
            response = await axios.post(`/set-prices/packages/update/${props.zoneTypePackage.id}`, form);
          } else {
            response = await axios.post('/set-prices/packages/store', form);
          }
          if (response.status === 201) {
            successMessage.value = t('package_price_created_successfully');
            // form.reset();
            router.get(`/set-prices/packages/${props.zoneTypePrice.zone_type_id}`);
          } else {
            alertMessage.value = t('failed_to_create_package_price');
          }
        } catch (error) {
          if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
          } else {
            console.error(t('error_creating_package_price'), error);
            alertMessage.value = t('failed_to_create_package_price');
          }
        }
      };
  
      const dismissMessage = () => {
        successMessage.value = "";
        alertMessage.value = "";
      };
        onMounted(() => {

          if (zone_unit.value == 1) {
            unit.value =  t('kilo_meter'); // Unit 1 corresponds to kilometers
          } else if (zone_unit.value == 2) {
            unit.value = t('miles'); // Unit 2 corresponds to miles
          }

      });
  
      const showCommission = ref(false);
      const forDriver = ref(true);
      const calculatedPrice = ref({});
      const calculateRentalPrice = () =>{

        let subtotal = 0;
        let commission = 0;
        let driver_commission = 0;
        let tax = 0;

        let total = form.base_price;
        let percentageCharges = 100 + (form.service_tax ?? 0);
        let fixedCharges = 0;

        if(form.admin_commission > 0){
          if(form.admin_commission_type == 1){
            percentageCharges += form.admin_commission;
          }else{
            fixedCharges += form.admin_commission;
          }
        }

        if(forDriver.value){
          if(form.admin_commission_from_driver > 0){
            if(form.admin_commission_type_from_driver == 1){
              percentageCharges += form.admin_commission_from_driver;
            }else{
              fixedCharges += form.admin_commission_from_driver;
            }
          }
        }else{
          if(form.admin_commission_from_owner > 0){
            if(form.admin_commission_type_from_owner == 1){
              percentageCharges += form.admin_commission_from_owner;
            }else{
              fixedCharges += form.admin_commission_from_owner;
            }
          }
        }


        if(fixedCharges){
          total -= fixedCharges;
        }

        subtotal = total*100/(percentageCharges);

        if(form.admin_commission_type == 1){
          commission += (subtotal * form.admin_commission/100);
        }else{
          commission += form.admin_commission;
        }

        if(forDriver.value){
          if(form.admin_commission_from_driver > 0){
            if(form.admin_commission_type_from_driver == 1){
              driver_commission += (subtotal * form.admin_commission_from_driver/100);
            }else{
              driver_commission += form.admin_commission_from_driver;
            }
          }
        }else{
          if(form.admin_commission_from_owner > 0){
            if(form.admin_commission_type_from_owner == 1){
              driver_commission += (subtotal * form.admin_commission_from_owner/100);
            }else{
              driver_commission += form.admin_commission_from_owner;
            }
          }
        }

        tax = subtotal * form.service_tax/100


        calculatedPrice.value = {
          subtotal: subtotal.toFixed(2),
          commission: commission.toFixed(2),
          driver_commission: driver_commission.toFixed(2),
          forDriver: forDriver.value,
          tax: tax.toFixed(2),
        };

        showCommission.value = true;
      };
      return {
        form,
        packageTypes: ref(props.packageTypes || []),
        successMessage,
        alertMessage,
        handleSubmit,
        dismissMessage,
        validationRules,
        validationRef,
        errors,
        unit,
        showCommission,
        calculatedPrice,
        calculateRentalPrice,
        forDriver,
      };
    },
  };
  </script>
  
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
  