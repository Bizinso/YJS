<template>
    <div class="p-3">
        <div class="viewFrame">
            <div class="headingLine">
                <h3>Product Details</h3>
                <div class="editBtnFrame">
                    <img src="../assets/img/editBtn.svg" class="editBtn" alt="Edit Button">
                </div>
            </div>
            <div class="cardBox">
                <div class="cardHeading">
                    <h2>Basic Information</h2>
                </div>
                <div class="detailsSection">
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Product Name Product Name Product Name Product Name:</p>
                        </span>
                        <span class="valueHead">
                            <p>Elegant Gold Ring Elegant Gold Ring Elegant Gold Ring Elegant Gold Ring Elegant Gold Ring Elegant Gold Ring </p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Product Name:</p>
                        </span>
                        <span class="valueHead">
                            <p>Elegant Gold Ring</p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Product Name:</p>
                        </span>
                        <span class="valueHead">
                            <p><b-badge class="masterBadge">Active</b-badge></p>
                        </span>
                    </div>
                </div>
            </div>

            
            <div class="cardBox">
                <div class="cardHeading">
                    <h2>Image</h2>
                </div>
                <div class="detailsSection">
                        <div class="imageSection">
                            <img src="../assets/img/demo.png" class="imagesDisplay" alt="">
                        </div>
                        <div class="imageSection">
                            <img src="../assets/img/demo.png" class="imagesDisplay" alt="">
                        </div>
                        <div class="imageSection">
                            <img src="../assets/img/demo.png" class="imagesDisplay" alt="">
                        </div>
                        <div class="imageSection">
                            <img src="../assets/img/demo.png" class="imagesDisplay" alt="">
                        </div>
                        <div class="imageSection">
                            <img src="../assets/img/demo.png" class="imagesDisplay" alt="">
                        </div>
                </div>
            </div>

            <div class="cardBox">
                <div class="cardHeading">
                    <h2>Material & Attributes</h2>
                </div>
                <div class="detailsSection">
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Material Type:</p>
                        </span>
                        <span class="valueHead">
                            <p>Gold </p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>SKU:</p>
                        </span>
                        <span class="valueHead">
                            <p>SKU-0001 </p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Purity / Karat:</p>
                        </span>
                        <span class="valueHead">
                            <p>22K </p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Gemstone Type:</p>
                        </span>
                        <span class="valueHead">
                            <p>Ruby </p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Gemstone Weight:</p>
                        </span>
                        <span class="valueHead">
                            <p>1.5 Carats </p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Metal Weight:</p>
                        </span>
                        <span class="valueHead">
                            <p>5.8 grams </p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Finish Type:</p>
                        </span>
                        <span class="valueHead">
                            <p>Glossy </p>
                        </span>
                    </div>
                    <div class="innercolumn">
                        <span class="boldHead">
                            <p>Occasion:</p>
                        </span>
                        <span class="valueHead">
                            <p>Wedding </p>
                        </span>
                    </div>
                </div>
            </div>

            <div class="cardBox">
                <div class="cardHeading">
                    <h2>Item Details</h2>
                </div>
                <div class="detailsSection itemDetails">
                        <div class="imageSection">
                            <img src="../assets/img/demo.png" class="imagesDisplay" alt="">
                        </div>
                        <div class="itemDetails">
                            <span class="boldHead">
                                <p>Gold Pendant</p>
                            </span>
                            <span class="valueHead">
                                <p>18K, 5gm, Round Cut </p>
                            </span>
                            <span class="quantityHead">
                                <p>Quantity: <span class="quantity">2</span> </p>
                            </span>
                        </div>
                        <div class="priceDetails">
                            <span class="valueHead">
                                <p>Price: </p>
                            </span>
                            <span class="boldHead">
                                <p>₹48,000</p>
                            </span>
                        </div>
                </div><div class="detailsSection itemDetails">
                        <div class="imageSection">
                            <img src="../assets/img/demo.png" class="imagesDisplay" alt="">
                        </div>
                        <div class="itemDetails">
                            <span class="boldHead">
                                <p>Gold Pendant</p>
                            </span>
                            <span class="valueHead">
                                <p>18K, 5gm, Round Cut </p>
                            </span>
                            <span class="quantityHead">
                                <p>Quantity: <span class="quantity">2</span> </p>
                            </span>
                        </div>
                        <div class="priceDetails">
                            <span class="valueHead">
                                <p>Price: </p>
                            </span>
                            <span class="boldHead">
                                <p>₹48,000</p>
                            </span>
                        </div>
                </div>
            </div>



        </div>
    </div>
</template>
<script setup>
import { ref, onMounted} from "vue";


const deleteModal = ref(false);
const openDeleteModal = (id) => {
  deleteModal.value = true;
};

</script>
