<template>
    <div class="yjs_about">
        <div class="bannerSection">
            <div class="bannerimage">
                <img src="../assets/images/static/about/bannerImage.png" alt="ring1" class="banner" />
            </div>
            <div class="bannerText">
                <h2 class="boxText">Get in Touch With Us</h2>
            </div>
        </div>


        <div class="py-5 ourStory">
            <div class="container pt-5">
                <h2 class="globalHeading">Contact Information</h2>
                <div class="contactDetails">

                    <p class="basicDetails mb-4">
                        <strong>Address:</strong><br />
                        14, Floor 3, Plot 19, Shreeji Niwas, Pophalwadi 3 lane,<br /> Bhuleshwar, Kalbadevi, MUMBAI, Mumbai
                        City, Maharashtra - 400002.
                    </p>

                    <p class="basicDetails mb-4">
                        <strong>Email:</strong><br />
                         sales@yjs.com<br />
                         Support@yjs.com
                    </p>

                    <p class="basicDetails mb-4">
                        <strong>Phone:</strong><br />
                        +91 98765 43210
                    </p>
                </div>
            </div>
        </div>


        <div class="photoGallery">
            <div class="productImageGrid">
                <div class="horizontalGrid wf-20">
                    <img src="../assets/images/static/about/about1.png" alt="ring3" class="mainImage" />
                </div>
                <div class="verticalGrid wf-20">
                    <img src="../assets/images/static/about/about2.png" alt="ring1" class="mainImage" />
                    <img src="../assets/images/static/about/about3.png" alt="ring2" class="mainImage" />
                </div>
                <div class="horizontalGrid wf-15">
                    <img src="../assets/images/static/about/about4.png" alt="ring3" class="mainImage" />
                </div>
                <div class="horizontalGrid wf-40">
                    <img src="../assets/images/static/about/about5.png" alt="ring3" class="mainImage" />
                </div>
            </div>
        </div>






    </div>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";

const isMobile = ref(false);

function checkScreenSize() {
    // Bootstrap breakpoint: <768px is "mobile"
    isMobile.value = window.innerWidth < 768;
}

onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", checkScreenSize);
});
</script>
