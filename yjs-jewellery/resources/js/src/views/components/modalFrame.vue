<template>
    <div>
        <div class="modalFrame">
            <div class="buttonGrid">
                <b-button class="fillBTN" @click="openDeleteModal">Open Modal</b-button>
            </div>
        </div>
        <b-modal
            id="delete-modal"
            v-model="deleteModal"
            hide-header
            hide-footer
            centered
            size="lg"
            class="customModal"
            >   
                <img src="../assets/img/close.svg" class="closeBtn" @click="deleteModal = false" alt="Modal Close Button">
                <div class="contentFrame">
                    <h6 class="modalHeading">Are you sure you want to delete this Product Type?</h6>
                    <h6 class="modalContent">Are you sure you want to delete this Product Type?</h6>
                </div>
                <div class="footerFrame buttonGrid">
                    <b-button class="fillBTN" @click="deleteModal = false"
                        >Cancel</b-button
                    >
                    <b-button class="transBTN" @click="confirmDelete">Delete</b-button>
                </div>

        </b-modal>
    </div>
</template>
<script setup>
import { ref, onMounted} from "vue";


const deleteModal = ref(false);
const openDeleteModal = (id) => {
  deleteModal.value = true;
};

</script>
