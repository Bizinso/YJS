  <template>
    <div>
      <b-form @submit="onSubmit" @reset="onReset" v-if="show" class='formMaster form-60'>

        <div class="product-attributes">
    <!-- Color Swatches -->
    <b-form-group label="Color:" class="multiDrop">
      <div class="d-flex flex-wrap">
        <div
          v-for="color in colors"
          :key="color.value"
          :style="{ backgroundColor: color.code }"
          class="swatch"
          :class="{ active: Swatch.color === color.value }"
          @click="Swatch.color = color.value"
        ></div>
      </div>
      <small v-if="Swatch.color" class="text-muted">
        Selected: {{ Swatch.color }}
      </small>
    </b-form-group>

    <!-- Size Swatches -->
    <b-form-group label="Size:" class="multiDrop">
      <div class="d-flex flex-wrap">
        <button
          v-for="size in sizes"
          :key="size"
          type="button"
          class="btn btn-outline-secondary m-1 swatchText"
          :class="{ active: Swatch.size === size }"
          @click="Swatch.size = size"
        >
          {{ size }}
        </button>
      </div>
      <small v-if="Swatch.size" class="text-muted">
        Selected: {{ Swatch.size }}
      </small>
    </b-form-group>

    <!-- Dropdown -->
    <b-form-group label="Material:" class="multiDrop">
      <v-select
        :options="materials"
        v-model="form.material"
        placeholder="Select material"
      />
      <small v-if="Swatch.material" class="text-muted">
        Selected: {{ Swatch.materisal }}
      </small>
    </b-form-group>
  </div>

        <b-form-group
          label="Date Picker:"
          class="DateField"
        >
        <VueDatePicker v-model="picked" time-picker ></VueDatePicker>
      </b-form-group>
      
      
      <b-form-group
          label="Date Picker:"
          class="DateField"
        >
        <VueDatePicker v-model="picked" :enable-time-picker="true"></VueDatePicker>
      </b-form-group>

      
        <b-form-group
          label="Date Picker:"
          class="DateField"
        >
        <Datepicker v-model="picked" />
        </b-form-group>


        <b-form-group
          label="Single Dropdown:"
          class="multiDrop"
        >
          <v-select :options="foods" placeholder="Select an option"></v-select>
        </b-form-group>

        <b-form-group
          label="Multi Select Dropdown:"
          class="multiDrop"
        >
          <v-select :options="foods" placeholder="Select an option" multiple></v-select>
        </b-form-group>


        
        <b-form-group label="Multi Select Dropdown:" class="multiDrop">

          <v-select
            :options="contactMethods"
            v-model="form.checkedmulti"
            placeholder="Select an option"
            multiple
            label="label"
          >
            <template #list-header>
              <li class="dynamites">

                <b-form-checkbox
                v-model="selectingAllValues"
                @change="toggleAll"
                >
                <strong>Select/Deselect All</strong>
              </b-form-checkbox>
            </li>
            </template>

            <template #option="{ label, selected }">
              <div class="d-flex align-items-center px-2">
                <!-- <b-form-checkbox
                  :checked="selected"
                  class="mr-2"
                /> -->
                {{ label }}
              </div>
            </template>
          </v-select>
        </b-form-group>


        <!-- <div class="dropdown" @click.stop>
    <button
  class="btn btn-outline-secondary dropdown-toggle specificIndicatorMultiChecks"
  type="button"
  @click.stop="toggleDropdown"
>
  <span class="dropdown-text">
    <template v-if="form.checkedmulti.length">
      <span
        v-for="(item, index) in form.checkedmulti"
        :key="index"
        class="badge bg-primary me-1"
      >
        {{ item }}
      </span>
    </template>
    <template v-else>
      Select Options
    </template>
  </span>
  <span class="caret"></span>
