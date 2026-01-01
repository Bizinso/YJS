<script setup>
</script>
<template>
    <div class="yjs_about">
         <div class="bannerSection">
            <div class="bannerimage">
                <img src="../assets/images/static/about/bannerImage.png" alt="ring1" class="banner" />
            </div>
            <div class="bannerText">
                <h2 class="boxText">Crafting Heritage, Preserving Legacy.</h2>
            </div>
         </div>
         
         <div class="quoteText">
            <div class="container">
                <p class="basicDetails">Yashoda Jagdish & Sons is more than a name—it is a legacy forged through decades of dedication to antique gold jewellery craftsmanship.</p>
                <p class="basicDetails">What began as a small atelier rooted in tradition has evolved into a heritage brand trusted by families, collectors, and connoisseurs. Every creation reflects a deep respect for India’s timeless artistry, where purity, design, and emotion come together in harmony.</p>
            </div>
         </div>

         <div class="ourStory">
            <div class="container">
                <h2 class="globalHeading">Our Story</h2>
                <p class="basicDetails">For over 25 years, we have been creating jewellery that embodies the spirit of tradition and the precision of true craftsmanship. Each ornament tells a story—a story of lineage, devotion, and artistry passed from one generation to the next. Our collections are designed not to follow fashion, but to uphold legacy. In every piece, you’ll find the reflection of India’s rich cultural heritage, reimagined with a modern sensibility.</p>
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

        <div class="legacy">
            <div class="container legacyFlex">

                <div class="textBox">
                    <h2 class="globalHeading">The Legacy of Craftsmanship</h2>
                </div>
                <div class="cardBox">
                    <div class="innerGrid">
                        <div class="legacyPoints">
                            <img src="../assets/images/static/about/legacy1.png" alt="ring3" class="mainImage" />
                            <p class="desc">
                                Every design preserves the essence of a centuries-old art form.
                            </p>
                        </div>
                        <div class="legacyPoints">
                            <img src="../assets/images/static/about/legacy2.png" alt="ring3" class="mainImage" />
                            <p class="desc">
                                We specialise in authentic antique gold jewellery that marries traditional motifs with fine detailing and craftsmanship.
                            </p>
                        </div>
                        <div class="legacyPoints">
                            <img src="../assets/images/static/about/legacy3.png" alt="ring3" class="mainImage" />
                            <p class="desc">
                                Our artisans carry forward techniques honed over decades, ensuring that every creation is not just a jewel, but a living testament to heritage.
                            </p>
                        </div>
                        <div class="legacyPoints">
                            <img src="../assets/images/static/about/legacy4.png" alt="ring3" class="mainImage" />
                            <p class="desc">
                                Precision, patience, and purity define our craft—values that have remained unchanged through time.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="Expertise">
            <div class=" ourExpertise" :class="!isMobile ? 'container' :''">
                <div class="expertFeatures">
                    <div class="gridBox">
                        <div class="cardBox">
                            <div class="imageBox">
                                <img src="../assets/images/static/about/expertise1.png" alt="ring3" class="mainImage" />
                            </div>
                            <div class="desc">Antique Gold Jewellery</div>
                        </div>
                        <div class="cardBox">
                            <div class="imageBox">
                                <img src="../assets/images/static/about/expertise2.png" alt="ring3" class="mainImage" />
                            </div>
                            <div class="desc">Handcrafted techniques passed down through generations</div>
                        </div>
                        <div class="cardBox">
                            <div class="imageBox">
                                <img src="../assets/images/static/about/expertise3.png" alt="ring3" class="mainImage" />
                            </div>
                            <div class="desc">Custom designs rooted in tradition yet timeless in elegance</div>
                        </div>
                        <div class="cardBox">
                            <div class="imageBox">
                                <img src="../assets/images/static/about/expertise4.png" alt="ring3" class="mainImage" />
                            </div>
                            <div class="desc">Jewelry that carries emotional and cultural value</div>
                        </div>
                    </div>
                </div>
                <div class="expertTitle">
                    <h2 class="globalHeading">Our Expertise</h2>
                </div>
            </div>
        </div>


        <div class="ourPromise">
            <div class="expertTitle">
                <h2 class="globalHeading">Our Promise</h2>
                <p class="basicDetails">At Yashoda Jagdish & Sons, every creation is built on trust, authenticity, and heritage. We believe jewellery should transcend fashion—it should endure. Each piece is crafted to be more than an adornment; it is an heirloom that carries emotion, tradition, and the unmistakable mark of craftsmanship. We remain committed to offering jewellery that stands as a symbol of purity, elegance, and lasting legacy.</p>
            </div>
        </div>

        <div class="quoteBox">
            <div class="expertTitle">
                <p class="basicDetails">“From our hands to your legacy—we don’t just create jewellery, we preserve tradition for generations to come.”</p>
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
