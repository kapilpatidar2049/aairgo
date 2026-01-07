<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import { ref, watch } from "vue";
import axios from "axios";
import imageUpload from "@/Components/widgets/imageUpload.vue";
import Multiselect from "@vueform/multiselect";
import FormValidation from "@/Components/FormValidation.vue";
import { useI18n } from 'vue-i18n';
import { BCard, BCardBody } from 'bootstrap-vue-next';

export default {
  components: {
    Layout,
    PageHeader,
    Head,
    Pagination,
    Multiselect,
    FormValidation,
    imageUpload,
  },
  props: {
    successMessage: String,
    alertMessage: String,
    referral_commission_amount_for_user: Object,
    enable_user_referral_earnings: Object,
    app_for: String,
    validate: Function,
    referral_type:Object,
    referral_commission_for_new_user_from_referer_user:Object,
    referral_commission_for_new_driver_from_referer_user:Object,
    enable_referral_condition_by_ride_count:Object,
    enable_referral_condition_by_earning:Object,
    referral_condition_user_ride_count:Object,
    referral_condition_driver_ride_count:Object,
    referral_condition_user_spent_amount:Object,
    referral_condition_driver_earning_amount:Object,
  },
  setup(props) {
    const { t } = useI18n();

    const form = useForm({
      enable_user_referral_earnings: props.enable_user_referral_earnings ?? false,
      referral_commission_amount_for_user: props.referral_commission_amount_for_user?.value ?? "",
      referral_type: props.referral_type?.value ?? "",
      referral_commission_for_new_user_from_referer_user: props.referral_commission_for_new_user_from_referer_user?.value ?? "",
      referral_commission_for_new_driver_from_referer_user: props.referral_commission_for_new_driver_from_referer_user?.value ?? "",
      enable_referral_condition_by_ride_count:props.enable_referral_condition_by_ride_count ?? false,
      enable_referral_condition_by_earning:props.enable_referral_condition_by_earning ?? false,
      referral_condition_user_ride_count:props.referral_condition_user_ride_count?.value ?? "",
      referral_condition_driver_ride_count:props.referral_condition_driver_ride_count?.value ?? "",
      referral_condition_user_spent_amount:props.referral_condition_user_spent_amount?.value ?? "",
      referral_condition_driver_earning_amount:props.referral_condition_driver_earning_amount?.value ?? "",
      
    });

    const validationRef = ref(null);
    const errors = ref({});
    const successMessage = ref(props.successMessage || '');
    const alertMessage = ref(props.alertMessage || '');

    const dismissMessage = () => {
      successMessage.value = "";
      alertMessage.value = "";
    };

    const handleSubmit = async () => {
      errors.value = validationRef.value.validate();

      if (Object.keys(errors.value).length > 0) {
        return;
      }

      try {
        const response = await axios.post(`/referral-settings/update`, form.data());
        console.log("response",response);

        if (response.status === 200) {
          successMessage.value = t('referral_settings_updated_successfully');
          router.get('/referral-settings');
        } else {
          alertMessage.value = t('failed_to_update_referral_settings');
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          errors.value = error.response.data.errors;
        } else {
          console.error(t('error_updating_referral_settings'), error);
          alertMessage.value = t('failed_to_update_referral_settings');
        }
      }
    };

    const handleToggle = async (event) => {
      const isChecked = event.target.checked;
      const dataKey = event.target.getAttribute('data-key');

      form[dataKey] = isChecked; // Update form value based on checkbox state

      try {
        const response = await axios.post(`/referral-settings/toggle`, {
          key: dataKey,
          enabled: isChecked,
        });

        if (response.status === 200) {
          successMessage.value = t('referral_settings_toggled_successfully');
        } else {
          alertMessage.value = t('failed_to_toggle_referral_settings');
        }
      } catch (error) {
        console.error(t('error_toggling_referral_settings'), error);
        alertMessage.value = t('failed_to_toggle_referral_settings');
      }
    };

    // Watch for changes in enable_user_referral_earnings
    watch(() => form.enable_user_referral_earnings, (newValue) => {
      console.log("Updated enable_user_referral_earnings:", newValue);
    });

    return {
      form,
      successMessage,
      alertMessage,
      handleSubmit,
      dismissMessage,
      handleToggle,
      validationRef,
      errors,
    };
  },
  methods: {
  onToggleCondition(type) {
    if (type === 'ride') {
      if (this.form.enable_referral_condition_by_ride_count) {
        this.form.enable_referral_condition_by_earning = false;
      }
    } else if (type === 'earning') {
      if (this.form.enable_referral_condition_by_earning) {
        this.form.enable_referral_condition_by_ride_count = false;
      }
    }
  },
}
};
</script>

