<script setup>
//local registration
import { FormWizard, TabContent } from "vue3-form-wizard";
import "vue3-form-wizard/dist/style.css";

function onComplete() {
  alert("Yay. Done!");
}
</script>
<template>
    <div class="customFormWizard">

  <FormWizard @on-complete="onComplete" shape="tab">
    <TabContent title="Personal details">
      My first tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Additional Info">
      My second tab content
    </TabContent>
    <TabContent title="Last step">
      Yuhuuu! This seems pretty damn simple
    </TabContent>
    <template v-slot:footer="props">
      <div class="wizard-footer-left buttonGrid">
        <b-button
          v-if="!props.isLastStep"
          @click.native="props.nextTab()"
          class="fillBTN wizard-button"
          :style="props.fillButtonStyle"
        >
          Next
        </b-button>

        <b-button
          v-else
          @click.native="onComplete"
          class="fillBTN wizard-button"
          :style="props.fillButtonStyle"
        >
          {{ props.isLastStep ? "Done" : "Next" }}
        </b-button>
        <b-button class="transBTN wizard-button"
          v-if="props.activeTabIndex > 0"
          :style="props.fillButtonStyle"
          @click.native="props.prevTab()"
        >
          Previous
        </b-button>
      </div>
      
    </template>
  </FormWizard>
  
    </div>
</template>
