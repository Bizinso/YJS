<template>
  <div class="listing_screen global_table_liting">
    <div class="buttonBox">
      <router-link :to="{ name: 'admin.products' }" class="GlobaltransBTN">
        ← Product List
      </router-link>
    </div>
    <div class="masterTabs">
      <div class="masterTabContent">
        <div class="formMaster form-70 set1000">
          <div class="customFormWizard">
            <FormWizard @on-complete="submitForm" shape="tab">
              <!-- First Tab -->
              <TabContent title="Basic Details" :before-change="() => onTabChange('Basic Details')">

                <h5 class="sectionTitle">Product Information</h5>
                <b-form-group>
                  <label class="required">Product Name</label>
                  <b-form-input id="input-2" v-model="form.product_name" placeholder="Enter Product Name" required>
                  </b-form-input>
                  <small class="text-danger">{{ errors[0] }}</small>
                  <div class="text-danger" v-if="hasErrors('product_name')">
                    {{ getErrors("product_name") }}
                  </div>
                </b-form-group>

                <b-form-group id="input-group-2" label="SKU" label-for="input-2">
                  <b-form-input id="input-2" v-model="form.sku" autocomplete="off" readonly></b-form-input>

                  <div class="text-danger" v-if="hasErrors('sku')">
                    {{ getErrors("sku") }}
                  </div>
                </b-form-group>



                <b-form-group label="Product Description">
                  <b-form-textarea v-model="form.description" placeholder="Enter Description..." rows="3"
                    max-rows="6" />
                </b-form-group>

                <b-form-group id="input-group-3" label-for="input-3" class="multiDrop">
                  <label class="required">Product Type</label>
                  <v-select v-model="form.product_type_id" :options="ProductTypeOptions" :reduce="(val) => val.value"
                    label="label" :clearable="true" placeholder="Select Product Type"
                    @update:modelValue="onProductTypeChange" />
                  <small class="text-danger">{{ errors[0] }}</small>
                  <div class="text-danger" v-if="hasErrors('product_type_id')">
                    {{ getErrors("product_type_id") }}
                  </div>
                </b-form-group>

                <b-form-group id="input-group-3" label-for="input-3" class="multiDrop">
                  <label class="required">Category</label>
                  <v-select v-model="form.category_id" @update:modelValue="onCategoryChange" :options="CategoryOptions"
                    :reduce="(val) => val.value" label="label" :clearable="true" placeholder="Select Category"
                    @input="RemoveError('category_id')" />
                  <small class="text-danger">{{ errors[0] }}</small>
                  <div class="text-danger" v-if="hasErrors('category_id')">
                    {{ getErrors("category_id") }}
                  </div>
                </b-form-group>

                <b-form-group id="input-group-3" label="Sub Category" label-for="input-3" class="multiDrop">
                  <v-select v-model="form.sub_category_id" :options="SubCategoryOptions" :reduce="(val) => val.value"
                    label="label" :clearable="true" placeholder="Select Sub Category" />
                  <small class="text-danger">{{ errors[0] }}</small>
                  <div class="text-danger" v-if="hasErrors('sub_category_id')">
                    {{ getErrors("sub_category_id") }}
                  </div>
                </b-form-group>
                <b-form-group id="input-group-3" label-for="input-3" class="multiDrop">
                  <label class="required">Tags/Labels</label>
                  <v-select v-model="form.tags_id" :options="TagOptions" label="label" multiple :clearable="true"
                    placeholder="Select Tag" @input="RemoveError('tags_id')">
                    <template #option="{ label }">
                      {{ label }}
                    </template>
                    <template #selected-option="{ label }">
                      {{ label }}
                    </template>
                  </v-select>
                  <small class="text-danger">{{ errors[0] }}</small>
                  <div class="text-danger" v-if="hasErrors('tags_id')">
                    {{ getErrors("tags_id") }}
                  </div>
                </b-form-group>

                <b-form-group class="radioclassification" label="Status">
                  <b-form-radio-group class="radioSpace" v-model="form.status" :options="statusMethods"
                    name="statusMethods" @input="RemoveError('status')"></b-form-radio-group>
                  <div class="text-danger" v-if="hasErrors('status')">
                    {{ getErrors("status") }}
                  </div>
                </b-form-group>



                <b-form-group class="radioclassification" label="Featured Product">
                  <b-form-radio-group class="radioSpace" v-model="form.is_featured" :options="visibilityMethods"
                    name="Featured"></b-form-radio-group>
                </b-form-group>
                <b-form-group label="Visible To" class="radioclassification">
                  <b-form-radio-group class="radioSpace" v-model="form.visible_to" :options="[
                    { text: 'Customer', value: 'customer' },
                    { text: 'Partner', value: 'partner' },
                    { text: 'Both', value: 'both' }
                  ]" name="visibleToOptions"></b-form-radio-group>
                </b-form-group>
                <b-form-group v-if="form.visible_to === 'partner' || form.visible_to === 'both'" class="multiDrop">
                  <label class="required">Select Partners</label>
                  <v-select v-model="form.visible_partner_ids" :options="PartnerOptions" label="name"
                    :reduce="partner => partner.id" multiple :clearable="true" placeholder="Select Partner(s)" />
                  <div class="text-danger" v-if="hasErrors('visible_partner_ids')">
                    {{ getErrors("visible_partner_ids") }}
                  </div>
                </b-form-group>

                <h5 class="sectionTitle innerTitle">Media</h5>

                <b-form-group label="Primary Image">
                  <div class="image-upload-wrapper text-center">
                    <div class="thumbnail-grid">
                      <div v-if="primaryImagePreview" class="thumbnail-container">
                        <img :src="primaryImagePreview" class="thumbnail" alt="Primary Image" />
                        <button class="remove-btn" @click="removePrimaryImage" type="button">×</button>
                      </div>

                      <div v-else-if="existingMainImage" class="thumbnail-container">
                        <img :src="existingMainImage" class="thumbnail" alt="Existing Image" />
                        <button class="remove-btn" @click="removePrimaryImage" type="button">×</button>
                      </div>

                      <div v-else class="thumbnail-container upload-placeholder" @click="triggerPrimaryFileInput">
                        <img src="../../assets/img/uploadimage.png" alt="Upload" />
                      </div>
                    </div>
                    <p class="upload-note mt-2 mb-0">
                      Image resolution should be like 3848px X 3848px. Png, Jpg, Jpeg, WebP etc.
                    </p>

                    <input type="file" ref="primaryFileInput" @change="handlePrimaryImage"
                      accept="image/png, image/jpeg, image/jpg" class="d-none" />

                    <small class="text-danger text-left d-block" v-if="hasErrors('main_image')">
                      {{ getErrors('main_image') }}
                    </small>
                  </div>
                </b-form-group>


                <b-form-group v-if="selectedProductTypeLabel !== 'Configurable'" label="Addtional Images">
                  <div class="image-upload-wrapper text-center">
                    <div class="thumbnail-grid">
                      <div v-for="(media, index) in imagePreviews" :key="index" class="thumbnail-container">
                        <img :src="media.url" class="thumbnail" alt="Selected image" />
                        <button class="remove-btn" @click="removeImage(index)" type="button">
                          ×
                        </button>
                        <small class="text-danger" v-if="hasErrors(`images.${index}`)">
                          {{ getErrors(`images.${index}`) }}
                        </small>
                      </div>
                      <div class="thumbnail-container upload-placeholder" @click="triggerFileInput">
                        <img src="../../assets/img/uploadimage.png" alt="Selected image" />
                      </div>
                    </div>
                    <p class="upload-note mt-2">
                      Image resolution should be like 3848px X 3848px Png, Jpg, Jpeg, WebP
                      etc.
                    </p>

                    <input type="file" ref="fileInput" @change="handleFileChange"
                      accept="image/png, image/jpeg, image/jpg" multiple class="d-none" />
                  </div>
                </b-form-group>



                <b-form-group label="Video">
                  <div class="video-upload-wrapper text-center">
                    <div class="video-thumbnail-grid">
                      <!-- Show video preview if exists -->
                      <div v-if="videoPreviews.length > 0" class="video-thumbnail-container">
                        <video :src="videoPreviews[0].url" class="video-thumbnail" controls muted playsinline></video>
                        <button class="remove-btn" @click="removeVideo(0)" type="button">
                          ×
                        </button>
                        <small class="text-danger jugllingwords" v-if="hasErrors('videos.0')">
                          {{ getErrors('videos.0') }}
                        </small>
                      </div>

                      <!-- Show upload placeholder only if no video exists -->
                      <div v-else class="video-thumbnail-container upload-placeholder" @click="triggerMainVideoInput">
                        <img src="../../assets/img/uploadvid.png" alt="Upload video" />
                      </div>
                    </div>
                    <p class="upload-note mt-4">
                      Video formats allowed: mp4, webm, ogg etc(Size should be upto 2MB)..
                    </p>
                    <input type="file" ref="mainVideoInput" @change="handleVideoChange"
                      accept="video/mp4, video/webm, video/ogg" class="d-none" />
                  </div>
                </b-form-group>
                <b-form-group>
                  <label>Video URL</label>
                  <b-form-input id="input-2" v-model="form.video_url" placeholder="Enter Video URL" required>
                  </b-form-input>
                </b-form-group>
              </TabContent>

              <!-- Second Tab -->
              <!-- Second Tab - Product Details -->
              <TabContent title="Product Details" :before-change="() => onTabChange('Product Details')">
                <h5 class="sectionTitle innerTitle">Material & Attributes</h5>

                <!-- Material Type/Metal Type -->
                <b-form-group id="input-group-3" label-for="input-3" class="multiDrop">
                  <label class="required">Material Type/Metal Type</label>
                  <v-select v-model="form.material_type_id" :options="filteredMaterialTypeOptions"
                    :reduce="(val) => val.value" label="label" :clearable="true" placeholder="Select Metal Type"
                    @update:modelValue="
                      (value) => {
                        generateBasePrice(value);
                        onMetalTypeBasedPurityChange(value);
                        handleMaterialTypeChange(value);
                      }
                    " @input="RemoveError('material_type_id')" />
                  <small class="text-danger">{{ errors[0] }}</small>
                  <div class="text-danger" v-if="hasErrors('material_type_id')">
                    {{ getErrors("material_type_id") }}
                  </div>
                </b-form-group>

                <!-- Purity/Karat -->
                <b-form-group v-if="!isMaterialTypeNA" id="input-group-3" label="Purity/Karat" label-for="input-3"
                  label-class=" required" class="multiDrop">
                  <v-select v-model="form.purity_karat_id" :options="PurityOptions" :reduce="(val) => val.value"
                    label="label" :clearable="true" placeholder="Select Purity/Karat"
                    @input="RemoveError('purity_karat_id')" />
                  <small class="text-danger">{{ errors[0] }}</small>
                  <div class="text-danger" v-if="hasErrors('purity_karat_id')">
                    {{ getErrors("purity_karat_id") }}
                  </div>
                </b-form-group>
                <!-- Metal Weight -->
                <b-form-group v-if="selectedProductTypeLabel !== 'Configurable' && !isMaterialTypeNA" id="input-group-2"
                  label="Metal Weight(g)" label-for="input-2">
                  <b-form-input id="input-2" v-model="form.metal_weight" placeholder="Enter Metal Weight"
                    :disabled="isMaterialTypeNA" @input="RemoveError('metal_weight')"
                    @keypress="allowOnlyDecimalInput"></b-form-input>
                  <small class="text-danger">{{ errors[0] }}</small>
                  <div class="text-danger" v-if="hasErrors('metal_weight')">
                    {{ getErrors("metal_weight") }}
                  </div>
                </b-form-group>

              </TabContent>

              <!-- Third Tab -->
              <TabContent title="Variant & Pricing" :before-change="() => onTabChange('Variant & Pricing')">

                <h5 class="sectionTitle innerTitle">VARIANTS</h5>

                <b-form-group v-if="selectedProductType?.label == 'Configurable'" id="input-group-3"
                  label="Variant Attribute(s)" label-for="input-3" class="multiDrop">
                  <v-select v-model="form.variant_attribute_id" :options="VariantAttributeOption" label="label"
                    :clearable="true" placeholder="Select Variant Attribute" multiple
                    @update:modelValue="onSubVariantChange" :closeOnSelect="false" />
                </b-form-group>

                <b-form-group v-if="selectedProductType?.label == 'Configurable'" id="input-group-3"
                  label="Variant Options" label-for="input-3" class="multiDrop mmmmb0">
                  <v-select v-model="form.variant_options_id" :options="VariantOptions" label="label" :clearable="true"
                    placeholder="Select Variant Option" multiple :group-label="'label'" :group-options="'options'"
                    @update:modelValue="getvarientcombinations" :closeOnSelect="false" />
                </b-form-group>

                <h5 class="sectionTitle mt-4" v-if="AttributeVariantOptions.length">
                  VARIANT OPTION WITH BASE PRICE CALCULATION
                </h5>
                <b-table responsive="sm"
                  v-if="AttributeVariantOptions.length && selectedProductType?.label == 'Configurable'"
                  :items="AttributeVariantOptions" :fields="tableFields" class="settler">
                  <template #cell(upload_image)="data">
                    <div @click="openVariantImageSidebar(data.item)">
                      <template v-if="data.item.is_new == false && data.item.primary_image">
                        <img :src="`/storage/${data.item.primary_image}`"
                          style="width: 50px; height: 50px; object-fit: cover" class="img-thumbnail" />
                      </template>
                      <template v-else-if="data.item.is_new == true && data.item.primary_image">
                        <img :src="`${data.item.primary_image}`" style="width: 50px; height: 50px; object-fit: cover"
                          class="img-thumbnail" />
                      </template>
                      <template v-else>
                        <div class="upload-placeholder">
                          <i class="fas fa-camera fa-xs sizeCamera"></i>
                        </div>
                      </template>
                    </div>
                    <small class="text-danger" v-if="errors[`variants_product.${data.index}.primary_image`]">
                      {{ errors[`variants_product.${data.index}.primary_image`][0] }}
                    </small>
                  </template>
                  <template #cell(variant_name)="data">
                    {{ data.item.variant_name }}
                  </template>


                  <template #cell(metal_weight)="data">
                    <div>
                      <b-form-input v-model="data.item.metal_weight" placeholder="e.g., 4.0 gm"
                        @update:modelValue="validateWeight(data, 'metal_weight')" @keypress="allowOnlyDecimalInput" />
                      <small v-if="data.item.metal_weight_error" class="text-danger">
                        {{ data.item.metal_weight_error }}
                      </small>
                    </div>
                  </template>

                  <template #cell(sku)="data">
                    <b-form-input v-model="data.item.sku" placeholder="SKU" readonly />
                  </template>

                  <template #cell(base_price)="data">
                    <b-form-input v-model="data.item.base_price" type="number" placeholder="₹ Price" readonly />
                  </template>

                  <template #cell(status)="data">
                    <b-form-select v-model="data.item.status" :options="statusOptions" value-field="value"
                      text-field="label" />
                  </template>
                </b-table>
                <small class="text-danger" v-if="errors.variants">
                  {{ errors.variants[0] }}
                </small>


                <b-form-group v-if="selectedProductTypeLabel === 'Ready-made'" id="input-group-2" label="Unit Price"
                  label-for="input-2">
                  <b-form-input id="input-2" v-model="form.unit_price" placeholder="Enter Unit Price"></b-form-input>
                </b-form-group>

                <b-form-group v-if="
                  selectedProductTypeLabel !== 'Ready-made' &&
                  selectedProductTypeLabel !== 'Configurable'
                " id="input-group-2" label="Base Price" label-for="input-2">
                  <b-form-input id="input-2" v-model="form.base_price" placeholder="Enter Base Price"
                    readonly></b-form-input>
                </b-form-group>

                <h5 class="sectionTitle mt-4">CHARGES</h5>
                <div>
                  <div v-for="(charge, index) in productCharges" :key="index" class="charge-row">
                    <b-form-group label="Charges" class="wf-15 mb-0"
                      :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                      <v-select v-model="charge.charges" :options="chargeApplicationOptions" label="label"
                        :reduce="(option) => option.label" placeholder="Select Charges"
                        @update:modelValue="onChargeSelected(charge)" />
                    </b-form-group>


                    <!-- :options="['Percentage (%)', 'Flat']" -->
                    <b-form-group label="Type"
                      :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                      <v-select v-model="charge.type" :options="getChargesTypeOptions(charge.charges)"
                        placeholder="Select Type" />

                    </b-form-group>


                    <b-form-group label="Value"
                      :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                      <b-form-input v-model="charge.value" placeholder="e.g., 10%" />
                    </b-form-group>


                    <b-form-group label="Cost"
                      :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                      <v-select v-model="charge.primary_cost" :options="costoptions" placeholder="Select Cost" />
                    </b-form-group>

                    <b-form-group v-if="selectedProductType?.label != 'Configurable'" label="Calculated Amount"
                      class="wf-30">
                      <b-form-input v-model="charge.calculated_amount" placeholder="Calculated Amount" disabled />
                    </b-form-group>

                    <b-form-group label="Description"
                      :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                      <b-form-input v-model="charge.description" placeholder="Add description" />
                    </b-form-group>


                    <div class="wf-4 zzp" v-if="productCharges.length > 1">
                      <b-button size="sm" class="removeBTN" @click="removeChargeRow(index)">
                        ×
                      </b-button>
                    </div>
                    <div class="wf-4 zzp" v-if="index === productCharges.length - 1">

                      <b-button v-if="index === productCharges.length - 1" size="sm" class="addBTN"
                        @click="addChargeRow">
                        +
                      </b-button>
                    </div>
                  </div>
                </div>
                <div>

                  <h5 class="sectionTitle innerTitle mt-4">TAXES & Other CHARGES</h5>
                  <div>
                    <div v-for="(tax, index) in taxCharges" :key="index" class="charge-row">
                      <b-form-group :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                        <label class="required">Tax Application</label>
                        <v-select v-model="tax.tax_application" :options="taxMasterOptions" label="tax_name"
                          placeholder="Select Tax Application" @update:modelValue="selected => {
                            onTaxSelected(selected, tax);
                            removeTaxError(index, 'tax_application');
                          }" />
                        <small class="text-danger" v-if="errors[`taxes.${index}.tax_application`]">
                          {{ errors[`taxes.${index}.tax_application`][0] }}
                        </small>
                      </b-form-group>

                      <!-- Type -->
                      <b-form-group label="Type"
                        :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                        <v-select v-model="tax.type" :options="['Percentage', 'Flat']" placeholder="Select type"
                          @update:modelValue="removeTaxError(index, 'type')" />
                        <small class="text-danger" v-if="errors[`taxes.${index}.type`]">
                          {{ errors[`taxes.${index}.type`][0] }}
                        </small>
                      </b-form-group>

                      <!-- Value -->
                      <b-form-group label="Value"
                        :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                        <b-form-input v-model="tax.value" placeholder="e.g., 3%" readonly />
                      </b-form-group>


                      <!-- Primary Cost -->
                      <b-form-group label="Cost"
                        :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                        <v-select v-model="tax.primary_cost" :options="TaxCostOptions" label="label"
                          :reduce="(o) => o.label" placeholder="Select Cost"
                          @update:modelValue="removeTaxError(index, 'primary_cost')" />
                        <small class="text-danger" v-if="errors[`taxes.${index}.primary_cost`]">
                          {{ errors[`taxes.${index}.primary_cost`][0] }}
                        </small>
                      </b-form-group>

                      <b-form-group v-if="selectedProductType?.label != 'Configurable'" label="Calculated Amount"
                        class="wf-30">
                        <b-form-input v-model=tax.calculated_amount placeholder="Calculated Amount" disabled />
                      </b-form-group>

                      <!-- Description -->
                      <b-form-group label="Description"
                        :class="selectedProductType?.label != 'Configurable' ? 'wf-30' : 'wf-33'">
                        <b-form-input v-model="tax.description" placeholder="Add description" />
                      </b-form-group>


                      <div class="wf-4 zzp" v-if="taxCharges.length > 1">
                        <b-button size="sm" class="removeBTN" @click="removeTaxesRow(index)">
                          ×
                        </b-button>
                      </div>

                      <div class="wf-4 zzp" v-if="index === taxCharges.length - 1">
                        <b-button size="sm" class="addBTN" @click="addTaxesRow">
                          +
                        </b-button>
                      </div>

                      <!-- Remove Button -->


                      <!-- Add Button -->

                    </div>
                  </div>

                </div>


                <h5 v-if="filteredFinalRows.length && selectedProductType?.label == 'Configurable'"
                  class="sectionTitle innerTitle mt-4">PRODUCT PRICE CALCULATION</h5>
                <b-table responsive="sm" v-if="filteredFinalRows.length && selectedProductType?.label == 'Configurable'"
                  :items="filteredFinalRows" :fields="ConfigurableFields">
                  <template #cell(additional_charges)="data">
                    {{ data.item.additional_charges }}
                  </template>
                  <template #cell(taxes)="data">
                    {{ data.item.taxes }}
                  </template>

                  <template #cell(product_price)="data">
                    <template v-if="data.item.product_price">
                      {{ data.item.product_price }}
                    </template>
                    <template v-else>
                      {{
                        calculateFinalPrice({
                          ...data.item,
                          base_price: data.item.base_price || "0",
                          metal_base: data.item.metal_base || 0,
                          gemstone_base: data.item.gemstone_base || 0,
                        })
                      }}
                    </template>
                  </template>
                </b-table>


                <h5 class="sectionTitle innerTitle" v-if="selectedProductTypeLabel !== 'Configurable'">PRICING</h5>

                <b-form-group v-if="selectedProductTypeLabel !== 'Configurable'" id="input-group-2"
                  label="Product Price" label-for="input-2">
                  <b-form-input id="input-2" v-model="form.final_price" placeholder="Enter Final Price"
                    readonly></b-form-input>
                </b-form-group>
              </TabContent>

              <!-- Fourth Tab -->
              <TabContent title="Inventory & Shipping" :before-change="() => onTabChange('Inventory & Shipping')">
                <template v-if="selectedProductTypeLabel !== 'Configurable'">
                  <h5 class="sectionTitle innerTitle">INVENTORY</h5>
                  <label class="required">Total Stock</label>
                  <b-form-group id="input-group-2" label-for="input-2">
                    <b-form-input id="input-2" v-model="form.total_stock" placeholder="Enter Total Stock"
                      @keypress="allowOnlyNumberInput"
                      @input="() => { RemoveError('total_stock'); validateMainStock(); }"></b-form-input>
                    <div class="text-danger" v-if="hasErrors('total_stock')">
                      {{ getErrors("total_stock") }}
                    </div>
                  </b-form-group>

                  <b-form-group id="input-group-2" label="Low Stock" label-for="input-2">
                    <b-form-input id="input-2" v-model="form.low_stock" placeholder="Enter Low Stock"
                      @keypress="allowOnlyNumberInput" @input="() => { RemoveError('low_stock'); }"></b-form-input>
                    <div class="text-danger" v-if="hasErrors('low_stock')">
                      {{ getErrors("low_stock") }}
                    </div>
                  </b-form-group>

                </template>

                <!-- For configurable products -->
                <template v-else>
                  <h5 class="sectionTitle innerTitle">INVENTORY</h5>
                  <div v-if="AttributeVariantOptions.length">
                    <div v-for="(variant, index) in AttributeVariantOptions" :key="index" class="variant-row">
                      <!-- Variant Name -->
                      <div class="variant-field">
                        <label>Variant Name</label>
                        <div class="deferment">{{ variant.variant_name }}</div>
                      </div>

                      <!-- SKU -->
                      <div class="variant-field">
                        <label>SKU</label>
                        <div class="deferment">{{ variant.sku }}</div>
                      </div>


                      <!-- Total Stock -->
                      <div class="variant-field">
                        <label>Stock Quantity</label>
                        <b-form-input v-model="variant.total_stock" type="number" placeholder="Enter quantity"
                          @keypress="allowOnlyNumberInput" min="0" @input="validateVariantStock(index)" />
                      </div>

                      <!-- Low Stock -->
                      <div class="variant-field">
                        <label>Low Stock Alert</label>
                        <b-form-input v-model="variant.low_stock" type="number" placeholder="Enter threshold"
                          @keypress="allowOnlyNumberInput" min="0" @input="validateVariantStock(index)" />
                        <div class="text-danger" v-if="errors[`variant_options.${index}.low_stock`]">
                          <!-- show per-variant error -->
                          {{ errors[`variant_options.${index}.low_stock`][0] }}
                        </div>
                      </div>

                    </div>
                  </div>

                </template>

                <template v-if="selectedProductTypeLabel !== 'Configurable'">
                  <h5 class="sectionTitle mt-5">SHIPPING</h5>
                  <b-form-group id="input-group-2" label="Packaging Weight (g)" label-for="input-2">
                    <b-form-input id="input-2" type="number" v-model="form.weight" placeholder="Enter Weight"
                      @keypress="allowOnlyDecimalInput"></b-form-input>
                  </b-form-group>

                  <b-form-group label="Unit & Dimensions - L × W × H">
                    <div class="d-flex gap-2 justify-content-between">
                      <b-form-select v-model="form.unit" :options="['cm', 'inch']"></b-form-select>

                      <b-form-input v-model="form.length" placeholder="L"
                        @keypress="allowOnlyDecimalInput"></b-form-input>

                      <b-form-input v-model="form.width" placeholder="W"
                        @keypress="allowOnlyDecimalInput"></b-form-input>

                      <b-form-input v-model="form.height" placeholder="H"
                        @keypress="allowOnlyDecimalInput"></b-form-input>
                    </div>
                  </b-form-group>

                </template>

                <!-- For configurable products -->
                <template v-else>
                  <h5 class="sectionTitle mt-5">SHIPPING</h5>
                  <div v-if="AttributeVariantOptions.length">
                    <div v-for="(variant, index) in AttributeVariantOptions" :key="index" class="variant-row">
                      <!-- Variant Name -->
                      <div class="variant-field">
                        <label>Variant Name</label>
                        <div class="deferment">{{ variant.variant_name }}</div>
                      </div>

                      <!-- SKU -->
                      <div class="variant-field">
                        <label>SKU</label>
                        <div class="deferment">{{ variant.sku }}</div>
                      </div>


                      <!-- Package Weight -->
                      <div class="variant-field">
                        <label>Package Weight (g)</label>
                        <b-form-input v-model="variant.weight" type="number" placeholder="Weight in grams"
                          @keypress="allowOnlyDecimalInput" min="0" step="0.01" />
                      </div>

                      <!-- Dimension Unit -->
                      <div class="variant-field">
                        <label>Dimension Unit</label>
                        <b-form-select v-model="variant.unit" :options="['cm', 'inch']"></b-form-select>
                      </div>
                      <!-- Dimensions (L × W × H) -->
                      <div class="variant-field dimension-group">
                        <label>Dimensions (L × W × H)</label>
                        <div class="dimension-inputs">
                          <b-form-input v-model="variant.length" placeholder="L" @keypress="allowOnlyDecimalInput" />
                          <b-form-input v-model="variant.width" placeholder="W" @keypress="allowOnlyDecimalInput" />
                          <b-form-input v-model="variant.height" placeholder="H" @keypress="allowOnlyDecimalInput" />
                        </div>
                      </div>

                    </div>
                  </div>
                </template>
              </TabContent>

              <TabContent title="SEO,Metadata & Notification">
                <h5 class="sectionTitle">SEO & METADATA</h5>

                <b-form-group id="input-group-2" label="SEO Title" label-for="input-2">
                  <b-form-input id="input-2" v-model="form.seo_title" placeholder="Enter Title"></b-form-input>
                </b-form-group>

                <b-form-group label="Meta Description">
                  <b-form-textarea v-model="form.meta_description" placeholder="Enter Description..." rows="3"
                    max-rows="6" />
                </b-form-group>

                <b-form-group id="input-group-3" label="Slug / URL" label-for="input-3">
                  <b-form-input id="input-3" v-model="form.meta_slug_url" placeholder="Enter URL slug"></b-form-input>
                </b-form-group>

                <!-- Variant SEO Metadata Table for Configurable Products -->
                <div v-if="
                  selectedProductTypeLabel === 'Configurable' &&
                  AttributeVariantOptions.length
                ">
                  <h5 class="sectionTitle mt-5">VARIANTS SEO & METADATA</h5>
                  <b-table responsive="sm" :items="AttributeVariantOptions" :fields="variantSeoFields"
                    class="variant-seo-table">
                    <template #cell(variant_name)="data">
                      {{ data.item.variant_name }}
                    </template>

                    <template #cell(sku)="data">
                      {{ data.item.sku }}
                    </template>

                    <template #cell(actions)="data">
                      <b-button class="GlobalfillBTN" size="sm" @click="openVariantSeoSidebar(data.item, data.index)">
                        {{ data.item.seo_title ? "View" : "Set" }}
                      </b-button>
                    </template>
                  </b-table>
                </div>


                <div :class="{ parentBackground: sidebarstatusvariantSEO.variantSeo }">
                  <div class="filter_sidebar sidebar_main"
                    :class="{ filter_active: sidebarstatusvariantSEO.variantSeo }">
                    <div class="sidebar_toolbox woBorder">
                      <h6>{{ sidebarTitle }}</h6>
                      <div class="sidebar_toolbox_right_side">
                        <img src="../../assets/img/icons/close.svg"
                          @click="sidebarstatusvariantSEO.variantSeo = false" />
                      </div>
                    </div>
                    <div class="sidebar_form">
                      <div class="px-4 py-3 column_sidebar">
                        <b-form-group label="SEO Title">
                          <b-form-input v-model="currentVariantSeo.seo_title"
                            placeholder="Enter variant SEO title"></b-form-input>
                        </b-form-group>

                        <b-form-group label="Meta Description">
                          <b-form-textarea v-model="currentVariantSeo.meta_description"
                            placeholder="Enter variant meta description" rows="3" max-rows="6"></b-form-textarea>
                        </b-form-group>

                        <b-form-group label="Slug / URL">
                          <b-form-input v-model="currentVariantSeo.meta_slug_url"
                            placeholder="Enter variant URL slug"></b-form-input>
                        </b-form-group>
                      </div>

                      <div class="sidebarbtn_group">
                        <div class="buttonGrid">
                          <b-button @click="saveVariantSeo" class="fillBTN">
                            Save
                          </b-button>

                          <b-button class="transBTN" @click="sidebarstatusvariantSEO.variantSeo = false">
                            Cancel
                          </b-button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </TabContent>

              <TabContent title="Additional Products">
                <div>
                  <div class="CrudTitle blendAdditional">
                    <h5 class="sectionTitle">RELATED PRODUCT</h5>
                    <b-button class="GlobaltransBTN" @click="openRelatedProductsSidebar">
                      Add
                    </b-button>
                  </div>
                  <b-table responsive="sm" v-if="relatedProducts.length != 0" :items="relatedProducts" :fields="[
                    { key: 'selection', label: '', class: 'text-center', tdClass: 'selection-col', thStyle: { width: '40px' }, },
                    { key: 'name', label: 'Product Name' },
                    { key: 'details', label: 'Details' },
                    { key: 'status', label: 'Status', class: 'text-center' },
                  ]" :show-empty="true" empty-text="No related products selected">
                    <template #cell(selection)="row">
                      <b-form-checkbox :checked="true" @change="removeRelatedProduct(row.index)"
                        aria-label="Remove related product" />
                    </template>

                    <template #cell(name)="row">
                      {{ row.item.name }}
                    </template>

                    <template #cell(details)="row">
                      {{ row.item.sku }} / {{ row.item.product_type_label }}
                    </template>

                    <template #cell(status)>
                      <b-badge variant="success">Active</b-badge>
                    </template>

                  </b-table>
                </div>

                <!-- You May Also Like Section -->
                <div>
                  <div class="CrudTitle blendAdditional">
                    <h5 class="sectionTitle">YOU MAY ALSO LIKE</h5>
                    <b-button class="GlobaltransBTN" @click="openYouMayLikeSidebar">
                      Add
                    </b-button>
                  </div>
                  <b-table responsive="sm" v-if="youMayLikeProducts.length != 0" :items="youMayLikeProducts" :fields="[
                    { key: 'selection', label: '', class: 'text-center', tdClass: 'selection-col', thStyle: { width: '40px' }, },
                    { key: 'name', label: 'Product Name' },
                    { key: 'details', label: 'Details' },
                    { key: 'status', label: 'Status', class: 'text-center' },
                  ]" :show-empty="true" empty-text="No products selected">
                    <template #cell(selection)="row">
                      <b-form-checkbox :checked="true" @change="removeYouMayLikeProduct(row.index)"
                        aria-label="Remove recommended product" />
                    </template>

                    <template #cell(name)="row">
                      {{ row.item.name }}
                    </template>

                    <template #cell(details)="row">
                      {{ row.item.sku }} / {{ row.item.product_type_label }}
                    </template>

                    <template #cell(status)>
                      <b-badge variant="success">Active</b-badge>
                    </template>
                  </b-table>
                </div>
              </TabContent>

              <div :class="{ parentBackground: showRelatedProductsSidebar }">
                <div class="filter_sidebar sidebar_main" :class="{ filter_active: showRelatedProductsSidebar }">
                  <div class="sidebar_toolbox woBorder">
                    <h6>{{ sidebarTitle }}</h6>
                    <div class="sidebar_toolbox_right_side">
                      <img src="../../assets/img/icons/close.svg" @click="showRelatedProductsSidebar = false" />
                      <!-- <CloseIcon @click="sidebarstatus.add = false" /> -->
                    </div>
                  </div>
                  <div class="sidebar_form">
                    <div class="px-4 py-3 column_sidebar">
                      <b-form-group label="Product Type" class="multiDrop">
                        <v-select v-model="productFilters.product_type_id" :options="ProductTypeOptions"
                          :reduce="(val) => val.value" label="label" placeholder="Select Product Type"
                          @update:modelValue="fetchRelatedProducts" />
                      </b-form-group>

                      <b-form-group label="Category" class="multiDrop">
                        <v-select v-model="productFilters.category_id" :options="CategoryOptions"
                          :reduce="(val) => val.value" label="label" placeholder="Select Category"
                          @update:modelValue="onRelatedCategoryChange" />
                      </b-form-group>

                      <b-form-group label="Sub Category" class="multiDrop">
                        <v-select v-model="productFilters.sub_category_id" :options="SubCategoryOptions"
                          :reduce="(val) => val.value" label="label" placeholder="Select Sub Category"
                          @update:modelValue="fetchRelatedProducts" />
                      </b-form-group>

                      <b-form-group label="Product Name" class="multiDrop">
                        <v-select :options="filteredRelatedProducts" v-model="selectedProductNames" multiple
                          label="name" :reduce="p => p.id">
                          <template #list-header>
                            <li class="dynamites d-flex align-items-center px-2" @mousedown.prevent
                              @click.stop="selectingAllValues = !selectingAllValues">
                              <b-form-checkbox v-model="selectingAllValues" class="pe-none">
                                <strong>Select / Deselect All</strong>
                              </b-form-checkbox>
                            </li>
                          </template>



                          <template #option="{ name, sku }">
                            <div class="d-flex align-items-center px-2">
                              {{ name }} - {{ sku }}
                            </div>
                          </template>

                          <template #selected-option="{ name, sku }">
                            <span class="d-flex align-items-center px-2">
                              {{ name }} - {{ sku }}
                            </span>
                          </template>
                        </v-select>
                      </b-form-group>
                    </div>

                    <div class="sidebarbtn_group">
                      <div class="buttonGrid">
                        <b-button @click="saveSelectedProducts" class="fillBTN">
                          Save
                        </b-button>

                        <b-button class="transBTN" @click="closeSidebar">
                          Cancel
                        </b-button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <template v-slot:footer="props">
                <div class="wizard-footer-left buttonGrid">
                  <!-- Cancel (only on first step) -->
                  <b-button v-if="props.activeTabIndex == 0" class="transBTN wizard-button" @click="goBack()"
                    :style="props.fillButtonStyle">
                    Cancel
                  </b-button>

                  <!-- Back button (except first step) -->
                  <b-button v-if="props.activeTabIndex > 0" class="transBTN wizard-button" @click="props.prevTab()"
                    :style="props.fillButtonStyle">
                    Back
                  </b-button>

                  <!-- Next button (always visible except last step) -->
                  <b-button v-if="!props.isLastStep" @click="props.nextTab()" class="fillBTN wizard-button"
                    :style="props.fillButtonStyle">
                    Next
                  </b-button>

                  <!-- Save as Draft button (if status is Draft and not last step) -->
                  <b-button v-if="form.status === 'draft' && !props.isLastStep" @click="submitForm"
                    class="fillBTN wizard-button draftBTN" :style="props.fillButtonStyle">
                    Save as Draft
                  </b-button>

                  <!-- Done button (only on last step) -->
                  <b-button v-if="props.isLastStep" @click="submitForm" class="fillBTN wizard-button"
                    :style="props.fillButtonStyle">
                    Done
                  </b-button>
                </div>
              </template>


            </FormWizard>

            <div :class="{ parentBackground: sidebarstatus.variantImage }">
              <div class="filter_sidebar sidebar_main" :class="{ filter_active: sidebarstatus.variantImage }">
                <div class="sidebar_toolbox woBorder">
                  <h6>Upload Images for {{ currentVariant.variant_name }}</h6>
                  <div class="sidebar_toolbox_right_side">
                    <img src="../../assets/img/icons/close.svg" @click="sidebarstatus.variantImage = false" />
                  </div>
                </div>

                <div class="sidebar_form">
                  <div class="px-4 py-3 column_sidebar">
                    <!-- Primary Image Upload -->
                    <b-form-group label="Primary Image">
                      <div class="image-upload-wrapper text-center">
                        <input type="file" ref="primaryImageInput" @change="handleVariantPrimaryImage"
                          accept="image/png, image/jpeg, image/jpg" class="d-none" />
                        <div class="thumbnail-grid">
                          <template v-if="variantPrimaryImagePreview">
                            <div class="thumbnail-container">
                              <img :src="variantPrimaryImagePreview" class="thumbnail" alt="Primary Variant Image" />
                              <button class="remove-btn" @click="variantPrimaryImagePreview = null" type="button">
                                ×
                              </button>
                            </div>
                          </template>
                          <template v-else>
                            <div class="thumbnail-container upload-placeholder"
                              @click="$refs.primaryImageInput.click()">
                              <img src="../../assets/img/uploadimage.png" alt="Upload image" />
                            </div>
                          </template>
                        </div>
                        <p class="upload-note mt-2 mb-0">Recommended: 500px × 600px</p>
                        <div class="text-danger text-left d-block" v-if="hasErrors(primaryImageErrorKey)">
                          {{ getErrors(primaryImageErrorKey) }}
                        </div>
                      </div>
                    </b-form-group>

                    <!-- Additional Images -->
                    <b-form-group label="Additional Images">
                      <div class="image-upload-wrapper text-center">
                        <div class="thumbnail-grid">
                          <div v-for="(img, idx) in variantAdditionalImages" :key="idx" class="thumbnail-container">
                            <img :src="img.url" class="thumbnail" alt="Additional image" />
                            <button class="remove-btn" @click="removeVariantAdditionalImage(idx)" type="button">
                              ×
                            </button>
                          </div>
                          <div class="thumbnail-container upload-placeholder"
                            @click="$refs.additionalImagesInput.click()">
                            <img src="../../assets/img/uploadimage.png" alt="Add image" />
                          </div>
                        </div>
                        <p class="upload-note mt-2">
                          Image resolution should be like 3848px X 3848px. Png, Jpg,
                          Jpeg etc.
                        </p>
                        <input type="file" ref="additionalImagesInput" @change="handleVariantAdditionalImages"
                          accept="image/png, image/jpeg, image/jpg" multiple class="d-none" />
                      </div>
                    </b-form-group>


                  </div>

                  <div class="sidebarbtn_group">
                    <div class="buttonGrid">
                      <b-button @click="saveVariantMedia" class="fillBTN">
                        Save
                      </b-button>

                      <b-button class="transBTN" @click="sidebarstatus.variantImage = false">
                        Cancel
                      </b-button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import axiosEmployee from '@axiosEmployee';
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import { FormWizard, TabContent } from "vue3-form-wizard";
import "vue3-form-wizard/dist/style.css";
import { toast } from "vue3-toastify";

