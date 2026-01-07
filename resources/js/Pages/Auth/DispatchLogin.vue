<script setup>
import { Head, Link, useForm ,router} from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import axios from 'axios';
import {onMounted, ref }  from 'vue';
import {usePage} from '@inertiajs/vue3'

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
 
const togglePassword = ref(false);
const page = usePage()
const appFor = page.props.app_for
const submit = async () => {
    form.clearErrors();
    try {
        const response = await axios.post('/dispatch-login', form.data());
        if (response.status == 200) {
            router.get('/dispatcher/bookride');
        }
    } catch (error) {
        if (error.response.status === 422) {
            // Set validation errors in the form
            form.setError('email', error.response.data.errors.email ? error.response.data.errors.email[0] : '');
            form.setError('password', error.response.data.errors.password ? error.response.data.errors.password[0] : '');
        } else {
            console.error('Unexpected error:', error);
        }
    }
};

onMounted(()=>{
    const displayEmail = document.getElementById('display-email')?.textContent.trim();
    const displayPassword = document.getElementById('display-password')?.textContent.trim();
    const fillBtn = document.getElementById('fillBtn');
    const emailInput = document.getElementById('email-input');
    const passwordInput = document.getElementById('password-input');

    fillBtn?.addEventListener('click', () =>{
        if(emailInput && passwordInput){
            emailInput.value = displayEmail;
            passwordInput.value = displayPassword;
            form.email = displayEmail;
            form.password = displayPassword;
            passwordInput.focus();
        }
    });
});
</script>

<template>

    <Head title="Log in" />

    <body id="bodyContainer">
        <div class="bg-overlay"></div>
        <div class="container-fluid ">
            <BRow>
                <BCol lg="5">
                    <div class="form_field text-white">
                        <Bcard>
                            <BCardBody class="BcardBody" >
                                <h1 class="display-6" style="color: white;"><b>Dispatcher Signin</b></h1>
                                <p class="">Welcome To Dispatch</p>
                                <div class="mt-5">
                                    <form @submit.prevent="submit">

                                         <!-- Email Input -->
                                    <div class="mb-2">
                                        <InputLabel for="email" value="Email" />
                                        <TextInput id="email-input" v-model="form.email" type="email"
                                            class="form-control p-3" autofocus placeholder="Please enter email"
                                            autocomplete="email"
                                            required :class="{ 'is-invalid': form.errors.email }" />
                                        <InputError :message="form.errors.email" />
                                    </div>

                                    <!-- Password Input -->
                                    <div class="mb-2">
                                        <InputLabel for="password" value="Password" />
                                        <div class="position-relative auth-pass-inputgroup mb-3">
                                            <input :type="togglePassword ? 'text' : 'password'" class="form-control pe-5 p-3"
                                                placeholder="Enter password" id="password-input" v-model="form.password"
                                                autocomplete="password" required :class="{ 'is-invalid': form.errors.password }"
                                            />
                                            <BButton
                                                variant="link"
                                                class="position-absolute end-0 mt-2 top-0 text-decoration-none text-muted"
                                                type="button"
                                                id="password-addon"
                                                @click="togglePassword = !togglePassword"
                                            >
                                                <i class="ri-eye-fill align-middle ri-lg"></i>
                                            </BButton>
                                            <InputError :message="form.errors.password" />
                                        </div>
                                    </div>
                                        <div class="float-end " >
                                            <Link v-if="canResetPassword" :href="route('password.request')"
                                                class="text-white">Forgot password?
                                        </Link>
                                        </div>
                                     <!--email and password auto fill-->
                                     <div class="credential-panel credential-row" v-if = "appFor === 'demo'">
                                        <div class="col">
                                            <div class="credential-item">
                                                <span class="label">Email:</span>
                                                <span class="value" id="display-email">dispatch@admin.com</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                             <div class="credential-item">
                                                <span class="label1">Password:</span>
                                                <span class="value" id="display-password">123456789</span>
                                             </div>
                                        </div>
                                        <!-- Copy icon button -->
                                        <button class="copy-btn" id="fillBtn" title="Fill form" type="button">
                                            <i class="ri-file-copy-line icon text-muted"></i>
                                        </button>
                                     </div>


                                        <div class="form-check form-check-success mt-3">
                                            <Checkbox v-model:checked="form.remember" name="remember" class="form-check-input"
                                                id="auth-remember-check" />
                                            <label class="form-check-label" for="auth-remember-check">Remember
                                                me</label>
                                        </div>

                                        <div class="mt-2">
                                            <BButton variant="success" class="w-100 mt-3" type="submit"
                                                :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                                                style="height: 45px; border-radius: 10px;">Login</BButton>
                                        </div>

                                    </form>
                                </div>
                            
                            </BCardBody>
                        </Bcard>
                    </div>
                </BCol>

            </BRow>
        </div>
    </body>
