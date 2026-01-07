<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import { ref, watch, onMounted } from "vue";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import '@vueform/multiselect/themes/default.css';
import FormValidation from "@/Components/FormValidation.vue";
import { useI18n } from 'vue-i18n';

export default {
  data() {
    return {
    }
  },
  components: {
    Layout,
    PageHeader,
    Head,
    // Pagination,
    Multiselect,
    FormValidation,
  },
  props: {
    successMessage: String,
    alertMessage: String,
    zones: Array,
    zoneTypePrice: Object,
    zoneType: Object,
    setprice: Object,
  },
  setup(props) {
    const { t } = useI18n();
    const form = useForm({
      zone_id: props.zoneType ? props.zoneType.zone_id || "" : props.setprice?.zone_id,
      transport_type: props.zoneType ? props.zoneType.transport_type || "" : props.setprice?.transport_type,
      vehicle_type: props.zoneType ? props.zoneType.type_id || "" : props.setprice?.type_id,
      payment_type: props.zoneType ? props.zoneType.payment_type.split(',') || [] : [],
      admin_commision_type: props.zoneType ? props.zoneType.admin_commision_type || "" : "",
      admin_commision: props.zoneType ? props.zoneType.admin_commision ?? 0 : "",
      admin_commission_type_from_driver: props.zoneType ? props.zoneType.admin_commission_type_from_driver || "" : "",
      admin_commission_from_driver: props.zoneType ? props.zoneType.admin_commission_from_driver ||  0 : "",
      admin_commission_type_for_owner: props.zoneType ? props.zoneType.admin_commission_type_for_owner || "" : "",
      admin_commission_for_owner: props.zoneType ? props.zoneType.admin_commission_for_owner ||  0 : "",
      service_tax: props.zoneType ? props.zoneType.service_tax ||  0 : "",
      order_number: props.zoneType ? props.zoneType.order_number ||  0 : "",
      base_price: props.zoneTypePrice ? props.zoneTypePrice.base_price ||  0 : "",
      drop_zone: props.zoneType ? props.zoneType.drop_zone || "" : "",
    });


    const validationRules = {
      zone_id: { required: true },
      transport_type: { required: true },
      vehicle_type: { required: true },
      payment_type: { required: true },
      admin_commision_type: { required: true },
      admin_commision: { required: true },
      admin_commission_type_from_driver: { required: true },
      admin_commission_from_driver: { required: true },
      admin_commission_type_for_owner: { required: true },
      admin_commission_for_owner: { required: true },
      service_tax: { required: true },
      order_number: { required: true },
      base_price: { required: true },
      drop_zone: { required: true },
    };

    const validationRef = ref(null);
    const errors = ref({});
    const successMessage = ref(props.successMessage || '');
    const alertMessage = ref(props.alertMessage || '');
    const unit =  ref();
    const zones =  ref(props.zones);

    // const transportTypes = ['taxi', 'delivery', 'both'];


    const capitalizeFirstLetter = (word) => {
      return word.charAt(0).toUpperCase() + word.slice(1);
    };

    const dismissMessage = () => {
      successMessage.value = "";
      alertMessage.value = "";
    };

    const handleSubmit = async () => {
      errors.value = validationRef.value.validate(form);
      if (Object.keys(errors.value).length > 0) {
        return;
      }
      try {
        let response;
        if (props.zoneTypePrice && props.zoneTypePrice.id) {
          response = await axios.post(`/farefix/update/${props.zoneTypePrice.id}`, form.data());
        } else {
          response = await axios.post('/farefix/store', form.data());
        }
        if (response.status === 201) {
          successMessage.value = t('vehicle_price_created_successfully');
          form.reset();
          router.get(`/farefix/${props.setprice.id}`);
        } else {
          alertMessage.value = t('failed_to_create_vehicle_price');
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          errors.value = error.response.data.errors;
        } else if (error.response && error.response.status == 403) {
          alertMessage.value = error.response.data.alertMessage;
          setTimeout(()=>{
            router.get(`/farefix/${props.setprice.id}`);
          },5000)
        } else {
          console.error(t('error_creating_vehicle_price'), error);
          alertMessage.value = t('failed_to_create_vehicle_price');
        }
      }
    };

    return {
      form,
      zones,
      // transportTypes,
      successMessage,
      alertMessage,
      handleSubmit,
      dismissMessage,
      validationRules,
      validationRef,
      errors,
      capitalizeFirstLetter,
      unit,
    };
  }
};
</script>