</button>

    <ul class="dropdown-menu show" v-if="isOpen" @click.stop>
      <li>
        <b-form-checkbox 
              type="checkbox"
              class="selectingAllValues"
              v-model="selectingAllValues"
              @change="toggleAll"
            >
            <span class="select-text">
              {{ selectingAllValues ? 'Deselect' : 'Select' }}
            </span>
            All
          </b-form-checkbox>
      </li>
      <li v-for="option in optionsValue" :key="option">
            <b-form-checkbox 
              type="checkbox"
              name="check-button2"
              class="option justone"
              :value="option"
              v-model="form.checkedmulti"
              @change="handleIndividualCheck"
            >
            {{ option }}
            </b-form-checkbox>
      </li>
    </ul>
  </div> -->



    
        <b-form-group
          id="input-group-1"
          label="Email address:"
          label-for="input-1"
        >
          <b-form-input
            id="input-1"
            v-model="form.email"
            type="email"
            placeholder="Enter email"
            required
          ></b-form-input>
        </b-form-group>

      
        <b-form-group id="input-group-2" label="Your Name:" label-for="input-2">
          <b-form-input
            id="input-2"
            v-model="form.name"
            placeholder="Enter name"
            required
          ></b-form-input>
        </b-form-group>

      
        <b-form-group id="input-group-3" label="Food:" label-for="input-3">
          <b-form-select
            id="input-3"
            v-model="form.food"
            :options="foods"
            required
          ></b-form-select>
        </b-form-group>

      
        <b-form-group class='radioclassification' label="Preferred Contact Method:">
          <b-form-radio-group
            class="radioSpace"
            v-model="form.contactMethod"
            :options="contactMethods"
            name="contactMethods"
          ></b-form-radio-group>
        </b-form-group>

          <b-form-group label="Select items:" class='radioclassification'>
              <!-- <b-form-checkbox class="radioSpace" v-model="selectAll" @change="toggleSelectAll">
              Select All
              </b-form-checkbox> -->

              <b-form-checkbox-group class="radioSpace" v-model="form.checked" :options="options" />
          </b-form-group>

        <b-form-group class='radioclassification' label="Switch Checkbox:">
          <b-form-checkbox v-model="checked" class="switch" name="check-button" switch></b-form-checkbox>
        </b-form-group>

          <b-form-group label="Additional Notes">
              <b-form-textarea
                  v-model="form.notes"
                  placeholder="Add notes here..."
                  rows="3"
                  max-rows="6"
              />
              </b-form-group>

              <b-form-group label="Upload Images">
                <div class="image-upload-wrapper text-center">
                  <div class="thumbnail-grid">
                    <div
                      v-for="(img, index) in imagePreviews"
                      :key="index"
                      class="thumbnail-container"
                    >
                      <img :src="img" class="thumbnail" alt="Selected image" />
                      <button class="remove-btn" @click="removeImage(index)" type="button">×</button>
                    </div>
                    <div class="thumbnail-container upload-placeholder" @click="triggerFileInput">
                      <img src="../assets/img/uploadimage.png" alt="Selected image" />
                    </div>
                  </div>
                  <p class="upload-note mt-2">
                    Image resolution should be like 560px X 609px. Png, Jpg, Jpeg etc.
                  </p>
                  <input
                    type="file"
                    ref="fileInput"
                    @change="handleFileChange"
                    accept="image/png, image/jpeg, image/jpg"
                    multiple
                    class="d-none"
                  />
                </div>
              </b-form-group>

              <b-form-group label="Upload Videos">
                <div class="video-upload-wrapper text-center">
                  <div class="video-thumbnail-grid">
                    <div
                      v-for="(video, index) in videoPreviews"
                      :key="index"
                      class="video-thumbnail-container"
                    >
                      <video
                        :src="video"
                        class="video-thumbnail"
                        controls
                        muted
                        playsinline
                      ></video>
                      <button class="remove-btn" @click="removeVideo(index)" type="button">×</button>
                    </div>
                    <div class="video-thumbnail-container upload-placeholder" @click="triggerVideoInput">
                      <img src="../assets/img/uploadvid.png" alt="Selected image" />
                    </div>
                  </div>
                  <p class="upload-note mt-2">
                    Video formats allowed: mp4, webm, ogg etc.
                  </p>
                  <input
                    type="file"
                    ref="videoInput"
                    @change="handleVideoChange"
                    accept="video/mp4, video/webm, video/ogg"
                    multiple
                    class="d-none"
                  />
                </div>
              </b-form-group>


      
        <div class="buttonGrid">
            <b-button type="submit" class="fillBTN">Save</b-button>
            <b-button type="reset" class="transBTN">Cancel</b-button>
          </div>
      
      </b-form>

    
    </div>
  </template>


  <script setup>