</template>

<style>
.form_field {
    margin: 100px 20px 0px 80px;
}

#bodyContainer {
    /* background: url('/assets/images/workspace.jpg') no-repeat; */
    background: var(--loginbg) no-repeat;
    background-repeat: no-repeat;
    background-size: cover;
    height: 100vh;
}
#email-input, #password-input{
    background: none;
    color: white;
}
.credential-panel{
    position: relative;
    border-radius: 10px;
    padding: 15px 56px 15px 18px; 
    border: solid 1px #ced4da;
    box-shadow: 0 1px 0 rgba(0,0,0,0.03);
    width: 445px;
}
.credential-row {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}
.credential-item {
    display: flex;
    align-items: center;
}
.label{
    font-weight: 500;
    width: 40px;
    font-size: 13px;
}
.label1{
    font-weight: 500;
    width: 70px;
    font-size: 13px;
}
.value{
    font-size: 13px;
    color: #faf8f8;
    user-select: all; 
    margin-left: 7px;
}
.copy-btn{
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    border-radius: 6px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: background .12s ease, transform .08s ease;
}
.icon{
    font-size: 20px;
}
.copy-btn:hover{ background: rgba(235, 233, 233, 0.04); }
.copy-btn:active{ transform: translateY(-50%) scale(.98); }
.form{
    margin-top: 18px;
    display:flex;
    gap: 12px;
    align-items: center;
}
.form input{
    padding: 8px 10px;
    border-radius: 6px;
    border: 1px solid #d8d8d8;
    font-size: 14px;
    width: 260px;
}
.icon {
    width: 18px;
    height: 18px;
    display:block;
    fill: #6b6b6b;
}
@media only screen and (max-width: 988px) {
    #bodyContainer{
        background: black;
    }

}
@media only screen and (max-width: 320px){
.copy-btn {
    position: absolute;
    right: 7px;
    top: 75%;
}
.label {
    width: 36px;
    font-size: 12px;
}
.label1 {
    width: 63px;
    font-size: 12px;
}
.value {
    font-size: 12px;
    user-select: all;
}
.icon{
    font-size: 15px;
}
.credential-panel {
    position: relative;
    border-radius: 10px;
    padding: 18px 56px 18px 18px;
    border: 1px solid #ffff;
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.03);
    width: 198px;
}
#bodyContainer {
    height: 120vh;
}
}
@media only screen and (max-width: 375px) and (min-width: 321px){
.credential-panel {
    width: 250px;
}
#bodyContainer {
    height: 117vh;
}
}
@media only screen and (max-width: 425px) and (min-width: 376px){
.credential-panel {
    width: 300px;
}
#bodyContainer {
    height: 117vh;
}
}
@media only screen and (max-width: 768px) and (min-width: 426px){
.credential-panel {
    width: 100% !important;
    padding-right: 48px; 
}
.credential-row {
    flex-direction: column;
    align-items: flex-start;
}
.col {
    width: 100%;
}
.copy-btn {
    top: auto;
    bottom: 12px;
    right: 12px;
    transform: none;
}
}
@media only screen and (max-width: 1024px) and (min-width: 769px){
.credential-panel {
    width: 307px;
}
}
</style>