import { toRaw } from "vue";

import { useRouter } from 'vue-router';
const router = useRouter();
const props = defineProps({
  productId: {
    type: [String, Number],
    required: true,
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["submitted", "cancel"]);

const CategoryOptions = ref([]);
const SubCategoryOptions = ref([]);
const VariantOptions = ref([]);
const ProductTypeOptions = ref([]);
const TagOptions = ref([]);
const MaterialTypeOptions = ref([]);
const PurityOptions = ref([]);
const VariantAttributeOption = ref([]);
const PartnerOptions = ref([]);

const statusMethods = [
  { text: "Active", value: "active" },
  { text: "Inactive", value: "inactive" },
  { text: "Draft", value: "draft" },
];



const productCharges = ref([
  {
    charges: "",
    type: "Percentage (%)",
    value: "",
    primary_cost: "",
    description: "",
    calculated_amount: "",
  },
]);
const taxCharges = ref([
  {
    tax_application: "",
    type: "Percentage",
    value: "",
    primary_cost: "",
    description: "",
    calculated_amount: "",
  },
]);

const visibilityMethods = [
  { text: "Yes", value: true },
  { text: "No", value: false }
];


const costoptions = computed(() => {
  const option = [
    'Base Price',
  ]
  if (!isMaterialTypeNA.value) {
    option.splice(1, 0, 'Metal Weight Cost') // Insert gemstone_weight after metal_weight
  }

  return option
})
const ConfigurableFields = computed(() => {
  const option = [
    'variant_name',
    'sku',
    'base_price',
    'additional_charges',
    'taxes',
    'product_price',
  ]
  if (!isMaterialTypeNA.value) {
    option.splice(2, 0, 'metal_weight') // Insert gemstone_weight after metal_weight
  }

  return option
})

const getChargesTypeOptions = (chargeType) => {
  const typeOptionsMap = {
    'Stone Setting Charges': ['Flat'],
    'Gemstone Certification': ['Flat'],
    'Making Charges': ['Percentage (%)', 'Flat'],
    'Wastage Charges': ['Percentage (%)', 'Flat'],
    'Additional Charges': ['Percentage (%)', 'Flat'],
    'Polishing Charges': ['Percentage (%)', 'Flat'],
    'Engraving Charges': ['Flat'],
  };

  return typeOptionsMap[chargeType] || ['Percentage (%)', 'Flat'];
};





const encodeBase64 = (data) => {
  if (data === undefined || data === null) {
    return "";
  }
  return btoa(data.toString());
};

const chargeApplicationOptions = ref([]);
const taxMasterOptions = ref([]);

// Image and Video Upload
const imagePreviews = ref([]);
const fileInput = ref(null);
const primaryFileInput = ref(null);
const videoPreviews = ref([]);
const primaryImagePreview = ref(null);

const form = reactive({
  sku: "",

  id: null,
  product_name: "",
  main_image: null,
  description: "",
  category_id: null,
  sub_category_id: null,
  product_type_id: null,
  tags_id: [],
  material_type_id: null,
  purity_karat_id: null,
  variant_attribute_id: [],
  variant_options_id: [],
  gemstone_weight: "",
  metal_weight: "",
  weight: "",
  unit: "cm",
  length: "",
  width: "",
  height: "",
  video_url: "",
  base_price: "",
  unit_price: "",
  final_price: "",
  discount: "",
  status: "active",
  variants: "no",
  additional_charges: false,
  making_charges: "",
  wastage_charges: "",
  dimensions: "",
  shipping_charges: "",

  total_stock: "",
  seo_title: "",
  meta_description: "",
  meta_slug_url: "",
  low_stock: "",
  sku: "",
  making_type: "percentage",
  wastage_type: "percentage",
  images: [],
  videos: [],
  existing_media: [],
  deleted_media: [],
  is_featured: false, // default to not featured
  existing_main_image: null, // To store existing image path
  remove_main_image: false,
  children_products: [],
  visible_to: 'customer',
  visible_partner_ids: [],
});

function allowOnlyDecimalInput(event) {
  const char = String.fromCharCode(event.which);
  const isNumber = /[0-9]/.test(char);
  const isDot = char === ".";
  const alreadyHasDot = event.target.value.includes(".");

  if (!isNumber && (!isDot || alreadyHasDot)) {
    event.preventDefault();
  }
}

function allowOnlyNumberInput(event) {
  const char = String.fromCharCode(event.which);
  const isNumber = /[0-9]/.test(char);

  if (!isNumber) {
    event.preventDefault();
  }
}

const selectingAllValues = ref(false);


const toggleAllProducts = () => {
  selectingAllValues.value
    ? selectedProductNames.value = filteredRelatedProducts.value
    : selectedProductNames.value = [];
};

// Related Products Data
const relatedProducts = ref([]);
const youMayLikeProducts = ref([]);
const showRelatedProductsSidebar = ref(false);
const currentSelectionType = ref("related"); // 'related' or 'youMayLike'
const filteredRelatedProducts = ref([]);

// Product Filters
const productFilters = reactive({
  product_type_id: null,
  category_id: null,
  sub_category_id: null,
  search: "",
});

const selectedProductNames = ref([]);

const tableFields = computed(() => {
  const fields = [
    'upload_image',
    'variant_name',
    'sku',
    'base_price',
    'status'
  ]

  if (!isMaterialTypeNA.value) {
    fields.splice(2, 0, 'metal_weight')
  }
  return fields
})


const sidebarTitle = ref("Select Products");

// Methods for Related Products
const openRelatedProductsSidebar = async () => {
  currentSelectionType.value = "related";
  sidebarTitle.value = "Select Related Products"; // Set title for related products
  showRelatedProductsSidebar.value = true;
  // Initialize filters with current product's values
  productFilters.product_type_id = form.product_type_id;
  productFilters.category_id = form.category_id;
  productFilters.sub_category_id = form.sub_category_id;
  await fetchRelatedProducts();

  // ✅ Clear previous selection first
  selectedProductNames.value = [];
};

const openYouMayLikeSidebar = async () => {
  currentSelectionType.value = "youMayLike";
  sidebarTitle.value = "Select You May Also Like Products"; // Set title for "you may also like"
  showRelatedProductsSidebar.value = true;
  await fetchRelatedProducts();

  // ✅ Clear previous selection first
  selectedProductNames.value = [];
};

const closeSidebar = () => {
  showRelatedProductsSidebar.value = false;
};

const onRelatedCategoryChange = async (value) => {
  productFilters.category_id = value;
  productFilters.sub_category_id = null;

  if (value) {
    const response = await axiosEmployee.get(`/SubCategoryOptions/${value}`);
    SubCategoryOptions.value = response.data;
  } else {
    SubCategoryOptions.value = [];
  }

  await fetchRelatedProducts();
};
const goBack = () => {
  router.push('/admin/products')
}
const onProductTypeChange = (value) => {
  RemoveError("product_type_id");

  const selectedType = ProductTypeOptions.value.find((opt) => opt.value === value);

  // ✅ Automatically set variants_mode based on Product Type
  if (selectedType?.label == "Configurable") {
    form.variants_mode = "yes";
  } else {
    form.variants_mode = "no";
  }
};
const fetchRelatedProducts = async () => {
  try {
    const params = {
      product_type_id: productFilters.product_type_id,
      category_id: productFilters.category_id,
      sub_category_id: productFilters.sub_category_id,
      exclude: props.productId,
    };

    const response = await axiosEmployee.get("/products/related", { params });
    filteredRelatedProducts.value = response.data.data.map((product) => ({
      ...product,
      // Add product_type_label if not already included in response
      product_type_label:
        product.product_type_label ||
        ProductTypeOptions.value.find(
          (opt) => opt.value === product.product_type_id
        )?.label ||
        "Simple",
    }));

  } catch (error) {
    console.error("Error fetching related products:", error);
    filteredRelatedProducts.value = [];
  }
};

const saveSelectedProducts = () => {
  if (selectedProductNames.value.length > 0) {
    const selectedProducts = filteredRelatedProducts.value.filter((product) =>
      selectedProductNames.value.some((selected) => selected.id === product.id)
    );

    if (currentSelectionType.value === "related") {
      // Merge without duplicates
      const existingIds = new Set(relatedProducts.value.map((p) => p.id));
      relatedProducts.value = [
        ...relatedProducts.value,
        ...selectedProducts.filter((p) => !existingIds.has(p.id)),
      ];
    } else {
      const existingIds = new Set(youMayLikeProducts.value.map((p) => p.id));
      youMayLikeProducts.value = [
        ...youMayLikeProducts.value,
        ...selectedProducts.filter((p) => !existingIds.has(p.id)),
      ];
    }
  }

  //  Always clear selection and close sidebar
  selectedProductNames.value = [];
  closeSidebar();
};

const removeRelatedProduct = (index) => {
  relatedProducts.value.splice(index, 1);
};

const removeYouMayLikeProduct = (index) => {
  youMayLikeProducts.value.splice(index, 1);
};

// related product end

const AttributeVariantOptions = ref([]);
const metalBasePrice = ref(0);
const gemstoneBasePrice = ref(0);

const selectedProductType = computed(() =>
  ProductTypeOptions.value.find((item) => item.value === form.product_type_id)
);

const selectedProductTypeLabel = computed(() => {
  return (
    ProductTypeOptions.value.find((item) => item.value === form.product_type_id)
      ?.label || ""
  );
});

//variant seo

// Update your sidebarstatus ref to include variantSeo
const sidebarstatusvariantSEO = ref({
  variantImage: false,
  variantSeo: false,
});

// Add currentVariantSeo ref
const currentVariantSeo = ref({
  variantId: null,
  variantName: "",
  seo_title: "",
  meta_description: "",
  meta_slug_url: "",
  index: null,
});

// Add variantSeoFields ref
const variantSeoFields = ref([
  { key: "variant_name", label: "Variant Name" },
  { key: "sku", label: "SKU" },
  { key: "actions", label: "Actions" },
]);

// Add methods for handling variant SEO
const openVariantSeoSidebar = (variant, index) => {
  currentVariantSeo.value = {
    variantId: variant.id,
    variantName: variant.variant_name,
    seo_title: variant.seo_title || "",
    meta_description: variant.meta_description || "",
    meta_slug_url: variant.meta_slug_url || "",
    index: index,
  };
  sidebarstatusvariantSEO.value.variantSeo = true;
};

const saveVariantSeo = () => {
  const variantIndex = AttributeVariantOptions.value.findIndex(
    (v) => v.id === currentVariantSeo.value.variantId
  );

  if (variantIndex !== -1) {
    AttributeVariantOptions.value[variantIndex] = {
      ...AttributeVariantOptions.value[variantIndex],
      seo_title: currentVariantSeo.value.seo_title,
      meta_description: currentVariantSeo.value.meta_description,
      meta_slug_url: currentVariantSeo.value.meta_slug_url,
    };
  }

  sidebarstatusvariantSEO.value.variantSeo = false;
};

// variant seo end

// Media Upload Methods

const triggerFileInput = () => fileInput.value.click();
const triggerPrimaryFileInput = () => primaryFileInput.value.click();

// const triggerVideoInput = () => videoInput.value.click();

const mainVideoInput = ref(null);
const variantVideoInput = ref(null);

const triggerMainVideoInput = () => mainVideoInput.value.click();
const triggerVariantVideoInput = () => variantVideoInput.value.click();

const handleFileChange = (event) => {
  const files = Array.from(event.target.files);
  files.forEach((file) => {
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        if (!imagePreviews.value.some((img) => img.url === e.target.result)) {
          imagePreviews.value.push({
            url: e.target.result,
            type: "image",
            file: file,
            isNew: true,
          });
        }
      };
      reader.readAsDataURL(file);
    }
  });
  event.target.value = "";
};