import { ref, reactive, watch, computed, nextTick } from 'vue'
import VueDatePicker from '@vuepic/vue-datepicker';
const picked = ref(new Date())

const form = reactive({
  email: '',
  name: '',
  food: null,
  contactMethod: null,
  checked: [],
  checkedmulti: [],
  notes: ''
})
const Swatch = reactive({
  color: null,
  size: null,
  material: null,
});

const colors = [
  { value: "Red", code: "#ff0000" },
  { value: "Blue", code: "#0000ff" },
  { value: "Green", code: "#00ff00" },
];

const sizes = ["S", "M", "L", "XL"];

const materials = ["Cotton", "Polyester", "Leather"];

const checked = ref(false)
const selectAll = ref(false)
const options = ['Option A', 'Option B', 'Option C']
const foods = [
  { text: 'Select One', value: null },
  'Carrots',
  'Beans',
  'Tomatoes',
  'Corn'
]
const contactMethods = [
  { label: 'Email', value: 'email' },
  { label: 'Phone', value: 'phone' },
  { label: 'Text Message', value: 'text' }
]

const show = ref(true)

const optionsValue = ['Option 1', 'Option 2', 'Option 3']
const selectedOptionsValue = ref([])
const isOpen = ref(false)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

// Extract values for select all logic
const optionsValueMain = contactMethods.map(opt => opt.value)

// Checkbox controlling Select All
const selectingAllValues = ref(false)

// Watch selection to update select-all checkbox state
watch(
  () => form.checkedmulti,
  (newVal) => {
    selectingAllValues.value = newVal.length === optionsValueMain.length
  },
  { immediate: true }
)

// Select/Deselect all logic
const toggleAll = () => {
  if (selectingAllValues.value) {
    form.checkedmulti = [...contactMethods]
  } else {
    form.checkedmulti = []
  }
}




// Methods
function onSubmit(event) {
  event.preventDefault()
  alert(JSON.stringify(form, null, 2))
}

function toggleSelectAll() {
  if (selectAll.value) {
    form.checked = [...options]
  } else {
    form.checked = []
  }
}

function onReset(event) {
  event.preventDefault()
  form.email = ''
  form.name = ''
  form.food = null
  form.contactMethod = null
  form.checked = []
  show.value = false
  nextTick(() => {
    show.value = true
  })
}

//Code For Input file Image start here
const imagePreviews = ref([]);
const fileInput = ref(null);

const triggerFileInput = () => {
  fileInput.value.click();
};

const handleFileChange = (event) => {
  const files = Array.from(event.target.files);

  files.forEach((file) => {
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        // Check if already added (optional: avoid duplicates)
        if (!imagePreviews.value.includes(e.target.result)) {
          imagePreviews.value.push(e.target.result);
        }
      };
      reader.readAsDataURL(file);
    }
  });
  event.target.value = '';
};
const removeImage = (index) => {
  imagePreviews.value.splice(index, 1);
};
//Code For Input file Image ends here

//Code For Input File Video Starts Here

const videoPreviews = ref([]);
const videoInput = ref(null);

const triggerVideoInput = () => {
  videoInput.value.click();
};

const handleVideoChange = (event) => {
  const files = Array.from(event.target.files);

  files.forEach((file) => {
    if (file.type.startsWith('video/')) {
      const url = URL.createObjectURL(file);
      if (!videoPreviews.value.includes(url)) {
        videoPreviews.value.push(url);
      }
    }
  });

  // Reset input for re-selecting same files later
  event.target.value = '';
};

const removeVideo = (index) => {
  // Release object URL to avoid memory leaks
  URL.revokeObjectURL(videoPreviews.value[index]);
  videoPreviews.value.splice(index, 1);
};

//Code For Input File Video ends Here



// Watcher
watch(
  () => form.checked,
  (newVal) => {
    selectAll.value = newVal.length === options.length
  }
)
</script>

<style>
/*  */

</style>