<template>
  <Layout>
    <Head title="User Referral Settings" />
    <PageHeader :title="$t('user-referral-settings')" :pageTitle="$t('user-referral-settings')" />
    <BRow>
      <BCol lg="12">
        <BCard v-if="app_for === 'demo'" no-body id="tasksList">
          <BCardHeader class="border-0">
            <div class="alert bg-warning border-warning fs-18" role="alert">
              <strong> {{$t('note')}} : <em> {{$t('actions_restricted_due_to_demo_mode')}}</em> </strong>
            </div>
          </BCardHeader>
        </BCard>
 
        <!-- </BCard> -->
        <BCard no-body id="tasksList">
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <form @submit.prevent="handleSubmit">
              <FormValidation :form="form" :rules="validationRules" ref="validationRef">

                <!-- User Referral Earnings Setup -->
                <section class="row">
                  <div class="accordion custom-accordionwithicon-plus" id="accordionWithplusiconUser">
                    <div class="accordion-item">
                      <div class="accordion-header" id="accordionwithplusExampleUser">
                        <div class="card border rounded">
                          <div class="card-body">
                            <div class="d-flex mt-1">
                              <div>
                                <h5>{{ $t("user_referral_earnings_setup") }}</h5>
                                <p class="fs-12">
                                {{ $t("invite_others_to_use_our_app_with_your_unique_referral_code_and_earn_exciting_rewards") }}
                                </p>
                              </div>
                              <div class="ms-auto">
                                <div class="form-check form-switch form-switch-lg mt-2">
                                  <input
                                    class="form-check-input"
                                    type="checkbox"
                                    :disabled="app_for == 'demo'"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#showContentUser"
                                    aria-expanded="true"
                                    aria-controls="showContentUser"
                                    @change="handleToggle"
                                    data-key="enable_user_referral_earnings"
                                    aria-label="Toggle User Referral Earnings Setup"
                                    v-model="form.enable_user_referral_earnings"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Referral Type (Shown only if referral enabled) -->
                      <div v-if="form.enable_user_referral_earnings" class="row p-2">
                        <div class="col-sm-6">
                          <div class="mb-3">
                            <label for="referral_type" class="form-label">{{ $t("referral_type") }} </label>
                            <div class="tooltip-wrapper">
                              <i class="ri-information-line"></i>
                              <div class="tooltip-text">
                                <b>{{ $t("instant_for_referrer_user") }}:</b> {{ $t( "referrer_user_get_rewards_after_new_user_sign_up")}}<br>
                                <b>{{ $t("instant_for_referrer_user_and_new_user") }}:</b> {{ $t("reward_details_for_referral_user_and_new_user")}} <br>
                                <b>{{ $t("conditional_for_referrer_user") }}:</b> {{ $t("referrer_reward_datails_after_new_user_meets_ride_count_and_earnings_condition")}} <br>
                                <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b> {{ $t("both_referer_and_referred_user_get_rewards_after_condition_is_met")}}
                              </div>
                            </div>
                            <select
                              id="referral_type"
                              :disabled="app_for === 'demo'"
                              class="form-select"
                              v-model="form.referral_type"
                            >
                              <option disabled value="">{{ $t("select") }}</option>
                              <option value="instant">{{ $t("instant_for_referrer_user") }}</option>
                              <option value="dual_instant">{{ $t("instant_for_referrer_user_and_new_user") }}</option>
                              <option value="conditional">{{ $t("conditional_for_referrer_user") }}</option>
                              <option value="dual_conditional">{{ $t("conditional_for_referrer_user_and_new_user") }}</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <!-- Accordion Content -->
                      <div
                        id="showContentUser"
                        class="accordion-collapse collapse"
                        :class="{ show: form.enable_user_referral_earnings }"
                        aria-labelledby="accordionwithplusExampleUser"
                        data-bs-parent="#accordionWithplusiconUser"
                      >
                        <div class="accordion-body">
                          <div class="card border rounded">
                            <div class="card-header accordion-body p-3">
                              
                              <!-- 1️⃣ Referrer Input Field -->
                              <div
                                class="row"
                                v-if="['instant','dual_instant','conditional','dual_conditional'].includes(form.referral_type)"
                              >
                                <div class="col-lg-6">
                                  <h6>{{ $t("who_share_the_code_when_user_reffers_user") }}</h6>
                                  <p class="fs-12">{{ $t("offer_a_reward_to_users_for_each_referral_when_they_share_their_code")}}</p>
                                </div>
                                <div class="col-lg-6">
                                  <div class="border p-4 rounded bg-light">
                                    <label for="referral_commission_amount_for_user" class="form-label">
                                      {{ $t("earnings_to_each_referral") }}
                                    </label>
                                    <input
                                      type="text"
                                      class="form-control"
                                      placeholder="Enter the Amount"
                                      id="referral_commission_amount_for_user"
                                      :readonly="app_for == 'demo'"
                                      v-model="form.referral_commission_amount_for_user"
                                    />
                                    <small class="form-text text-muted">
                                    {{ $t("enter_the_amount_users_earn_for_each_referral")}}
                                    </small>
                                  </div>
                                </div>
                              </div>

                              <!-- 2️⃣ Dual Instant Extra Fields -->
                              <div
                                class="row mt-3"
                                v-if="form.referral_type === 'dual_instant'"
                              >
                                <div class="col-lg-6">
                                  <h6>{{ $t("user_who_use_the_refer_code_when_user_refer") }}</h6>
                                  <p class="fs-12">{{ $t("offer_a_reward_to_new_users_and_drivers_for_using_referral_code")}}</p>
                                </div>
                                <div class="col-lg-6">
                                  <div class="border p-4 rounded bg-light mb-3">
                                    <label for="referral_commission_for_new_user_from_referer_user" class="form-label">
                                      {{ $t("earnings_to_new_signup_user") }}
                                    </label>
                                    <div class="tooltip-wrapper">
                                      <i class="ri-information-line"></i>
                                      <div class="tooltip-text">
                                        <b>{{ $t("instant_for_referrer_user_and_new_user") }}:</b> {{ $t("referred_user_get_reward_after_referred_user_sign_up")}}<br>
                                        <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b> {{ $t("referred_user_rewarded_after_meeting_ride_or_spend_condition")}}
                                      </div>
                                    </div>
                                    <input
                                      type="text"
                                      class="form-control"
                                      placeholder="Enter the Amount"
                                      id="referral_commission_for_new_user_from_referer_user"
                                      v-model="form.referral_commission_for_new_user_from_referer_user"
                                    />
                                  </div>
                                  <div class="border p-4 rounded bg-light">
                                    <label for="referral_commission_for_new_driver_from_referer_user" class="form-label">
                                      {{ $t("earnings_to_new_signup_driver") }}
                                    </label>
                                    <div class="tooltip-wrapper">
                                      <i class="ri-information-line"></i>
                                      <div class="tooltip-text">
                                        <b>{{ $t("instant_for_referrer_user_and_new_user") }}:</b> {{ $t("referred_driver_get_reward_after_referrer_driver_sign_up")}} <br>
                                        <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b> {{ $t("referred_driver_rewarded_after_meeting_ride_or_spend_condition")}}
                                      </div>
                                    </div>
                                    <input
                                      type="text"
                                      class="form-control"
                                      placeholder="Enter the Amount"
                                      id="referral_commission_for_new_driver_from_referer_user"
                                      v-model="form.referral_commission_for_new_driver_from_referer_user"
                                    />
                                  </div>
                                </div>
                              </div>

              <!-- 3️⃣ Conditional Logic -->
              <div
                class="row mt-3"
                v-if="form.referral_type === 'conditional' || form.referral_type === 'dual_conditional'"
              >
                <div class="col-lg-12">
                  <h6>{{ $t("referral_condition_settings") }}</h6>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="enable_referral_condition_by_ride_count"
                      v-model="form.enable_referral_condition_by_ride_count"
                      @change="onToggleCondition('ride')"
                    />
                    <label class="form-check-label" for="enable_referral_condition_by_ride_count">
                      Enable Condition by Ride Count
                    </label>
                  </div>
                  
                </div>

                                <!-- Ride Count Conditions -->
                                <div class="col-lg-6 mt-3" v-if="form.enable_referral_condition_by_ride_count">
                                  <div class="border p-3 rounded bg-light">
                                    <label class="form-label">{{$t("user_ride_count")}}</label>
                                    <div class="tooltip-wrapper">
                                      <i class="ri-information-line"></i>
                                      <div class="tooltip-text">
                                        <b>{{ $t("conditional_for_referrer_user") }}:</b> {{ $t("referrer_user_only_get_rewards_based_on_referred_user_ride_count_condition")}}<b>{{form.referral_condition_user_ride_count}} {{ $t("counts")}}</b><br>
                                        <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b>{{ $t("both_referrer_user_and_referred_user_get_rewards_based_on_ride_count_condition")}}<b>{{form.referral_condition_user_ride_count}} {{ $t("counts")}}</b>
                                      </div>
                                    </div>
                                    <input
                                      type="number"
                                      class="form-control"
                                      id="referral_condition_user_ride_count"
                                      placeholder="Enter the Ride Count"
                                      :readonly="app_for == 'demo'"
                                      v-model="form.referral_condition_user_ride_count"
                                    />
                                  </div>
                                </div>
                                <div class="col-lg-6 mt-3" v-if="form.enable_referral_condition_by_ride_count">
                                  <div class="border p-3 rounded bg-light">
                                    <label class="form-label">{{ $t("driver_ride_count")}}</label>
                                    <div class="tooltip-wrapper">
                                      <i class="ri-information-line"></i>
                                      <div class="tooltip-text">
                                        <b>{{ $t("conditional_for_referrer_user") }}:</b> {{ $t( "referrer_user_only_get_rewards_based_on_referred_driver_ride_count_condition")}} <b>{{form.referral_condition_user_ride_count}} {{ $t("counts")}}</b><br>
                                        <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b>{{ $t("both_referrer_user_and_referred_driver_get_rewards_based_on_ride_count_condition")}} <b>{{form.referral_condition_user_ride_count}} {{ $t("counts")}}</b>
                                      </div>
                                    </div>
                                    <input
                                      type="number"
                                      class="form-control"
                                      :readonly="app_for == 'demo'"
                                      placeholder="Enter the Ride Count"
                                      id="referral_condition_driver_ride_count"
                                      v-model="form.referral_condition_driver_ride_count"
                                    />
                                  </div>
                                </div>

                <!-- Earnings Conditions -->
                <div class="col-lg-12">
                <div class="form-check mt-5">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="enable_referral_condition_by_earning"
                      v-model="form.enable_referral_condition_by_earning"
                      @change="onToggleCondition('earning')"
                    />
                    <label class="form-check-label" for="enable_referral_condition_by_earning">
                      Enable Condition by Earnings
                    </label>
                  </div>
                </div>
                <div class="col-lg-6 mt-3" v-if="form.enable_referral_condition_by_earning">
                  <div class="border p-3 rounded bg-light">
                    <label class="form-label">User Spend Amount</label>
                    <div class="tooltip-wrapper">
                      <i class="ri-information-line"></i>
                      <div class="tooltip-text">
                        <b>{{ $t("conditional_for_referrer_user") }}:</b> {{ $t("referrer_user_only_get_rewards_based_on_referred_user_spent_amount_condition")}} <b>$ {{form.referral_condition_user_spent_amount}}</b> {{ $t(".")}}<br>
                        <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b> {{ $t("both_referrer_user_and_referred_user_get_rewards_based_on_spent_amount_condition")}} <b>$ {{form.referral_condition_user_spent_amount}}</b> {{ $t(".")}}
                      </div>
                    </div>
                    <input
                      type="number"
                      class="form-control"
                      :readonly="app_for == 'demo'"
                      id="referral_condition_user_spent_amount"
                      placeholder="Enter the Amount"
                      v-model="form.referral_condition_user_spent_amount"
                    />
                  </div>
                </div>
                <div class="col-lg-6 mt-3" v-if="form.enable_referral_condition_by_earning">
                  <div class="border p-3 rounded bg-light">
                    <label class="form-label">Driver Earning Amount</label>
                    <div class="tooltip-wrapper">
                      <i class="ri-information-line"></i>
                      <div class="tooltip-text">
                        <b>{{ $t("conditional_for_referrer_user") }}:</b> {{ $t("referrer_user_only_get_rewards_based_on_referred_driver_earning_amount_condition")}} <b>$ {{form.referral_condition_driver_earning_amount}}</b> {{ $t(".")}} <br>
                        <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b>{{ $t("both_referrer_user_and_referred_driver_get_rewards_based_on_earning_amount_condition")}} <b>$ {{form.referral_condition_driver_earning_amount}}</b> {{ $t(".")}}
                      </div>
                    </div>
                    <input
                      type="number"
                      class="form-control"
                      :readonly="app_for == 'demo'"
                      id="referral_condition_driver_earning_amount"
                      placeholder="Enter the Amount"
                      v-model="form.referral_condition_driver_earning_amount"
                    />
                  </div>
                </div>
              </div>

                              <!-- 4️⃣ Dual Conditional Extra Fields -->
                              <div
                                class="row mt-5"
                                v-if="form.referral_type === 'dual_conditional'"
                              >
                                <div class="col-lg-6">
                                  <h6>{{ $t("user_who_use_the_refer_code_when_user_refer") }}</h6>
                                  <p class="fs-12">{{ $t("offer_a_reward_to_new_users_and_drivers_under_conditional_settings")}}</p>
                                </div>
                                <div class="col-lg-6">
                                  <div class="border p-4 rounded bg-light mb-3">
                                    <label for="referral_commission_for_new_user_from_referer_user" class="form-label">
                                      {{ $t("earnings_to_new_signup_user") }}
                                    </label>
                                    <div class="tooltip-wrapper">
                                      <i class="ri-information-line"></i>
                                      <div class="tooltip-text">
                                        <b>{{ $t("instant_for_referrer_user_and_new_user") }}:</b> {{ $t("referred_user_get_reward_after_referred_user_sign_up")}}<br>
                                        <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b>{{ $t( "referred_user_rewarded_after_meeting_ride_or_spend_condition")}}
                                      </div>
                                    </div>
                                    <input
                                      type="text"
                                      class="form-control"
                                      placeholder="Enter the Amount"
                                      id="referral_commission_for_new_user_from_referer_user"
                                      v-model="form.referral_commission_for_new_user_from_referer_user"
                                    />
                                  </div>
                                  <div class="border p-4 rounded bg-light">
                                    <label for="referral_commission_for_new_driver_from_referer_user" class="form-label">
                                      {{ $t("earnings_to_new_signup_driver") }}
                                    </label>
                                    <div class="tooltip-wrapper">
                                      <i class="ri-information-line"></i>
                                      <div class="tooltip-text">
                                        <b>{{ $t("instant_for_referrer_user_and_new_user") }}:</b> {{ $t("referred_driver_get_reward_after_referrer_driver_sign_up")}}<br>
                                        <b>{{ $t("conditional_for_referrer_user_and_new_user") }}:</b> {{ $t("referred_driver_rewarded_after_meeting_ride_or_spend_condition")}}
                                      </div>
                                    </div>
                                    <input
                                      type="text"
                                      class="form-control"
                                      placeholder="Enter the Amount"
                                      id="referral_commission_for_new_driver_from_referer_user"
                                      v-model="form.referral_commission_for_new_driver_from_referer_user"
                                    />
                                  </div>
                                </div>
                              </div>
                              
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </section>


                <!-- Submit Button -->
                <div v-if="app_for!== 'demo'" class="mt-4">
                  <button type="submit" class="btn btn-primary">{{$t("update_referral_settings")}}</button>
                </div>
              </FormValidation>
            </form>
            <div v-if="successMessage" class="alert alert-success mt-3" role="alert">
              {{ successMessage }}
              <button type="button" class="btn-close" aria-label="Close" @click="dismissMessage"></button>
            </div>
            <div v-if="alertMessage" class="alert alert-danger mt-3" role="alert">
              {{ alertMessage }}
              <button type="button" class="btn-close" aria-label="Close" @click="dismissMessage"></button>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>




<style scoped>
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
.accordion-button:not(.collapsed)
{
  background-color: #f3f6f9 !important;
}

.custom-accordionwithicon-plus .accordion-button:not(.collapsed)::after{
  content: "" !important; 
}

.custom-accordionwithicon-plus .accordion-button::after{
  content: "" !important;
}
.rank{
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-weight: 800;
}

/* tootip */
.tooltip-wrapper {
  position: relative;
  display: inline-block;
  margin-left: 8px;
}

.tooltip-icon {
  color: #007bff;
  cursor: pointer;
  font-size: 16px;
}

.tooltip-text {
  visibility: hidden;
  opacity: 0;
  background-color: #f0f0f0;
  color: #222222;
  text-align: left;
  border-radius: 8px;
  padding: 10px;
  position: absolute;
  z-index: 100;
  bottom: 130%; /* position above icon */
  left: 50%;
  transform: translateX(-50%);
  transition: opacity 0.2s ease-in-out;
  font-size: 13px;
  line-height: 1.4;
  width: 380px;
  white-space: normal;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.tooltip-wrapper:hover .tooltip-text {
  visibility: visible;
  opacity: 1;
}
</style>