const removeImage = (index) => {
  const media = imagePreviews.value[index];
  if (!media.isNew && media.id) {
    form.deleted_media.push(media.id);
  }
  imagePreviews.value.splice(index, 1);
};

// Add this computed property
const existingMainImage = computed(() => {
  return form.existing_main_image
    ? `/storage/${form.existing_main_image}`
    : null;
});

// Modified image handling methods
function removePrimaryImage() {
  if (form.existing_main_image) {
    // Mark existing image for deletion
    form.remove_main_image = true;
  }

  // Clear all image references
  primaryImagePreview.value = null;
  form.main_image = null;
  form.existing_main_image = null;

  // Reset file input
  if (primaryFileInput.value) {
    primaryFileInput.value.value = '';
  }
}



// Change handleVideoChange method to handle single video
const handleVideoChange = (event) => {
  const file = event.target.files[0];
  if (file && file.type.startsWith("video/")) {
    // Clear existing video
    videoPreviews.value = [];

    const url = URL.createObjectURL(file);
    videoPreviews.value.push({
      url: url,
      type: "video",
      file: file,
      isNew: true,
    });
  }
  event.target.value = "";
};

// Update removeVideo method
const removeVideo = (index) => {
  if (videoPreviews.value.length > 0) {
    const media = videoPreviews.value[index];
    if (!media.isNew && media.id) {
      form.deleted_media.push(media.id);
    }
    URL.revokeObjectURL(videoPreviews.value[index].url);
    videoPreviews.value = [];
  }
};