<template>
  <Layout>
    <Head :title="$t('farefix')" />
    <PageHeader :title="zoneTypePrice ? $t('edit') : $t('create')"  :pageTitle="$t('set_prices')"  :pageLink="`/farefix/${setprice.id}`" />
    <BRow>
      <BCol lg="12">
        <BCard no-body id="tasksList">
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <form @submit.prevent="handleSubmit">
              <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                <div class="row">
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="select_zone" class="form-label">{{$t("zone")}}
                      </label>
                      <select id="select_zone" disabled class="form-select" v-model="form.zone_id">
                        <option :key="setprice.zone_id" :value="setprice.zone_id" selected>{{ setprice.zone_name }}</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="select_vehicle_type" class="form-label">{{$t("vehicle_type")}}
                      </label>
                      <select id="select_vehicle_type" disabled class="form-select" v-model="form.vehicle_type">
                        <option :key="setprice.type_id" :value="setprice.type_id" selected>{{ setprice.vehicle_type_name }}</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="select_drop_zone" class="form-label">{{$t("drop_zone")}}
                        <span class="text-danger">*</span>
                      </label>
                      <select id="select_drop_zone" class="form-select" v-model="form.drop_zone">
                        <option disabled value="">{{$t('select_zone')}}</option>
                        <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.name }}</option>
                      </select>
                      <span v-for="(error, index) in errors.drop_zone" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="base_price" class="form-label">{{$t("base_price")}}
                        <span class="text-danger">*  </span>
                        <!-- ({{$t("kilo_meter")}}) -->
                      </label>
                      <input type="number" step="any" class="form-control"  :placeholder="$t('enter_base_price')" id="base_price" v-model.number="form.base_price">
                      <span v-for="(error, index) in errors.base_price" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="admin_commision_type" class="form-label">{{$t("admin_commission_type_from_customer")}}
                        <span class="text-danger">*</span>
                      </label>
                      <select id="admin_commision_type" class="form-select" v-model="form.admin_commision_type">
                        <option disabled value="">{{$t('select_admin_commission_type_from_customer')}}</option>
                        <option value="1">{{$t('percentage')}}</option>
                        <option value="2">{{$t('fixed_amount')}}</option>
                      </select>
                      <span v-for="(error, index) in errors.admin_commision_type" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="admin_commision" class="form-label">{{$t("admin_commission_from_customer")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="number" step="any" class="form-control" :placeholder="$t('enter_admin_commission_from_customer')" id="admin_commision" v-model.number="form.admin_commision" :max="form.admin_commision_type == '1' ?  100: null ">
                      <span v-for="(error, index) in errors.admin_commision" :key="index" class="text-danger">{{ error }}</span>
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
                      <input type="number" step="any" class="form-control" :placeholder="$t('enter_admin_commission_from_driver')" id="admin_commission_from_driver" v-model.number="form.admin_commission_from_driver" :max="form.admin_commission_type_from_driver == '1' ?  100: null ">
                      <span v-for="(error, index) in errors.admin_commission_from_driver" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="admin_commission_type_for_owner" class="form-label">{{$t("admin_commission_type_for_owner")}}
                        <span class="text-danger">*</span>
                      </label>
                      <select id="admin_commission_type_for_owner" class="form-select" v-model="form.admin_commission_type_for_owner">
                        <option disabled value="">{{$t('select_admin_commission_type_for_owner')}}</option>
                        <option value="1">{{$t('percentage')}}</option>
                        <option value="2">{{$t('fixed_amount')}}</option>
                      </select>
                      <span v-for="(error, index) in errors.admin_commission_type_for_owner" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="admin_commission_for_owner" class="form-label">{{$t("admin_commission_for_owner")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="number" step="any" class="form-control" :placeholder="$t('enter_admin_commission_for_owner')" id="admin_commission_for_owner" v-model.number="form.admin_commission_for_owner" :max="form.admin_commission_type_for_owner == '1' ?  100: null ">
                      <span v-for="(error, index) in errors.admin_commission_for_owner" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="select_payment_type" class="form-label">{{$t("payment_type")}}
                        <span class="text-danger">*</span>
                      </label>
                      <Multiselect 
                        id="select_payment_type" 
                        mode="tags" 
                        v-model="form.payment_type" 
                        :close-on-select="false"
                        :searchable="true" 
                        :create-option="false"
                        :options="[
                          { value: 'cash', label: $t('cash') },
                          { value: 'online', label: $t('online') },
                          { value: 'wallet', label: $t('wallet') },
                        ]"
                        :placeholder="$t('select_payment_type')"
                      />
                      <span v-for="(error, index) in errors.payment_type" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="service_tax" class="form-label">{{$t("service_tax")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="number" step="any" class="form-control":placeholder="$t('enter_service_tax')" id="service_tax" v-model.number="form.service_tax">
                      <span v-for="(error, index) in errors.service_tax" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="order_number" class="form-label">{{$t("order_number")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="number" step="any" class="form-control":placeholder="$t('enter_order_number')" id="order_number" v-model.number="form.order_number">
                      <span v-for="(error, index) in errors.order_number" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success">{{$t("save")}}</button>
                  </div>
                </div>
              </FormValidation>
            </form>
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

.heart {
	animation: beat .25s infinite alternate;
	transform-origin: center;
}
@keyframes beat{
	to { transform: scale(1.2); }
}

</style>
