<template>
    <div class="listing_screen global_table_liting">
        <div class="listing_tab_and_actions mb-3 w-100">
            <div class="p-3 w-100">
                <div class="d-flex justify-content-start w-100">
                    <b-button @click="handleBackClick" class="GlobaltransBTN mb-3">
                        ← Back to Metal Type Listing
                    </b-button>
                </div>
                <div class="viewFrame" v-if="metalTypeData">
                    <div class="cardBox">
                        <div class="cardHeading">
                            <h2>Metal Type Details</h2>
                        </div>
                        <div class="detailsSection">
                            <div class="innercolumn">
                                <span class="boldHead">
                                    <p>Name:</p>
                                </span>
                                <span class="valueHead">
                                    <p>{{ metalTypeData.metal_name }}</p>
                                </span>
                            </div>
                            <div class="innercolumn">
                                <span class="boldHead">
                                    <p>Purity:</p>
                                </span>
                                <span class="valueHead">
                                    <p>{{ metalTypeData.purity_name }}</p>
                                </span>
                            </div>

                            <div class="innercolumn">
                                <span class="boldHead">
                                    <p>Price/gm:</p>
                                </span>
                                <span class="valueHead">
                                    <p>{{ metalTypeData.price_per_gram }}</p>
                                </span>
                            </div>

                            <div class="innercolumn">
                                <span class="boldHead">
                                    <p>Last Updated Date:</p>
                                </span>
                                <span class="valueHead">
                                    <p>{{ formatDateTime(metalTypeData.updated_at) }}</p>
                                </span>
                            </div>



                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import axiosEmployee from '@axiosEmployee';

const route = useRoute();
const router = useRouter();
const metalTypeData = ref(null);

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'N/A';
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Months 0-11 hote hain
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const fetchProductType = async () => {
    try {
        const { id } = route.params;
        const response = await axiosEmployee.get(`metal-type/${id}`);
        metalTypeData.value = response.data;
    } catch (error) {
        //
    }
};

const handleBackClick = () => {
  router.push(
    `/admin/settings?tab=ProductManagement&master=Material/Metal+Type`
  );
};


onMounted(fetchProductType);
</script>