function handlePrimaryImage(event) {
  const file = event.target.files[0];
  if (file) {
    // User selected a new image
    form.remove_main_image = false;
    form.existing_main_image = null;

    const reader = new FileReader();
    reader.onload = (e) => {
      primaryImagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
    form.main_image = file;
  }
}


//variant images

// Add these to your data properties
const currentVariant = ref({});
const variantPrimaryImageFile = ref(null);
const variantPrimaryImagePreview = ref(null);
const variantAdditionalImages = ref([]);
const variantVideos = ref([]);
const variantcharges = ref([]);
const varianttaxes = ref([]);
const sidebarstatus = ref({
  variantImage: false,
});

// Methods for handling variant media
const openVariantImageSidebar = (variant) => {
  currentVariant.value = variant;

  // Reset media states
  variantPrimaryImageFile.value = null;
  // variantPrimaryImagePreview.value = variant.primary_image || null;

  const image =
    variant.product_variant_image ?? variant.primary_image ?? null;

  variantPrimaryImagePreview.value = image
    ? `/storage/${image}`
    : null;

  // Set existing additional images
  variantAdditionalImages.value = variant.additional_images
    ? variant.additional_images.map((img) => ({
      url: img,
      file: null,
      isNew: false,
    }))
    : [];

  // Set existing videos
  variantVideos.value = variant.videos
    ? variant.videos.map((vid) => ({
      url: vid,
      file: null,
      isNew: false,
    }))
    : [];

  sidebarstatus.value.variantImage = true;
};

const handleVariantPrimaryImage = (event) => {

  const file = event.target.files[0];
  if (file && file.type.startsWith("image/")) {
    variantPrimaryImageFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      variantPrimaryImagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
  event.target.value = "";
};

const handleVariantAdditionalImages = (event) => {
  const files = Array.from(event.target.files);
  files.forEach((file) => {
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        variantAdditionalImages.value.push({
          url: e.target.result,
          file: file,
          isNew: true,
        });
      };
      reader.readAsDataURL(file);
    }
  });
  event.target.value = "";
};

const handleVariantVideoUpload = (event) => {
  const files = Array.from(event.target.files);
  files.forEach((file) => {
    if (file.type.startsWith("video/")) {
      const url = URL.createObjectURL(file);
      variantVideos.value.push({
        url: url,
        file: file,
        isNew: true,
      });
    }
  });
  event.target.value = "";
};

const removeVariantAdditionalImage = (index) => {
  variantAdditionalImages.value.splice(index, 1);
};

const removeVariantVideo = (index) => {
  URL.revokeObjectURL(variantVideos.value[index].url);
  variantVideos.value.splice(index, 1);
};

const saveVariantMedia = () => {


  const variantIndex = AttributeVariantOptions.value.findIndex(
    (v) => v.id === currentVariant.value.id
  );

  if (variantIndex !== -1) {
    // Update primary image
    if (variantPrimaryImageFile.value) {
      AttributeVariantOptions.value[variantIndex].is_new
      AttributeVariantOptions.value[variantIndex].primary_image =
        variantPrimaryImagePreview.value;
      AttributeVariantOptions.value[variantIndex].primary_image_file =
        variantPrimaryImageFile.value;
      AttributeVariantOptions.value[variantIndex].is_new = true;
    }

    // Update additional images
    AttributeVariantOptions.value[variantIndex].additional_images =
      variantAdditionalImages.value.map((img) => img.url);
    AttributeVariantOptions.value[variantIndex].additional_image_files =
      variantAdditionalImages.value
        .filter((img) => img.isNew)
        .map((img) => img.file);

    // Update videos
    AttributeVariantOptions.value[variantIndex].videos =
      variantVideos.value.map((vid) => vid.url);
    AttributeVariantOptions.value[variantIndex].video_files =
      variantVideos.value.filter((vid) => vid.isNew).map((vid) => vid.file);
  }

  sidebarstatus.value.variantImage = false;
};

// Unified helpers
const getPrimaryCostAmount = (label, bases, chargeMap = {}) => {
  if (label === 'Metal Weight Cost') return bases.metalBase;
  if (label === 'Gemstone Cost') return bases.gemstoneBase;
  if (label === 'Base Price') return bases.base;
  return parseFloat(chargeMap[label]) || 0;
};

// Normalize any incoming primary_cost (object/string/empty) to a safe label string
function normalizePrimaryCost(value) {
  if (!value) return 'Base Price';
  if (typeof value === 'object' && value.label) return String(value.label);
  return String(value);
}

const computeChargeAmount = (charge, bases) => {
  const value = parseFloat((charge.value || '').toString().replace('%', '')) || 0;
  if (charge.primary_cost == null || !charge.charges) return 0;
  const baseAmount = getPrimaryCostAmount(charge.primary_cost, bases);
  const isPercent = typeof charge.type === 'string' && charge.type.includes('Percentage');
  const amount = isPercent ? (baseAmount * value) / 100 : value;

  charge.calculated_amount = isNaN(amount) ? 0 : parseFloat(amount.toFixed(2));
  return charge.calculated_amount;
};

const computeTaxAmount = (tax, bases, chargeMap) => {
  const value = parseFloat((tax.value || '').toString().replace('%', '')) || 0;

  if (tax.primary_cost == null) return 0;

  const baseAmount = getPrimaryCostAmount(tax.primary_cost, bases, chargeMap);
  const isPercent = typeof tax.type === 'string' && tax.type.includes('Percentage');
  const amount = isPercent ? (baseAmount * value) / 100 : value;

  tax.calculated_amount = isNaN(amount) ? 0 : parseFloat(amount.toFixed(2));
  return tax.calculated_amount;
};


const calculateAdditionalCharges = (item) => {
  const bases = {
    base: parseFloat(item.base_price) || 0,
    metalBase: parseFloat(item.metal_base) || 0,
    gemstoneBase: parseFloat(item.gemstone_base) || 0,
  };

  let additional = 0;
  if (!variantcharges.value[item.id]) {
    variantcharges.value[item.id] = [];
  }

  const map = {};
  for (const charge of productCharges.value) {
    // normalize primary_cost to string
    charge.primary_cost = normalizePrimaryCost(charge.primary_cost);
    const amt = computeChargeAmount(charge, bases);
    if (!charge.charges) continue;
    const idx = variantcharges.value[item.id].findIndex((c) => c.label === charge.charges);
    if (idx !== -1) {
      variantcharges.value[item.id][idx].amount = amt;
    } else {
      variantcharges.value[item.id].push({ label: charge.charges, amount: amt });
    }
    map[charge.charges] = amt;
    additional += amt;
  }
  return Math.round(additional);
};

