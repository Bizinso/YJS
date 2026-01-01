<template>
  <div class="p-3 pt-0 customFormWizardScroll">
    <div v-if="loading" class="text-center p-5">
      <p>Loading product details...</p>
    </div>
    <div class="viewFrame" v-else>
      <div class="headingLine">
          <div class="buttonBox">
            <router-link :to="{ name: 'admin.products' }" class="GlobaltransBTN">
              ← Product List
            </router-link>
          <div class="editBtnFrame" v-if="product && product.id">
            <router-link :to="{
              name: 'admin.products.edit',
              params: { id: encodeBase64(product.id) }
            }">
              <img src="../../assets/img/editBtn.svg" class="editBtn" alt="Edit Button" />
            </router-link>
          </div>

        </div>
      </div>

      <!-- Basic Information -->
      <div class="cardBox" v-if="isReady">
        <div class="cardHeading">
          <h2>Basic Information</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn">
            <span class="boldHead">
              <p>Product Name:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.name }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>SKU:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.sku }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Status:</p>
            </span>
            <span class="valueHead">
              <p>
                <b-badge class="masterBadge" :variant="product.status === 'active' ? 'success' : 'danger'">
                  {{
                    product.status === "active"
                      ? "Active"
                      : product.status === "inactive"
                        ? "Inactive"
                        : "Draft"
                  }}
                </b-badge>
              </p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Description:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.description == 'null' ? "-" : product.description }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Product Type:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.product_type_name }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Category:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.category_name }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Sub Category:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.sub_category_name }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Tag Name:</p>
            </span>
            <span class="valueHead">
              <p>{{ formatTags(product.tags_id) }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Visible To:</p>
            </span>
            <span class="valueHead">
              <p>
                {{ product.visible_to ? product.visible_to.charAt(0).toUpperCase() + product.visible_to.slice(1) : "-"
                }}
              </p>
            </span>
          </div>

          <div class="innercolumn" v-if="product.partner_options && product.partner_options.length">
            <span class="boldHead">
              <p>Visible Partners:</p>
            </span>
            <span class="valueHead">
              <p>
                <span v-for="(partner, index) in product.partner_options" :key="partner.id">
                  {{ partner.name }}<span v-if="index < product.partner_options.length - 1">, </span>
                </span>
              </p>
            </span>
          </div>

        </div>
      </div>
      <!-- Images -->
      <div class="cardBox" v-if="product.main_image">
        <div class="cardHeading">
          <h2>Images</h2>
        </div>
        <div class="detailsSection">
          <div class="imageSection">
            <img :src="`/storage/${product.main_image}`" class="imagesDisplay" alt="Main Product Image" />
          </div>
          <div class="imageSection" v-for="(image, index) in product.other_images" :key="index">
            <img :src="`/storage/${image}`" class="imagesDisplay" :alt="`Product Image ${index + 1}`" />
          </div>
        </div>
      </div>

      <!-- Material & Attributes -->
      <div class="cardBox">
        <div class="cardHeading">
          <h2>Material & Attributes</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn">
            <span class="boldHead">
              <p>Material Type/Metal Type:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.metal_name || "-" }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Purity / Karat:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.purity_name || "-" }}</p>
            </span>
          </div>

          <div class="innercolumn" v-if="product.product_type_name != 'Configurable'">
            <span class="boldHead">
              <p>Metal Weight:</p>
            </span>
            <span class="valueHead">
              <p>
                {{
                  product.metal_weight ? `${product.metal_weight} grams` : "-"
                }}
              </p>
            </span>
          </div>

        </div>
      </div>

      <!-- Add Variants -->
      <div class="cardBox" v-if="product.product_type_name === 'Configurable'">
        <div class="cardHeading">
          <h2>Add Variants</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn">
            <span class="boldHead">
              <p>Add Variants:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.variants ? "Yes" : "No" }}</p>
            </span>
          </div>

          <div class="innercolumn" v-for="(variant, index) in product.variant_attributes" :key="index">
            <span class="boldHead">
              <p>Variant Attribute(s):</p>
            </span>
            <span class="boldHead">
              <p>{{ variant.name ?? '-' }}</p>
            </span>
          </div>
        </div>
      </div>

      <!-- Pricing -->
      <div class="cardBox" v-if="product.product_type_name !== 'Configurable'">
        <div class="cardHeading">
          <h2>PRICING AND CHARGES</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn" v-if="product.product_type_name === 'Simple'">
            <span class="boldHead">
              <p>Base Price:</p>
            </span>
            <span class="valueHead">
              <p>₹{{ product.base_price ?? '-' }}</p>
            </span>
          </div>
          <div class="innercolumn" v-if="product.product_type_name === 'Ready-made'">
            <span class="boldHead">
              <p>Unit Price:</p>
            </span>
            <span class="valueHead">
              <p>₹{{ product.unit_price ?? '-' }}</p>
            </span>
          </div>

          <div class="innercolumn">
            <span class="boldHead">
              <p>Additional Charges:</p>
            </span>

            <div v-for="(charge, index) in product.charges" :key="index">
              <span class="boldHead">
                <p>{{ charge.charges ?? '-' }}</p>
              </span>
              <span class="valueHead">
                <p>Type: {{ charge.type ?? '-' }}</p>
                <p>Value: {{ charge.value ?? '-' }}</p>
                <p>Primary Cost: {{ charge.primary_cost ?? '-' }}</p>
                <p>Charges Cost: {{ charge.calculated_amount ?? '-' }}</p>
                <p>Description: {{ charge.description ?? '-' }}</p>
              </span>
            </div>
          </div>

          <div class="innercolumn">
            <span class="boldHead">
              <p>Additional Taxes:</p>
            </span>

            <div v-for="(tax, index) in product.taxes" :key="index">
              <span class="boldHead">
                <p>{{ tax.tax_application ?? '-' }}</p>
              </span>
              <span class="valueHead">
                <p>Type: {{ tax.type ?? '-' }}</p>
                <p>Value: {{ tax.value ?? '-' }}</p>
                <p>Primary Cost: {{ tax.primary_cost ?? '-' }}</p>
                <p>Tax Charges Cost: {{ Math.floor(tax.amount) ?? '-' }}</p>
                <p>Description: {{ tax.description ?? '-' }}</p>


              </span>
            </div>
          </div>
          <div class="innercolumn" v-if="product.product_type_name !== 'Configurable'">
            <span class="boldHead">
              <p>Product Price:</p>
            </span>
            <span class="valueHead">
              <p class="finalPrice">₹{{ product.final_price ?? '-' }}</p>
            </span>
          </div>
        </div>
      </div>



      <div class="cardBox" v-if="product.product_type_name === 'Configurable'">
        <div class="cardHeading">
          <h2>PRICING AND CHARGES</h2>
        </div>

        <div class="detailsSection">
          <div class="boxFit" v-for="(child, i) in product.childrenproducts" :key="i">
            <!-- v-for="(child, i) in product.childrenproducts" :key="i" -->
            <div class="setinnername ">
              <h2 class="sattardo"> {{ child.name }} </h2>
            </div>
            <div class="detailsSection">
              <div class="innercolumn">
                <span class="boldHead">
                  <p>Base Price</p>
                </span>
                <span class="valueHead">
                  <p>{{ Math.floor(child.base_price) }} </p>
                </span>
              </div>

              <div class="innercolumn">
                <span class="boldHead">
                  <p>Metal Weight</p>
                </span>
                <span class="valueHead">
                  <p> {{ child.metal_weight ?? '-' }} </p>
                </span>
              </div>


            </div>
            <div v-if="child.charges?.length">

              <div class="setinnername">
                <h2>Additional Charges</h2>
              </div>
              <div class="detailsSection" v-for="(charge, j) in child.charges" :key="j">
                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Charges</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ charge.charges ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Type</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ charge.type ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Value</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ charge.value ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Cost</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ charge.primary_cost ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Description</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ charge.description ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Amount:</p>
                  </span>
                  <span class="valueHead">
                    <p> ₹ {{ Math.floor(child.base_price * charge.value / 100) ?? '-' }} </p>
                  </span>
                </div>

              </div>
            </div>

            <div v-if="child.tax_charges?.length">
              <div class="setinnername">
                <h2>Taxes</h2>
              </div>
              <div class="detailsSection" v-for="(tax, t) in child.tax_charges" :key="t">
                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Tax Application:</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ tax.tax_application ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Type:</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ tax.type ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Value:</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ tax.value ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Cost:</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ tax.primary_cost ?? '-' }} </p>
                  </span>
                </div>
                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Description</p>
                  </span>
                  <span class="valueHead">
                    <p> {{ tax.description ?? '-' }} </p>
                  </span>
                </div>

                <div class="innercolumn">
                  <span class="boldHead">
                    <p>Amount:</p>
                  </span>
                  <span class="valueHead">
                    <p> ₹ {{ tax.amount ?? '-' }} </p>
                  </span>
                </div>


              </div>
            </div>
          </div>
        </div>
      </div>



      <!-- Variant Inventory -->
      <div class="cardBox" v-if="product.product_type_name === 'Configurable'">
        <div class="cardHeading">
          <h2>Variant Inventory</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn" v-for="(variant, index) in product.childrenproducts" :key="index">
            <span class="boldHead">
              <p>Name: {{ variant.name ?? '-' }}</p>
            </span>
            <span class="valueHead">
              <p> SKU: {{ variant.sku ?? '-' }}</p>

              <p>Total Stock: {{ variant.total_stock ?? '-' }}</p>
              <p>Low Stock: {{ variant.low_stock ?? '-' }}</p>

            </span>
          </div>
        </div>
      </div>

      <!-- Variant Shipping -->
      <div class="cardBox" v-if="product.product_type_name === 'Configurable'">
        <div class="cardHeading">
          <h2>Variant Shipping</h2>
        </div>

        <div class="detailsSection" v-if="Array.isArray(product.childrenproducts) && product.childrenproducts.length">
          <div class="innercolumn" v-for="(variant, index) in product.childrenproducts" :key="index">
            <span class="boldHead">
              <p>{{ variant.name ?? '-' }}</p>
            </span>

            <span class="valueHead">
              <p>SKU: {{ variant.sku ?? '-' }}</p>

              <!-- Only show Package weight line when it's meaningful -->
              <p v-if="hasValue(variant.weight)">Package weight: {{ formatValue(variant.weight) }}</p>

              <!-- Only show Unit when meaningful -->
              <p v-if="hasValue(variant.unit)">Unit: {{ formatValue(variant.unit) }}</p>

              <p v-if="hasValue(variant.length)">Length: {{ formatValue(variant.length) }}</p>
              <p v-if="hasValue(variant.width)">Width: {{ formatValue(variant.width) }}</p>
              <p v-if="hasValue(variant.height)">Height: {{ formatValue(variant.height) }}</p>

            </span>
          </div>

        </div>
      </div>


      <!--Simple and Ready-made Inventory & Shipping -->
      <div class="cardBox" v-if="product.product_type_name !== 'Configurable'">
        <div class="cardHeading">
          <h2>Inventory</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn">
            <span class="boldHead">
              <p>Total Stock:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.total_stock || "-" }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Low Stock Alert:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.low_stock || "-" }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Track Inventory:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.track_inventory ? "Yes" : "No" }}</p>
            </span>
          </div>
        </div>
      </div>
      <div class="cardBox" v-if="product.product_type_name !== 'Configurable'">
        <div class="cardHeading">
          <h2>Shipping</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn">
            <span class="boldHead">
              <p>Package Weight:</p>
            </span>
            <span class="valueHead">
              <p>{{ product?.weight && product.weight !== 'null' && product.weight !== ''
                ? `${product.weight} grams`
                : '-' }}
              </p>
            </span>

          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Dimensions:</p>
            </span>
            <span class="valueHead">
              <p>
                <template v-if="product.length && product.width && product.height">
                  {{ product.length }} × {{ product.width }} ×
                  {{ product.height }} {{ product.unit }}
                </template>
                <template v-else>-</template>
              </p>
            </span>
          </div>

        </div>
      </div>

      <!-- Variant Inventory -->
      <div class="cardBox" v-if="product.product_type_name === 'Configurable'">
        <div class="cardHeading">
          <h2>VARIANT SEO METADATA</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn">
            <span class="boldHead">
              <p>Seo Title: {{ product.seo_title ?? '-' }}</p>
              <p>Meta Description: {{ product.meta_description ?? '-' }}</p>
              <p>Slug URL: {{ product.meta_slug_url ?? '-' }}</p>
            </span>
          </div>

          <div class="innercolumn" v-for="(variant, index) in product.childrenproducts" :key="index">
            <span class="boldHead">
              <p>{{ variant.name ?? '-' }}</p>
            </span>
            <span class="valueHead">
              <p>SKU: {{ variant.sku ?? '-' }}</p>

              <p>Seo Title: {{ variant.seo_title ?? '-' }}</p>
              <p>Meta Description: {{ variant.meta_description ?? '-' }}</p>
              <p>Meta Slug: {{ variant.meta_slug_url ?? '-' }}</p>




            </span>
          </div>
        </div>
      </div>

      <!-- SEO & Metadata -->
      <div class="cardBox" v-if="product.product_type_name !== 'Configurable'">
        <div class="cardHeading">
          <h2>SEO & Metadata</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn">
            <span class="boldHead">
              <p>SEO Title:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.seo_title || "-" }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Meta Description:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.meta_description || "-" }}</p>
            </span>
          </div>
          <div class="innercolumn">
            <span class="boldHead">
              <p>Slug/URL:</p>
            </span>
            <span class="valueHead">
              <p>{{ product.meta_slug_url || "-" }}</p>
            </span>
          </div>
        </div>
      </div>

      <!-- Related Products -->
      <div class="cardBox">
        <div class="cardHeading">
          <h2>Related Products</h2>
        </div>
        <div class="detailsSection">
          <div class="relatedProduct smallerbox" v-for="(related, index) in product.related_products" :key="index">
            <div class="itemDetails">
              <span class="boldHead">
                <p>{{ related.name ?? '-' }}</p>
              </span>
              <span class="valueHead">
                <p>{{ related.sku ?? '-' }} </p>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- You May Also Like -->
      <div class="cardBox">
        <div class="cardHeading">
          <h2>You May Also Like</h2>
        </div>
        <div class="detailsSection">
          <div class="relatedProduct smallerbox" v-for="(item, index) in product.you_may_like_products" :key="index">
            <div class="itemDetails">
              <span class="boldHead">
                <p>{{ item.name ?? '-' }}</p>
              </span>
              <span class="valueHead">
                <p>{{ item.sku ?? '-' }} </p>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import axiosEmployee from '@axiosEmployee';

