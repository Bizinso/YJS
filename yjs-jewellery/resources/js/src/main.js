import { createApp } from "vue";
import App from "./App.vue";
import router from "./router/index.js";
import '@fortawesome/fontawesome-free/css/all.min.css';
import BootstrapVue3 from "bootstrap-vue-3";
import "bootstrap/dist/css/bootstrap.css";
import "bootstrap-vue-3/dist/bootstrap-vue-3.css";
import "./views/assets/scss/style.scss";
import "./views/assets/scss/admin.scss";
const app = createApp(App);

import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import Vue3FormWizard from 'vue3-form-wizard';
import 'vue3-form-wizard/dist/style.css';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css'
import mitt from 'mitt';
import { ability, abilitiesPlugin } from './ability';

app.component('VueDatePicker', VueDatePicker)
app.component('v-select', vSelect);
// app.use(store);
app.use(router);
app.use(BootstrapVue3);
const emitter = mitt();
app.config.globalProperties.emitter = emitter;
app.config.globalProperties.$can = (action, subject) => ability.can(action, subject);
app.use(abilitiesPlugin, ability);
app.mount("#app");