const calculateTaxes = (item) => {
  const bases = {
    base: parseFloat(item.base_price) || 0,
    metalBase: parseFloat(item.metal_base) || 0,
    gemstoneBase: parseFloat(item.gemstone_base) || 0,
  };

  if (!varianttaxes.value[item.id]) {
    varianttaxes.value[item.id] = [];
  }

  const charges = variantcharges.value[item.id] || [];
  const chargeMap = charges.reduce((acc, c) => {
    acc[c.label] = c.amount;
    return acc;
  }, {});

  let totalTax = 0;
  for (const tax of taxCharges.value) {
    tax.primary_cost = normalizePrimaryCost(tax.primary_cost);
    const amt = computeTaxAmount(tax, bases, chargeMap);
    totalTax += amt;

    const idx = varianttaxes.value[item.id].findIndex((c) => c.label === tax.tax_application);
    if (idx !== -1) {
      varianttaxes.value[item.id][idx].amount = amt;
    } else {
      varianttaxes.value[item.id].push({ label: tax.tax_application, amount: amt });
    }
  }

  return Math.round(totalTax);
};

const calculateFinalPrice = (item) => {
  const base = parseFloat(item.base_price) || 0;
  const additional = calculateAdditionalCharges(item);
  const tax = calculateTaxes(item);
  return Math.round(base + additional + tax);
};

// Pure helpers to avoid mutating during render
function normalizePrimaryCostLabel(val) {
  if (!val) return '';
  if (typeof val === 'object' && val.label) return val.label;
  return String(val);
}

function computeAdditionalChargesPure(item) {
  const base = parseFloat(item.base_price) || 0;
  const metalBase = parseFloat(item.metal_base) || 0;
  const gemstoneBase = parseFloat(item.gemstone_base) || 0;
  let total = 0;
  const chargeMap = {};

  for (const charge of productCharges.value) {
    const value = parseFloat((charge.value || '').toString().replace('%', '')) || 0;
    const label = charge.charges;
    const primary = normalizePrimaryCostLabel(charge.primary_cost);
    if (!label || !primary || !value) continue;

    let baseAmount = 0;
    if (primary === 'Base Price') baseAmount = base;
    else if (primary === 'Metal Weight Cost') baseAmount = metalBase;
    else if (primary === 'Gemstone Cost') baseAmount = gemstoneBase;
    else if (chargeMap[primary] != null) baseAmount = chargeMap[primary];
    else baseAmount = 0;

    const isPercent = typeof charge.type === 'string' && charge.type.includes('Percentage');
    const amt = isPercent ? (baseAmount * value) / 100 : value;
    const amount = isNaN(amt) ? 0 : amt;
    chargeMap[label] = amount;
    total += amount;
  }

  return { total: Math.round(total), chargeMap };
}

function computeTaxesPure(item, chargeMap) {
  const base = parseFloat(item.base_price) || 0;
  const metalBase = parseFloat(item.metal_base) || 0;
  const gemstoneBase = parseFloat(item.gemstone_base) || 0;
  let totalTax = 0;
  const row_taxes = [];

  for (const tax of taxCharges.value) {
    const value = parseFloat((tax.value || '').toString().replace('%', '')) || 0;
    if (!value) continue;

    const primary = normalizePrimaryCostLabel(tax.primary_cost);
    if (!primary) continue;

    let baseAmount = 0;
    if (primary === 'Base Price') baseAmount = base;
    else if (primary === 'Metal Weight Cost') baseAmount = metalBase;
    else if (primary === 'Gemstone Cost') baseAmount = gemstoneBase;
    else if (chargeMap && chargeMap[primary] != null) baseAmount = chargeMap[primary];
    else baseAmount = 0;

    const isPercent = typeof tax.type === 'string' && tax.type.includes('Percentage');
    const amt = isPercent ? (baseAmount * value) / 100 : value;
    const taxAmount = isNaN(amt) ? 0 : amt;
    totalTax += taxAmount;

    // 🟢 Add per-row breakdown
    row_taxes.push({
      tax_application: tax.tax_application || '',
      type: tax.type || '',
      value: value,
      primary_cost: tax.primary_cost || '',
      calculated_amount: Math.round(taxAmount),
    });
  }

  return {
    total: Math.round(totalTax),
    row_taxes,
  };
}


const filteredFinalRows = computed(() => {
  return AttributeVariantOptions.value
    .filter((item) => {
      const metal = parseFloat(item.metal_weight) || 0;
      const gem = parseFloat(item.gemstone_weight) || 0;
      const base = parseFloat(item.base_price) || 0;
      return base > 0 && (metal > 0 || gem > 0);
    })
    .map((item) => {
      const base = parseFloat(item.base_price) || 0;
      const { total: addl, chargeMap } = computeAdditionalChargesPure(item);
      const { total: taxes, row_taxes } = computeTaxesPure(item, chargeMap);
      const final = Math.round(base + addl + taxes);
      return {
        ...item,
        additional_charges: addl,
        taxes,
        row_taxes, // 🟢 include per-row breakdown
        product_price: final,
      };

    });
});

// Form Methods
const addChargeRow = () => {
  productCharges.value.push({
    charges: '',
    type: 'Percentage (%)',
    value: '',
    primary_cost: '',
    description: '',
  });
};

const removeChargeRow = (index) => {
  productCharges.value.splice(index, 1);
};

const addTaxesRow = () => {
  taxCharges.value.push({
    tax_application: "",
    type: "Percentage",
    value: "",
    primary_cost: "",
    description: "",
    calculated_amount: 0
  });
};

const onTaxSelected = (selected, item) => {
  if (selected) {
    item.tax_application = selected.tax_name;
    item.value = selected.tax_rate;
  } else {
    item.tax_application = null;
    item.value = null;
  }
};
const removeTaxesRow = (index) => {
  taxCharges.value.splice(index, 1);
};
// const removeTaxError = (index, field) => {
//   delete errors[`taxes.${index}.${field}`];
// };

const onChargeSelected = (rowItem) => {
  const selected = chargeApplicationOptions.value.find(
    (opt) => opt.label === rowItem.charges
  );

  if (selected) {
    rowItem.value = selected.amount;
    rowItem.type =
      selected.charges_type === "Percent" ? "Percentage (%)" : "Flat";
  }
};

// Recompute charges/taxes for current product (non-variant) context
function recomputeProductChargesAndTaxes() {
  const base = selectedProductTypeLabel.value === 'Ready-made'
    ? parseFloat(form.unit_price) || 0
    : parseFloat(form.base_price) || 0;

  const bases = {
    base,
    metalBase: metalBasePrice.value || 0,
    gemstoneBase: gemstoneBasePrice.value || 0,
  };

  const chargeMap = {};
  // Charges
  productCharges.value.forEach((charge) => {
    if (typeof charge.primary_cost === 'object' && charge.primary_cost?.label) {
      charge.primary_cost = charge.primary_cost.label;
    }
    const amt = computeChargeAmount(charge, bases);
    if (charge.charges) chargeMap[charge.charges] = amt;
  });

  // Taxes
  taxCharges.value.forEach((tax) => {
    tax.primary_cost = normalizePrimaryCost(tax.primary_cost);
    computeTaxAmount(tax, bases, chargeMap);
  });
}

const generateBasePrice = () => {


  const weight = parseFloat(form.metal_weight);
  if (!form.material_type_id || !form.purity_karat_id || isNaN(weight)) {
    metalBasePrice.value = 0;
    updateTotalBasePrice();
    return;
  }

  axiosEmployee
    .get("/generateBasePrice", {
      params: {
        material_type_id: form.material_type_id,
        purity_karat_id: form.purity_karat_id
      },
    })
    .then((response) => {
      const fetchedPrice = parseFloat(response.data.data);
      metalBasePrice.value = !isNaN(fetchedPrice) ? fetchedPrice * weight : 0;
      if (selectedProductTypeLabel.value === "Ready-made") {
        return;
      }
      updateTotalBasePrice();
    })
    .catch(() => {
      metalBasePrice.value = 0;
      updateTotalBasePrice();
    });
};

const recalculateBasePrice = (index) => {
  const item = AttributeVariantOptions.value[index];
  const metalWeight = parseFloat(item.metal_weight);
  const gemstoneWeight = parseFloat(item.gemstone_weight);

  let metalBase = 0;
  let gemstoneBase = 0;



  const metalPromise =
    form.material_type_id && form.purity_karat_id && !isNaN(metalWeight)
      ? axiosEmployee
        .get("/generateBasePrice", {
          params: {
            material_type_id: form.material_type_id,
            purity_karat_id: form.purity_karat_id
          },
        })
        .then((res) => {
          const pricePerGram = parseFloat(res.data.data);

          metalBase = !isNaN(pricePerGram) ? pricePerGram * metalWeight : 0;
        })
      : Promise.resolve();

  const gemstonePromise =
    form.gemstone_type_id && form.stone_color_id && form.stone_shape_id &&
      form.stone_clarity_id && form.stone_cut_id &&
      !isNaN(gemstoneWeight)
      ? axiosEmployee
        .get("/generateGemstonePrice", {
          params: {
            gemstone_type_id: form.gemstone_type_id,
            stone_color_id: form.stone_color_id,
            stone_shape_id: form.stone_shape_id,
            stone_clarity_id: form.stone_clarity_id,
            stone_cut_id: form.stone_cut_id

          },
        })
        .then((res) => {
          const pricePerCarat = parseFloat(res.data.data);
          gemstoneBase = !isNaN(pricePerCarat)
            ? pricePerCarat * gemstoneWeight
            : 0;
        })
      : Promise.resolve();

  Promise.all([metalPromise, gemstonePromise]).then(() => {
    const total = metalBase + gemstoneBase;
    item.base_price = total.toFixed(2);
    item.metal_base = metalBase;
    item.gemstone_base = gemstoneBase;
  });
};

const generateGemstonePrice = () => {
  if (selectedProductTypeLabel.value === "Ready-made") {
    gemstoneBasePrice.value = 0;
    updateTotalBasePrice();
    return;
  }

  const weight = parseFloat(form.gemstone_weight);
  if (!form.gemstone_type_id || !form.stone_color_id || !form.stone_shape_id ||
    !form.stone_clarity_id || !form.stone_cut_id || isNaN(weight)) {
    gemstoneBasePrice.value = 0;
    updateTotalBasePrice();
    return;
  }

  axiosEmployee
    .get("/generateGemstonePrice", {
      params: {
        gemstone_type_id: form.gemstone_type_id,
        stone_color_id: form.stone_color_id,
        stone_shape_id: form.stone_shape_id,
        stone_clarity_id: form.stone_clarity_id,
        stone_cut_id: form.stone_cut_id,
      },
    })
    .then((response) => {
      const fetchedPrice = parseFloat(response.data.data);
      gemstoneBasePrice.value = !isNaN(fetchedPrice)
        ? fetchedPrice * weight
        : 0;
      updateTotalBasePrice();
    })
    .catch(() => {
      gemstoneBasePrice.value = 0;
      updateTotalBasePrice();
    });
};

const updateTotalBasePrice = () => {
  const metalWeight = parseFloat(form.metal_weight);
  const gemstoneWeight = parseFloat(form.gemstone_weight);

  // If both weights are empty or invalid, clear base_price
  if (isNaN(metalWeight) && isNaN(gemstoneWeight)) {
    form.base_price = "";
    return;
  }

  const total = metalBasePrice.value + gemstoneBasePrice.value;
  form.base_price = total.toFixed(2);
};

function onCategoryChange(value) {
  form.category_id = value;
  form.sub_category_id = null; // Reset subcategory

  if (value) {
    axiosEmployee.get(`/SubCategoryOptions/${value}`).then((response) => {
      SubCategoryOptions.value = response.data;
    });
  } else {
    SubCategoryOptions.value = [];
  }
}