const route = useRoute();
const product = ref({});


const encodeBase64 = (data) => {
  if (data === undefined || data === null) {
    return "";
  }
  return btoa(data.toString());
};

const loading = ref(true);

const fetchProduct = async () => {
  loading.value = true;
  try {
    const response = await axiosEmployee.get(`/products/${route.params.id}`);
    product.value = response.data.data ?? null;
  } catch (err) {
    console.error("Failed to fetch product", err);
    product.value = null;
  } finally {
    loading.value = false;
  }
};

const hasValue = (val) => {
  if (val === null || val === undefined || val === '') return false;
  // If it's numeric-like, treat numeric zero as "no value"
  const n = Number(val);
  if (!Number.isNaN(n) && n === 0) return false;
  // otherwise show it
  return true;
};

/**
 * Format a displayed value. If empty/zero => '-', else return original.
 */
const formatValue = (val) => {
  if (!hasValue(val)) return '-';
  return val;
};


onMounted(async () => {
  await fetchProduct();

  const container = document.querySelector(".right_layout_screen"); 
  if (container) {
    console.log("container",container)
    container.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  } else {
    console.log("window",window)
    window.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  }
});

const isReady = computed(() => !!product.value?.id);
const formatTags = (tags) => {
  if (!tags) return '-'

  // Handle both string (JSON) and array formats
  if (typeof tags === 'string') {
    try {
      const parsedTags = JSON.parse(tags)
      return Array.isArray(parsedTags) ? parsedTags.join(', ') : '-'
    } catch (e) {
      return tags // Return as is if not JSON
    }
  }

  return Array.isArray(tags) ? tags.join(', ') : '-'
}
</script>
<style scoped></style>
