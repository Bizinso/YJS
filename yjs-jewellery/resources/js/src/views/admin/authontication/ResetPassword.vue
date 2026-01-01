<script setup>

</script>
<template>
    <div class="admin_login">
        <img src="../../assets/images/yjs_logo.png" class="authontication_logo_img" alt="yjs_logo">
        <div class="admin_login_avtar_img">            
            <img src="../../assets/images/admin/admin_login_avtar.png" class="authontication_avtar_img" alt="admin_login_avtar">
        </div>
        <div class="admin_login_form">
            <h3>Reset Password</h3>
            <h5 class="mb-4">Set your new Password</h5>
            <b-form>
                <label for="">New Password</label>
                <b-input-group class="mb-2">
                    <template #prepend>
                        <span class="label_prepend border-right-0"><i class="fa-solid fa-key"></i></span>
                    </template>
                    <b-form-input type="password" placeholder="******************" class="form_input_prepend"></b-form-input>
                    <template #append>
                        <b-button variant="c" class="border_input_light_append"><i class="fa-regular fa-eye"></i></b-button>
                    </template>
                </b-input-group>
                <label for="">Confirm Password</label>
                <b-input-group class="mb-1">
                    <template #prepend>
                        <span class="label_prepend border-right-0"><i class="fa-solid fa-key"></i></span>
                    </template>
                    <b-form-input type="password" placeholder="******************" class="form_input_prepend"></b-form-input>
                    <template #append>
                        <b-button variant="c" class="border_input_light_append"><i class="fa-regular fa-eye"></i></b-button>
                    </template>
                </b-input-group>
                <b-button class="w-100 mt-2" variant="primary">Set New Password</b-button>
                <div class="text-center mt-3">
                    <b-link class="link_to_back" to="/admin/login"><i class="fa-solid fa-arrow-left me-1"></i> Back to login</b-link>
                </div>
            </b-form>
        </div>
    </div>
</template>