async function onSubVariantChange(value) {
  form.variant_attribute_id = value;

  const ids = Array.isArray(value) ? value.map(v => v.value) : [value.value];

  if (!ids.length) {
    VariantOptions.value = [];
    return;
  }

  const res = await axiosEmployee.get(`/VariantOptions`, {
    params: { ids: ids.join(","), sub_category_id: form.sub_category_id },
  });

  const newOptions = res.data;
  const allOptions = [...VariantOptions.value, ...newOptions];

  // Deduplicate by attribute+label
  VariantOptions.value = allOptions.filter(
    (v, i, arr) =>
      arr.findIndex(o => o.attribute_id === v.attribute_id && o.label === v.label) === i
  );

  if (!Array.isArray(form.variant_options_id)) {
    form.variant_options_id = [];
  }
}
function generateCombinations(selectedOptions) {
  // Group options by attribute
  const grouped = selectedOptions.reduce((acc, opt) => {
    acc[opt.attribute_id] = acc[opt.attribute_id] || [];
    acc[opt.attribute_id].push(opt);
    return acc;
  }, {});

  const groups = Object.values(grouped);

  // Recursive function to generate cartesian product
  function cartesian(arr) {
    return arr.reduce((a, b) =>
      a.flatMap(d => b.map(e => [...d, e])),
      [[]]
    );
  }

  return cartesian(groups);
}
function getvarientcombinations() {
  RemoveError("variants");
  const isEdit = props.isEditing && props.productId;

  // ✅ Step 1: Load existing variants
  const existingChildren =
    (form.children_products || []).map((child, index) => ({
      id: child.id,
      variant_name: child.name || child.variant_name,
      metal_weight: parseFloat(child.metal_weight) || 0,
      gemstone_weight: parseFloat(child.gemstone_weight) || 0,
      base_price: parseFloat(child.base_price) || 0,
      sku: child.sku || `${form.sku}-${index + 1}`,
      sub_category_label:
        SubCategoryOptions.value.find((opt) => opt.value === child.sub_category_id)?.label || "",
      metal_base: parseFloat(child.metal_weight) || 0,
      gemstone_base: parseFloat(child.gemstone_weight) || 0,
      status: child.status || "A",
      category_id: child.category_id,
      sub_category_id: child.sub_category_id,
      attributes: Array.isArray(child.attributes)
        ? child.attributes
        : JSON.parse(child.variants || "[]").map((v) => ({
          attribute_id: v.attribute_id,
          attribute_value: v.attribute_value,
        })),
      primary_image: child.main_image || null,
      total_stock: child.total_stock || 0,
      low_stock: child.low_stock || 0,
      weight: parseFloat(child.weight) || 0,
      unit: child.unit || "cm",
      length: parseFloat(child.length) || 0,
      width: parseFloat(child.width) || 0,
      height: parseFloat(child.height) || 0,
      is_new: false,
    })) || [];

  const variantOptions = Array.isArray(form.variant_options_id)
    ? form.variant_options_id
    : [];

  // ✅ Step 2: No variant options → just show existing variants
  if (variantOptions.length === 0) {
    AttributeVariantOptions.value = [...existingChildren];
    return;
  }

  // ✅ Step 3: Generate all variant combinations
  const combos = generateCombinations(variantOptions);

  const parentProductName = form.product_name;
  const subCategoryLabel =
    SubCategoryOptions.value.find((opt) => opt.value === form.sub_category_id)?.label || "";

  // ✅ Step 4: Create new variants with SKU suffix (-1, -2, ...)
  const newVariants = combos.map((combo, index) => {
    const attributes = combo.map((o) => ({
      attribute_id: o.attribute_id,
      attribute_value: o.label,
    }));

    const variant_name = `${parentProductName} - ${subCategoryLabel} - ${combo
      .map((o) => o.label)
      .join(" - ")}`;

    const metalBase = parseFloat(form.metal_weight) || 0;
    const gemstoneBase = parseFloat(form.gemstone_weight) || 0;

    // ✅ SKU = parentSKU + "-" + (index + 1)
    const newSku = `${form.sku}-${index + 1}`;

    return {
      id: Date.now() + Math.random(),
      variant_name,
      metal_weight: metalBase,
      gemstone_weight: gemstoneBase,
      base_price: (metalBase + gemstoneBase).toFixed(2),
      sku: newSku,
      sub_category_label: subCategoryLabel,
      metal_base: metalBase,
      gemstone_base: gemstoneBase,
      status: "A",
      category_id: form.category_id,
      sub_category_id: form.sub_category_id,
      attributes,
      primary_image: null,
      total_stock: 0,
      low_stock: 0,
      weight: form.weight || 0,
      unit: form.unit || "cm",
      length: form.length || 0,
      width: form.width || 0,
      height: form.height || 0,
      is_new: true,
    };
  });

  // ✅ Step 5: Keep valid existing variants
  const validExisting = existingChildren.filter((child) => {
    const childAttrValues = child.attributes.map((a) => a.attribute_value).sort().join("-");
    return combos.some((combo) => {
      const comboAttrValues = combo.map((o) => o.label).sort().join("-");
      return childAttrValues === comboAttrValues;
    });
  });

  // ✅ Step 6: Avoid duplicates (same attributes)
  const filteredNewVariants = newVariants.filter((v) => {
    return !validExisting.some((child) => {
      const childAttrValues = child.attributes.map((a) => a.attribute_value).sort().join("-");
      const newAttrValues = v.attributes.map((a) => a.attribute_value).sort().join("-");
      return childAttrValues === newAttrValues;
    });
  });

  // ✅ Step 7: Merge both
  const merged = [...validExisting, ...filteredNewVariants];

  // ✅ Step 8: Ensure consistent SKU numbering even in edit mode
  merged.forEach((variant, index) => {
    variant.sku = `${form.sku}-${index + 1}`;
  });

  // ✅ Step 9: Update reactive list
  AttributeVariantOptions.value = merged;
}







function onMetalTypeBasedPurityChange(value) {
  form.material_type_id = value;
  form.purity_karat_id = null; // Reset subcategory

  if (value) {
    axiosEmployee.get(`/PurityOptions/${value}`).then((response) => {
      PurityOptions.value = response.data;
    });
  } else {
    PurityOptions.value = [];
  }
}

async function submitForm() {

  const formData = new FormData();

  if (form.variant_options_id === "null" || form.variant_options_id === "") {
    form.variant_options_id = null;
  }

  formData.append("product_name", form.product_name);
  formData.append("video_url", form.video_url);
  formData.append("description", form.description);
  formData.append("category_id", form.category_id);
  formData.append("sub_category_id", form.sub_category_id);

  const variantOptionIds = Array.isArray(form.variant_options_id)
    ? form.variant_options_id
    : [];

  // const labels = form.variant_options_id.map(item => item.label)
  if (form.variant_options_id && form.variant_options_id.length > 0) {
    const variants = JSON.parse(JSON.stringify(form.variant_options_id));
    formData.append("variant_options_id", JSON.stringify(variants));
  }

  AttributeVariantOptions.value.forEach((item, index) => {


    // Try to find updated values from filteredFinalRows
    const updated = filteredFinalRows.value.find((row) => row.sku === item.sku);

    // Use updated values if available, else fallback to original
    const finalItem = {
      ...item,
      additional_charges: updated?.additional_charges ?? 0,
      taxes: updated?.taxes ?? 0,
      product_price: updated?.product_price ?? updated?.final_price ?? 0,
    };

    // Append to formData
    formData.append(`variant_options[${index}][id]`, finalItem.id);

    formData.append(
      `variant_options[${index}][variant_name]`,
      finalItem.variant_name
    );
    formData.append(
      `variant_options[${index}][attributes]`,
      JSON.stringify(finalItem.attributes)
    );
    formData.append(`variant_options[${index}][size]`, finalItem.size);
    formData.append(
      `variant_options[${index}][metal_weight]`,
      finalItem.metal_weight
    );
    formData.append(
      `variant_options[${index}][gemstone_weight]`,
      finalItem.gemstone_weight
    );
    formData.append(`variant_options[${index}][sku]`, finalItem.sku);
    formData.append(
      `variant_options[${index}][base_price]`,
      finalItem.base_price
    );
    formData.append(`variant_options[${index}][status]`, finalItem.status);
    formData.append(
      `variant_options[${index}][category_id]`,
      finalItem.category_id
    );
    formData.append(
      `variant_options[${index}][sub_category_id]`,
      finalItem.sub_category_id
    );
    formData.append(
      `variant_options[${index}][additional_charges]`,
      finalItem.additional_charges
    );

    const rawVariantTaxes = toRaw(varianttaxes.value);
    const currentVariantTaxes = rawVariantTaxes[finalItem.id] || [];

    formData.append(
      `variant_options[${index}][variant_taxes]`,
      JSON.stringify(currentVariantTaxes)
    );


    Object.entries(variantcharges.value).forEach(([variantId, charges], index) => {
      if (!Array.isArray(charges)) return; // skip if not array

      charges.forEach((charge) => {
        if (charge.label === "Additional Charges") {
          formData.append(
            `variant_options[${index}][additional_charges]`,
            charge.amount
          );
        }
        if (charge.label === "Making Charges") {
          formData.append(
            `variant_options[${index}][making_charges]`,
            charge.amount
          );
        }
      });
    });
    formData.append(`variant_options[${index}][taxes]`, finalItem.taxes);
    formData.append(
      `variant_options[${index}][product_price]`,
      finalItem.product_price
    );
  });




  productCharges.value.forEach((charge, index) => {
    const chargeName = (charge.charges ?? "").trim().toLowerCase();

    if (chargeName === "additional charges") {
      formData.append("additional_charges", charge.calculated_amount ?? 0);
    } else if (chargeName === "making charges") {
      formData.append("making_charges", charge.calculated_amount ?? 0);
    }

    formData.append(`charges[${index}][charges]`, charge.charges ?? "");
    formData.append(`charges[${index}][type]`, charge.type ?? "");
    formData.append(`charges[${index}][value]`, charge.value ?? 0);
    formData.append(`charges[${index}][primary_cost]`, charge.primary_cost ?? "");
    formData.append(`charges[${index}][description]`, charge.description ?? "");
  });


  taxCharges.value.forEach((tax, index) => {
    formData.append(`taxes[${index}][tax_application]`, tax.tax_application);
    formData.append(`taxes[${index}][type]`, tax.type);
    formData.append(`taxes[${index}][value]`, tax.value);
    formData.append(`taxes[${index}][primary_cost]`, tax.primary_cost);
    formData.append(`taxes[${index}][amount]`, tax.calculated_amount);//
    formData.append(`taxes[${index}][description]`, tax.description);
  });

  filteredFinalRows.value.forEach((variantss, variantIndex) => {
    variantss.row_taxes.forEach((tax, taxIndex) => {
      formData.append(`variants_tax[${variantIndex}][taxes][${taxIndex}][amount]`, tax.calculated_amount);
    });

  });






  formData.append("product_type_id", form.product_type_id);
  // Extract tag labels, deduplicate, and filter out empty values
  const tagLabels = Array.isArray(form.tags_id)
    ? form.tags_id.map(tag => typeof tag === 'object' ? tag.label : tag)
      .map(label => String(label).trim())
      .filter(Boolean)
    : [];
  // Remove duplicates using Set
  const uniqueTagLabels = [...new Set(tagLabels)];
  formData.append("tags_id", JSON.stringify(uniqueTagLabels));

  formData.append("material_type_id", form.material_type_id);
  formData.append("purity_karat_id", form.purity_karat_id);

  formData.append("metal_weight", form.metal_weight);
  formData.append("weight", form.weight);
  formData.append("base_price", form.base_price);
  formData.append("unit_price", form.unit_price);

  formData.append("final_price", form.final_price);
  formData.append("discount", form.discount);
  formData.append("status", form.status);
  formData.append("variants", form.variants_mode);

  formData.append("dimensions", form.dimensions);
  formData.append("shipping_charges", form.shipping_charges);

  formData.append("unit", form.unit);
  formData.append("length", form.length);
  formData.append("width", form.width);
  formData.append("height", form.height);

  formData.append("total_stock", form.total_stock);
  formData.append("seo_title", form.seo_title);
  formData.append("meta_description", form.meta_description);
  formData.append("meta_slug_url", form.meta_slug_url);

  formData.append("visible_to", form.visible_to);
  if (Array.isArray(form.visible_partner_ids)) {
    form.visible_partner_ids.forEach(id => {
      formData.append("visible_partner_ids[]", id);
    });
  }

  formData.append("low_stock", form.low_stock);
  formData.append("track_inventory", form.track_inventory ? 1 : 0);
  formData.append("free_shipping", form.free_shipping ? "yes" : "no");
  formData.append("sku", form.sku);

  formData.append("wastage_charges", form.wastage_charges);
  formData.append('is_featured', form.is_featured ? 1 : 0);

  let variantIds = Array.isArray(form.variant_attribute_id)
    ? form.variant_attribute_id
    : [];

  variantIds.forEach((attr, index) => {
    formData.append(`variant_attribute_id[${index}]`, attr.value);
  });


  // Add removal flag if needed
  if (form.remove_main_image) {
    formData.append('remove_main_image', '1');
  }

  // Add new image if exists
  if (form.main_image) {
    formData.append('main_image', form.main_image);
  }

  AttributeVariantOptions.value.forEach((variant, index) => {

    // Primary image
    if (variant.primary_image_file) {
      formData.append(
        `variants_product[${index}][primary_image]`,
        variant.primary_image_file
      );
    } else {
      formData.append(
        `variants_product[${index}][primary_image]`,
        variant.primary_image
      );
    }



    // Additional images
    if (variant.additional_image_files) {
      variant.additional_image_files.forEach((file, imgIndex) => {
        formData.append(
          `variants_product[${index}][additional_images][${imgIndex}]`,
          file
        );
      });
    }

    if (variant.video_files) {
      variant.video_files.forEach((file, videoIndex) => {
        formData.append(
          `variants_product[${index}][videos][${videoIndex}]`,
          file
        );
      });
    }
  });

  const allMedia = [...imagePreviews.value, ...videoPreviews.value];

  // Add new files to upload
  allMedia.forEach((media, index) => {
    if (media.isNew) {
      formData.append(`media[${index}]`, media.file);
      formData.append(`media_types[${index}]`, media.type);
    }
  });

  // Add existing media to keep
  form.existing_media.forEach((media, index) => {
    if (!form.deleted_media.includes(media.id)) {
      formData.append(`existing_media[${index}]`, media.id);
    }
  });

  // Add deleted media IDs
  form.deleted_media.forEach((id, index) => {
    formData.append(`deleted_media[${index}]`, id);
  });

  imagePreviews.value.forEach((image, index) => {
    if (image.isNew) {
      formData.append(`images[${index}]`, image.file);
    }
  });



  // For videos (single upload)
  if (videoPreviews.value.length > 0 && videoPreviews.value[0].isNew) {
    formData.append('videos[0]', videoPreviews.value[0].file);
  }

  AttributeVariantOptions.value.forEach((item, index) => {
    // ... existing variant data ...
    // Add inventory fields for configurable products
    if (selectedProductTypeLabel.value === "Configurable") {
      formData.append(
        `variant_options[${index}][total_stock]`,
        item.total_stock || 0
      );
      formData.append(
        `variant_options[${index}][low_stock]`,
        item.low_stock || 0
      );
      formData.append(
        `variant_options[${index}][track_inventory]`,
        item.track_inventory ? 1 : 0
      );

      // Add shipping fields for configurable products
      formData.append(`variant_options[${index}][weight]`, item.weight || 0);
      formData.append(`variant_options[${index}][unit]`, item.unit || "cm");
      formData.append(`variant_options[${index}][length]`, item.length || 0);
      formData.append(`variant_options[${index}][width]`, item.width || 0);
      formData.append(`variant_options[${index}][height]`, item.height || 0);
      formData.append(
        `variant_options[${index}][shipping_charges]`,
        item.shipping_charges || 0
      );
      formData.append(
        `variant_options[${index}][free_shipping]`,
        item.free_shipping ? 1 : 0
      );
    }
  });

  if (selectedProductTypeLabel.value === "Configurable") {
    AttributeVariantOptions.value.forEach((variant, index) => {
      formData.append(
        `variant_options[${index}][seo_title]`,
        variant.seo_title || ""
      );
      formData.append(
        `variant_options[${index}][meta_description]`,
        variant.meta_description || ""
      );
      formData.append(
        `variant_options[${index}][meta_slug_url]`,
        variant.meta_slug_url || ""
      );
    });
  }

  const relatedIds = relatedProducts.value.map((p) => p.id);
  const youMayLikeIds = youMayLikeProducts.value.map((p) => p.id);

  formData.append("related_product_ids", JSON.stringify(relatedIds));
  formData.append("you_may_like_product_ids", JSON.stringify(youMayLikeIds));


  try {
    if (props.isEditing && props.productId) {
      formData.append("_method", "PUT");
      await axiosEmployee.post(`/products/${props.productId}`, formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      setTimeout(() => {
        toast("Product Updated successfully!", {
          type: "success",
          autoClose: 1000,
        });
      }, 300);
    } else {
      await axiosEmployee.post("/products", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },

      });
      setTimeout(() => {
        toast("Product added successfully!", {
          type: "success",
          autoClose: 1000,
        });
      }, 300);
    }
    emit("submitted");
  } catch (error) {
    if (error.response.data.code == 422) {
      errors.value = error.response.data.errors;
    }
  }
}

const statusOptions = ref([
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'draft', label: 'Draft' }
])

const validateWeight = (data, field) => {
  const weight = parseFloat(data.item[field]);

  if (isNaN(weight) || weight <= 0) {
    data.item[`${field}_error`] = "Kindly enter proper weight, e.g., 0.25";
    data.item.base_price = ""; // clear base price
  } else {
    data.item[`${field}_error`] = "";
    recalculateBasePrice(data.index); // only recalc when valid
  }
};



