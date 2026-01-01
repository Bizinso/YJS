<script setup>

</script>
<template>
    <div class="admin_login">
        <img src="../../assets/images/yjs_logo.png" class="authontication_logo_img" alt="yjs_logo">
        <div class="admin_login_avtar_img nisforget">            
            <img src="../../assets/images/admin/forgetPassword.svg" class="authontication_avtar_img" alt="admin_forgot_password_avtar">
        </div>
        <div class="admin_login_form">
            <h3>Forgot Password?</h3>
            <h5 class="mb-4">Send email to Reset Password</h5>
            <b-form>
                <label for="">Email Id</label>
                <b-input-group class="mb-2">
                    <template #prepend>
                        <span class="label_prepend border-right-0"><i class="fa-regular fa-user"></i></span>
                    </template>
                    <b-form-input placeholder="admin@mail.com" class="form_input_prepend p-2"></b-form-input>
                </b-input-group>
                <b-button class="w-100 mt-2" variant="primary">Send Reset Mail</b-button>
                <div class="text-center mt-3">
                    <b-link class="link_to_back" to="/admin/login"><i class="fa-solid fa-arrow-left me-1"></i> Back to login</b-link>
                </div>
            </b-form>
        </div>
    </div>
</template>