const validateCurrentTab = async (tabIndex) => {
  try {
    const formData = new FormData();

    switch (tabIndex) {
      case 0: // Basic Details tab
        formData.append("id", form.id || "");
        formData.append("product_name", form.product_name || "");
        formData.append("category_id", form.category_id || "");
        formData.append("sub_category_id", form.sub_category_id || "");
        formData.append("product_type_id", form.product_type_id || "");
        formData.append("tags_id", form.tags_id || "");
        formData.append("status", form.status || "");
        formData.append("sku", form.sku || "");
        formData.append("visible_to", form.visible_to);
        let partnerIds = []
        if (Array.isArray(form.visible_partner_ids)) {
          partnerIds = form.visible_partner_ids
        } else if (typeof form.visible_partner_ids === 'string') {
          try {
            partnerIds = JSON.parse(form.visible_partner_ids)
          } catch {
            partnerIds = []
          }
        }

        if (form.visible_to == 'partner' || form.visible_to == 'both') {
          partnerIds.forEach((id, index) => {
            formData.append(`visible_partner_ids[${index}]`, id)
          })
        }

        if (form.main_image) {
          formData.append("main_image", form.main_image);
        }
        // main multiple image and single video

        imagePreviews.value.forEach((image, index) => {
          if (image.isNew) {
            formData.append(`images[${index}]`, image.file);
          }
        });

        // For videos (single upload)
        if (videoPreviews.value.length > 0 && videoPreviews.value[0].isNew) {
          formData.append('videos[0]', videoPreviews.value[0].file);
        }

        break;

      case 1: // Product Details tab
        formData.append("material_type_id", form.material_type_id || "");
        formData.append("purity_karat_id", form.purity_karat_id || "");

        break;

      case 2: // Variant & Pricing tab
        if (
          form.variants_mode === "yes" &&
          AttributeVariantOptions.value.length === 0
        ) {
          errors.value = {
            variants: ["Please configure at least one variant"],
          };
          return false;
        }
        const invalidWeights = [];

        AttributeVariantOptions.value.forEach((variant) => {
          const metal = parseFloat(variant.metal_weight);
          const gem = parseFloat(variant.gemstone_weight);

          // Clear old errors
          variant.metal_weight_error = "";
          variant.gemstone_weight_error = "";

          // Metal validation
          if (isNaN(metal) || metal <= 0) {
            variant.metal_weight_error = "Kindly enter proper metal weight, e.g., 0.25";
            invalidWeights.push(variant);
          }

        });

        if (invalidWeights.length > 0) {
          errors.value = {
            weight: ["Please correct invalid weights before proceeding"],
          };
          return false;
        }
        if (form.variants_mode) {
          formData.append("variants_mode", form.variants_mode);
        }
        if (props.isEditing) {
          formData.append("isEditing", props.isEditing);
        }

        // Append tax charges data
        taxCharges.value.forEach((tax, index) => {
          formData.append(`taxes[${index}][tax_application]`, tax.tax_application || "");
          formData.append(`taxes[${index}][type]`, tax.type || "");
          formData.append(`taxes[${index}][value]`, tax.value || "");
          formData.append(`taxes[${index}][primary_cost]`, tax.primary_cost || "");
          formData.append(`taxes[${index}][description]`, tax.description || "");
          formData.append(`taxes[${index}][calculated_amount]`, tax.calculated_amount);
        });///
        AttributeVariantOptions.value.forEach((variant, index) => {
          // Primary image
          if (variant.primary_image_file) {
            formData.append(
              `variants_product[${index}][primary_image]`,
              variant.primary_image_file
            );
          } else {
            formData.append(
              `variants_product[${index}][primary_image]`,
              variant.primary_image
            );
          }
          // Additional images
          if (variant.additional_image_files) {
            variant.additional_image_files.forEach((file, imgIndex) => {
              formData.append(
                `variants_product[${index}][additional_images][${imgIndex}]`,
                file
              );
            });
          }

          if (variant.video_files) {
            variant.video_files.forEach((file, videoIndex) => {
              formData.append(
                `variants_product[${index}][videos][${videoIndex}]`,
                file
              );
            });
          }
        });

        // If needed, append more fields here
        break;

      case 3: // Inventory & Shipping tab
        // Frontend guard: block if low > total (non-configurable)
        if (selectedProductTypeLabel.value !== 'Configurable') {
          // REQUIRED validation (before Number conversion)
          if (form.total_stock === '' || form.total_stock === null) {
            
            errors.value = {
              total_stock: ["Total Stock is required"],
            };
            errors.value["total_stock"] = ["Total Stock is required"];
            return false;
          }

          const total = Number(form.total_stock);
          const low = Number(form.low_stock);

          // Optional: if zero is NOT allowed
          if (total <= 0) {
            errors.value = {
              total_stock: ["Total Stock must be greater than 0"],
            };

            return false;
          }

          if (!isNaN(total) && !isNaN(low) && low > total) {
            errors.value = {
              total_stock: ["Low stock cannot be greater than total stock."],
            };

            return false;
          }
        } else {
          // For configurable, check each variant
          let hasError = false;
          AttributeVariantOptions.value.forEach((v, idx) => {
            const total = Number(v.total_stock);
            const low = Number(v.low_stock);
            const key = `variant_options.${idx}.low_stock`;
            if (!isNaN(total) && !isNaN(low) && low > total) {
              errors.value[key] = ["Low stock cannot be greater than total stock for this variant."];
              hasError = true;
            } else if (errors.value[key]) {
              delete errors.value[key];
            }
          });
          if (hasError) return false;
        }
        break;

      // Add other tab logic as needed
    }

    // Send using FormData
    const response = await axiosEmployee.post(
      `/products/validate-tab/${tabIndex}`,
      formData,
      {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      }
    );

    return true;
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
      // Handle tax charge specific errors
      if (errors.value.taxes) {
        taxCharges.value.forEach((_, index) => {
          if (errors.value[`taxes.${index}.tax_application`]) {
            errors.value[`taxes.${index}.tax_application`] =
              errors.value[`taxes.${index}.tax_application`];
          }
          // Add similar handling for other tax fields
        });
      }

    }
    return false;
  }
};

// Modify your onTabChange method
const onTabChange = async (tabName) => {
  // Get current tab index based on tabName or other logic
  const currentTabIndex = getCurrentTabIndex(tabName); // You'll need to implement this
  
  const isValid = await validateCurrentTab(currentTabIndex);

  const container = document.querySelector(".customFormWizard"); // your scroll area
  if (container) {
    container.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  } else {
    window.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  }
  return isValid;

};


const generateSku = () => {
  if (!form.product_name) {
    form.sku = "";
    return;
  }

  // Convert product name to uppercase, replace spaces with dashes
  let baseSku = form.product_name
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-"); // keep alphanumeric + dashes

  // Optionally add random/unique suffix

  form.sku = `${baseSku}`;
}
// Helper function to get tab index
const getCurrentTabIndex = (tabName) => {
  const tabs = [
    "Basic Details",
    "Product Details",
    "Variant & Pricing",
    "Inventory & Shipping",
    "SEO,Metadata & Notification",
    "Additional Products",
  ];
  return tabs.indexOf(tabName);
};

// tab validation end

const removeTaxError = (index, field) => {
  const errorKey = `taxes.${index}.${field}`;
  if (errors.value[errorKey]) {
    delete errors.value[errorKey];
  }
};

const RemoveError = (errorName) => {
  errors.value[errorName] = " ";
};

const errors = ref({});

const hasErrors = (field) => {
  return (
    errors.value && Object.prototype.hasOwnProperty.call(errors.value, field)
  );
};

const getErrors = (field) => {
  return hasErrors(field) ? errors.value[field][0] : "";
};

// If using variant index as key like `variants_product.0.primary_image`
const primaryImageErrorKey = computed(() => {
  if (currentVariant.value?.id != null) {
    const variantIndex = AttributeVariantOptions.value.findIndex(
      (v) => v.id === currentVariant.value.id
    );
    return `variants_product.${variantIndex}.primary_image`;
  }
  return "";
});

// Inventory validations
function validateMainStock() {
  const total = Number(form.total_stock);
  const low = Number(form.low_stock);
  if (!isNaN(total) && !isNaN(low) && low > total) {
    errors.value["low_stock"] = ["Low stock cannot be greater than total stock."];
  } else {
    if (errors.value["low_stock"]) delete errors.value["low_stock"];
  }
}

function validateVariantStock(index) {
  const v = AttributeVariantOptions.value[index];
  if (!v) return;
  const total = Number(v.total_stock);
  const low = Number(v.low_stock);
  const key = `variant_options.${index}.low_stock`;
  if (!isNaN(total) && !isNaN(low) && low > total) {
    errors.value[key] = ["Low stock cannot be greater than total stock for this variant."];
  } else {
    if (errors.value[key]) delete errors.value[key];
  }
}

// ===== Auto-select Tags/Labels based on chosen fields =====
function getOptionLabel(options, id) {
  const found = (options || []).find(o => o && (o.value === id));
  return found ? String(found.label).trim() : null;
}

function isTagSelectedByLabel(label) {
  if (!label) return false;
  const current = Array.isArray(form.tags_id) ? form.tags_id : [];
  return current.some(t => (typeof t === 'object' ? String(t.label).trim() : String(t).trim()) === String(label).trim());
}
// Create a lookup to store sources (not inside tag object)
const tagSources = ref({});

function autoSelectTagByLabel(label, source = '') {
  if (!label) return;
  if (!Array.isArray(form.tags_id)) form.tags_id = [];

  // Remove old auto-added tag from same source
  form.tags_id = form.tags_id.filter(
    t => tagSources.value[t.value] !== source
  );

  // Find the actual tag option (original reference from TagOptions)
  const opt = (TagOptions.value || []).find(
    o => String(o.label).trim() === String(label).trim()
  );

  if (opt) {
    const exists = form.tags_id.some(t => String(t.value) === String(opt.value));
    if (!exists) {
      form.tags_id.push(opt); // ✅ push same reference
      tagSources.value[opt.value] = source; // remember where it came from
    }
  }
}






function resetForm() {
  form.id = null;
  form.product_name = "";
  form.main_image = null;
  form.description = "";
  form.category_id = "";
  form.sub_category_id = "";
  form.variant_options_id = "";
  form.product_type_id = "";
  form.tags_id = [];
  form.material_type_id = "";
  form.purity_karat_id = "";
  form.gemstone_type_id = "";
  form.stone_color_id = "";
  form.stone_treatment_id = "";
  form.stone_shape_id = "";
  form.stone_clarity_id = "";
  form.stone_cut_id = "";
  form.variant_attribute_id = [];
  form.variant_options_id = "";
  form.gemstone_weight = "";
  form.metal_weight = "";
  form.dimensions = "";
  form.weight = "";
  form.status = "";
  form.base_price = "";
  form.unit_price = "";
  form.final_price = "";
  form.discount = "";
  form.variants = "";
  form.variants_mode = "no";
  form.additional_charges = false;
  form.shipping_charges = "";
  form.unit = "cm";
  form.length = "";
  form.width = "";
  form.height = "";
  form.total_stock = null;
  form.seo_title = null;
  form.meta_description = "";
  form.meta_slug_url = "";
  form.low_stock = "";
  form.track_inventory = false;
  form.free_shipping = true;
  form.making_charges = "";
  form.wastage_charges = "";
  form.children_products = [];
  // generateNewSku();

  form.images = [];
  form.videos = [];
  form.existing_media = [];
  form.deleted_media = [];

  imagePreviews.value = [];
  videoPreviews.value = [];

  if (fileInput.value) fileInput.value.value = "";
  if (primaryFileInput.value) primaryFileInput.value.value = "";
  if (mainVideoInput.value) mainVideoInput.value.value = "";

  primaryImagePreview.value = null;
}

// Add these computed properties
const isMaterialTypeNA = computed(() => {
  return form.material_type_id === getNAOptionValue(MaterialTypeOptions.value);
});



const filteredMaterialTypeOptions = computed(() => {
  return MaterialTypeOptions.value;
});


// Helper function to get the NA option value
const getNAOptionValue = (options) => {
  const naOption = options.find(option => option.label === 'NA');
  return naOption ? naOption.value : null;
};

// Add these methods
const handleMaterialTypeChange = (value) => {
  const isNA = value === getNAOptionValue(MaterialTypeOptions.value);

  if (isNA) {
    watch(PurityOptions, (newVal) => {
      if (newVal.length > 0 && form.material_type_id === getNAOptionValue(MaterialTypeOptions.value)) {
        const zeroPurity = newVal.find(opt => String(opt.label).trim() === "0");
        form.purity_karat_id = zeroPurity ? zeroPurity.value : null;
      }
    });
    form.metal_weight = "0";
  };
}

async function fetchProductData() {
  if (!props.isEditing || !props.productId) return;

  try {
    // Load initial options
    shouldCalculatePrice.value = false;
    await axiosEmployee.get("/CategoryOptions").then((response) => {
      CategoryOptions.value = response.data;
    });

    await fetchRelatedProducts();
    // Fetch product data
    const response = await axiosEmployee.get(`/products/${encodeBase64(props.productId)}/edit`);
    const item = response.data.data;

    // Set basic form fields
    form.id = item.id;
    form.product_name = item.name;
    form.description = item.description;
    form.main_image = item.main_image;
    primaryImagePreview.value = null;
    form.category_id = item.category_id;
    form.sub_category_id = item.sub_category_id;
    form.product_type_id = item.product_type_id;

    const parsedTags = JSON.parse(item.tags_id || '[]');
    if (Array.isArray(parsedTags)) {
      // Remove duplicates by converting to Set and back to array
      const uniqueTagLabels = [...new Set(parsedTags.map(tag => String(tag).trim()).filter(Boolean))];
      // Map to TagOptions objects
      form.tags_id = uniqueTagLabels.map(label => {
        const tagOption = TagOptions.value.find(opt => String(opt.label).trim() === label);
        return tagOption || { value: null, label: label };
      }).filter(tag => tag.value !== null); // Only keep tags that exist in TagOptions
    } else {
      form.tags_id = [];
    }

    form.material_type_id = item.material_type_id;
    form.purity_karat_id = item.purity_karat_id;

    form.video_url = (!item.video_url || item.video_url === "null") ? "" : item.video_url;

    form.metal_weight = item.metal_weight || "";
    form.weight = item.weight;
    form.base_price = item.base_price || "";
    form.unit_price = item.unit_price || "";
    form.final_price = item.final_price;
    form.discount = item.discount;
    form.status = item.status;
    form.variants = item.variants;
    form.variant_options = item.variant_options;

    if (item.variant_options.length > 0) {
      form.variants_mode = 'yes';
    }
    form.making_charges = item.making_charges;
    form.wastage_charges = item.wastage_charges;
    form.additional_charges = item.additional_charges == 1;
    form.dimensions = item.dimensions;
    form.shipping_charges = item.shipping_charges || "";
    form.unit = item.unit;
    form.length = item.length;
    form.width = item.width;
    form.height = item.height;
    form.total_stock = item.total_stock;
    form.seo_title = item.seo_title || "";
    form.meta_description = item.meta_description || "";
    form.meta_slug_url = item.meta_slug_url || "";

    form.low_stock = item.low_stock;
    form.track_inventory = item.track_inventory === 1; // Or simply: !!item.track_inventory
    form.free_shipping = item.free_shipping === "yes";
    form.sku = item.sku;


    form.is_featured = item.is_featured === 1;
    form.existing_main_image = item.main_image;
    form.visible_to = item.visible_to;
    if (Array.isArray(item.visible_partner_ids)) {
      form.visible_partner_ids = item.visible_partner_ids
    } else if (typeof item.visible_partner_ids === 'string') {
      try {
        form.visible_partner_ids = JSON.parse(item.visible_partner_ids)
      } catch {
        form.visible_partner_ids = []
      }
    } else {
      form.visible_partner_ids = []
    }
    form.visible_partner_ids = (item.visible_partner_ids || []).map(id => Number(id));





    // --- Handle Related Products ---
    let relatedIds = [];

    try {
      if (Array.isArray(item.related_product_ids)) {
        relatedIds = item.related_product_ids;
      } else if (typeof item.related_product_ids === "string") {
        const parsedOnce = JSON.parse(item.related_product_ids);
        relatedIds =
          typeof parsedOnce === "string" ? JSON.parse(parsedOnce) : parsedOnce;
      }
    } catch (e) {
      relatedIds = [];
    }

    if (Array.isArray(relatedIds) && relatedIds.length > 0) {
      const relatedRes = await axiosEmployee.get("/products/related", {
        params: {
          ids: relatedIds.join(","),
        },
      });

      relatedProducts.value = relatedRes.data.data.map((product) => ({
        ...product,
        product_type_label:
          product.product_type_label ||
          ProductTypeOptions.value.find(
            (opt) => opt.value === product.product_type_id
          )?.label ||
          "Simple",
      }));
      selectedProductNames.value = relatedRes.data.data;
    }

    // --- Handle You May Also Like Products ---
    let youMayLikeIds = [];
    try {
      if (Array.isArray(item.you_may_like_product_ids)) {
        youMayLikeIds = item.you_may_like_product_ids;
      } else if (typeof item.you_may_like_product_ids === "string") {
        const parsedOnce = JSON.parse(item.you_may_like_product_ids);
        youMayLikeIds =
          typeof parsedOnce === "string" ? JSON.parse(parsedOnce) : parsedOnce;
      }
    } catch (e) {
      youMayLikeIds = [];
    }

    if (Array.isArray(youMayLikeIds) && youMayLikeIds.length > 0) {
      const youMayLikeRes = await axiosEmployee.get("/products/related", {
        params: {
          ids: youMayLikeIds.join(","),
        },
      });

      youMayLikeProducts.value = youMayLikeRes.data.data.map((product) => ({
        ...product,
        product_type_label:
          product.product_type_label ||
          ProductTypeOptions.value.find(
            (opt) => opt.value === product.product_type_id
          )?.label ||
          "Simple",
      }));
    }

    // Load dependent options
    const [subCatRes, purityRes
    ] = await Promise.all([
      form.category_id
        ? axiosEmployee.get(`/SubCategoryOptions/${form.category_id}`)
        : Promise.resolve(null),
      form.material_type_id
        ? axiosEmployee.get(`/PurityOptions/${form.material_type_id}`)
        : Promise.resolve(null)
    ]);

    if (subCatRes) SubCategoryOptions.value = subCatRes.data;
    if (purityRes) PurityOptions.value = purityRes.data;

    // Handle variant attributes
    form.variant_attribute_id = (
      item.variants && item.variants !== "null"
        ? JSON.parse(item.variants)
        : []
    ).map((id) => VariantAttributeOption.value.find((opt) => opt.value == id));

    // Handle variant options
    if (form.variant_attribute_id.length) {
      const ids = form.variant_attribute_id.map((v) => v.value);
      const res = await axiosEmployee.get(`/VariantOptions`, {
        params: {
          ids: ids.join(","),
          sub_category_id: form.sub_category_id,
        },
      });

      VariantOptions.value = res.data;
      form.variant_options_id = [];
      if (item.variant_options) {
        if (typeof item.variant_options === "string") {
          try {
            form.variant_options_id = JSON.parse(item.variant_options);
          } catch (e) {
            // fallback if comma-separated string
            form.variant_options_id = item.variant_options.split(",").map(v => v.trim());
          }
        } else if (Array.isArray(item.variant_options)) {
          form.variant_options_id = item.variant_options;
        }
      }
      if (item.childrenproducts && item.childrenproducts.length > 0) {
        form.children_products = item.childrenproducts.map(child => ({
          id: child.id,
          name: child.name,
          sku: child.sku,
          base_price: child.base_price,
          final_price: child.final_price,
          metal_weight: child.metal_weight,
          gemstone_weight: child.gemstone_weight,
          total_stock: child.total_stock,
          low_stock: child.low_stock,
          status: child.status,
          main_image: child.main_image,
          weight: child.weight,
          unit: child.unit,
          length: child.length,
          width: child.width,
          height: child.height,
          attributes: child.attributes,
          status: child.status ?? 'A',
        }));
      } else {
        form.children_products = [];
      }
    }

    // Load additional data in parallel
    const [chargesRes, taxesRes, mediaRes, subCategoryLabelRes] =
      await Promise.all([
        axiosEmployee.get(`/products/charges/${form.id}`),
        axiosEmployee.get(`/products/taxes/${form.id}`),
        axiosEmployee.get(`/products/${form.id}/media`),
        form.category_id
          ? axiosEmployee.get(`/SubCategoryOptions/${form.category_id}`)
          : Promise.resolve(null),
      ]);
    // Process charges
    productCharges.value = chargesRes.data.map((charge) => ({
      charges: charge.charges || charge.application_name,
      type: charge.type || "Percentage (%)",
      calculated_amount: charge.value ? form.base_price * charge.value / 100 : 0,
      value: charge.value || charge.amount || 0,
      primary_cost: charge.primary_cost || "Metal Weight Cost",
      description: charge.description || "",
    }));

    taxCharges.value = (taxesRes.data || []).map((row) => {


      return {
        tax_application: row?.tax_application ?? "",
        type: row?.type ?? "Percentage",
        value: Number(row?.value) || 0,
        primary_cost: row?.primary_cost ?? "",
        description: row?.description ?? "",
        calculated_amount: row?.calculated_amount ? parseFloat(row.calculated_amount) : 0,
      };
    });


    // Process media
    form.existing_media = mediaRes.data;
    imagePreviews.value = [];
    videoPreviews.value = []; // This will now only store one video

    form.existing_media.forEach((media) => {
      if (media.media_type === "image") {
        imagePreviews.value.push({
          url: `/storage/${media.file_url}`,
          type: "image",
          id: media.id,
          isNew: false,
        });
      } else if (media.media_type === "video") {
        // Only keep the first video if there are multiple
        if (videoPreviews.value.length === 0) {
          videoPreviews.value.push({
            url: `/storage/${media.file_url}`,
            type: "video",
            id: media.id,
            isNew: false,
          });
        } else {
          // If there are multiple videos in the database (from previous multiple uploads),
          // mark the extras as deleted so they'll be removed when the form is saved
          form.deleted_media.push(media.id);
        }
      }
    });

    // Process variants with calculated base prices
    const subCategoryLabel =
      subCategoryLabelRes?.data?.find(
        (opt) => opt.value === form.sub_category_id
      )?.label || "";



    const variantMediaRes = await axiosEmployee.get(
      `/products/${form.id}/variant-media`
    );

    const variantMediaMap = variantMediaRes.data.reduce((acc, variant) => {
      acc[variant.product_variant_id] = variant;
      return acc;
    }, {});

    getvarientcombinations();
    shouldCalculatePrice.value = true;
    // After full data load, recompute product charges/taxes and variant table values
    recomputeProductChargesAndTaxes();
    // Recompute for each variant to ensure table columns are populated
    AttributeVariantOptions.value.forEach((item, idx) => {
      recalculateBasePrice(idx);
    });
    // showList.value = false;
  } catch (error) {
    console.error("Error fetching product data:", error);
    // Handle error appropriately (show message, etc.)
  }
}
// }

// Watchers
watch(() => form.metal_weight, generateBasePrice);
watch(() => form.purity_karat_id, generateBasePrice);
watch(() => form.gemstone_weight, generateGemstonePrice);

watch(() => form.stone_color_id, generateGemstonePrice);
watch(() => form.stone_shape_id, generateGemstonePrice);
watch(() => form.stone_clarity_id, generateGemstonePrice);
watch(() => form.stone_cut_id, generateGemstonePrice);
// watch(() => form.stone_treatment_id, generateGemstonePrice);

watch(
  () => form.product_type_id,
  (newVal) => {
    if (selectedProductTypeLabel.value === "Ready-made") {
      form.variants_mode = "no";
    }
  }
);

const isEditing = ref(false);
const shouldCalculatePrice = ref(true);
function calculateFinalPrice1() {
  if (!shouldCalculatePrice.value) return;

  const discount = parseFloat(form.discount) || 0;
  const base = selectedProductTypeLabel.value === 'Ready-made'
    ? parseFloat(form.unit_price) || 0
    : parseFloat(form.base_price) || 0;

  if (isNaN(base)) {
    form.final_price = '';
    return;
  }

  const bases = {
    base,
    metalBase: metalBasePrice.value || 0,
    gemstoneBase: gemstoneBasePrice.value || 0,
  };

  let final = base;

  const chargeMap = {};
  for (const charge of productCharges.value) {
    charge.primary_cost = normalizePrimaryCost(charge.primary_cost);
    const amt = computeChargeAmount(charge, bases);
    if (charge.charges) {
      chargeMap[charge.charges] = amt;
    }
    final += amt;
  }

  for (const tax of taxCharges.value) {
    tax.primary_cost = normalizePrimaryCost(tax.primary_cost);
    const amt = computeTaxAmount(tax, bases, chargeMap);
    final += amt;
  }

  if (discount) {
    final -= (final * discount) / 100;
  }

  form.final_price = final.toFixed(2);
}

// 🔹 Watch
watch(
  () => [
    form.base_price,
    form.unit_price,
    form.discount,
    form.wastage_charges,
    form.making_charges,
    form.product_type_id,
    form.making_type,
    form.wastage_type,
    form.metal_weight,
    form.gemstone_weight,
    metalBasePrice.value,
    gemstoneBasePrice.value,
    productCharges.value,
    taxCharges.value,
  ],
  calculateFinalPrice1,
  { deep: true, immediate: false }
);


const TaxCostOptions = computed(() => {
  const base = selectedProductTypeLabel.value === 'Ready-made'
    ? parseFloat(form.unit_price) || 0
    : parseFloat(form.base_price) || 0;

  const options = [
    { label: 'Metal Weight Cost', amount: metalBasePrice.value || 0 },
    { label: 'Gemstone Cost', amount: gemstoneBasePrice.value || 0 },
    { label: 'Base Price', amount: base },
  ];

  productCharges.value.forEach((c) => {
    if (c.charges) {
      const label = normalizePrimaryCost(c.charges);
      options.push({ label, amount: parseFloat(c.calculated_amount) || 0 });
    }
  });

  return options;
});

watch(
  () => form.category_id,
  () => {
    RemoveError("category_id");
    const label = getOptionLabel(CategoryOptions.value, form.category_id);
    autoSelectTagByLabel(label, 'category');
  }
);

watch(
  () => form.product_type_id,
  () => {
    RemoveError("product_type_id");
    const label = getOptionLabel(ProductTypeOptions.value, form.product_type_id);
    autoSelectTagByLabel(label, 'product_type');
  }
);


watch(
  () => form.tags_id,
  () => {
    RemoveError("tags_id");
  }
);
watch(
  () => form.material_type_id,
  () => {
    RemoveError("material_type_id");
    // Auto-select material type label
    const label = getOptionLabel(MaterialTypeOptions.value, form.material_type_id);
    autoSelectTagByLabel(label);
  }
);
watch(
  () => form.purity_karat_id,
  () => {
    RemoveError("purity_karat_id");
    // Auto-select purity label
    const label = getOptionLabel(PurityOptions.value, form.purity_karat_id);
    autoSelectTagByLabel(label);
  }
);

watch(
  () => form.gemstone_type_id,
);
watch(
  () => form.sub_category_id,
  () => {
    RemoveError("sub_category_id");
    // Auto-select sub category label
    const label = getOptionLabel(SubCategoryOptions.value, form.sub_category_id);
    autoSelectTagByLabel(label);
  }
);

// Watch for changes in selection to update the "Select All" checkbox
// watch(
//   () => selectedProductNames.value,
//   (newVal) => {
//     selectingAllValues.value =
//       newVal.length === filteredRelatedProducts.value.length;
//   },
//   { deep: true }
// );
// Toggle products when checkbox OR text is clicked
watch(selectingAllValues, (checked) => {
  selectedProductNames.value = checked
    ? filteredRelatedProducts.value
    : [];
});

// Sync checkbox when user selects manually
watch(selectedProductNames, (val) => {
  selectingAllValues.value =
    val.length === filteredRelatedProducts.value.length;
});



// Mounted hook
onMounted(async () => {

  await Promise.all([


    axiosEmployee.get("/CategoryOptions").then((response) => {
      CategoryOptions.value = response.data;
    }),
    axiosEmployee.get("/ProductTypeOptions").then((response) => {
      ProductTypeOptions.value = response.data;
    }),
    axiosEmployee.get("/TagOptions").then((response) => {
      TagOptions.value = response.data;
    }),
    axiosEmployee.get("/MaterialTypeOptions").then((response) => {
      MaterialTypeOptions.value = response.data;
    }),
    axiosEmployee.get("/VariantAttributeOption").then((response) => {
      VariantAttributeOption.value = response.data;
    }),
    axiosEmployee.get("/charge-applications-options").then((res) => {
      chargeApplicationOptions.value = res.data.map((item) => ({
        ...item,
        label: item.application_name,
        value: item.id,
      }));
    }),
    axiosEmployee.get("/tax-master-options").then((res) => {
      taxMasterOptions.value = res.data;
    }),
    axiosEmployee.get('/partner-options')
      .then(res => {
        PartnerOptions.value = res.data.data;
      })
  ]);


  if (props.isEditing && props.productId) {
    await fetchProductData();
  } else {
    axiosEmployee.get('/fetchSku')
      .then((response) => {
        if (response.data.status) {
          form.sku = response.data.data;
        }
      })
  }
});
</script>
<style>
.formMaster.form-70.set1000 .wizard-tab-content,
.formMaster.form-70.set1000 .wizard-card-footer {
  width: 100%;
  margin: 0 auto;
}

.mmmmb0 {
  margin-bottom: 0 !important;
}

.pe-none {
  pointer-events: none;
}

.selection-col {
  width: 40px;
  padding: 0.25rem;
}
</style>
