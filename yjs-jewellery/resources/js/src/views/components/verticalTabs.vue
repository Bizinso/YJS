<template>
    <div class="verticalTabs">
       <b-tabs class="masterTabs" content-class="masterTabContent">
            <b-tab title="Organization Structure" active>
                <div class="organizationBoxes">
                    <div class="highlightedBox active">
                        <h2>Department</h2>
                    </div>
                    <div class="highlightedBox" >
                        <h2>Role</h2>
                    </div>
                    <div class="highlightedBox">
                        <h2>Designation</h2>
                    </div>
                    <div class="highlightedBox">
                        <h2>Employee</h2>
                    </div>
                </div>
            </b-tab>
            <b-tab title="Product Management">
                <p>I'm the second tab</p>
            </b-tab>
            <b-tab title="Order & Invoicing">
                <p>I'm the third tab</p>
            </b-tab>
            <b-tab title="Pricing & Discounts">
                <p>I'm the fourth tab</p>
            </b-tab>
            <b-tab title="Customers">
                <p>I'm the fifth tab</p>
            </b-tab>
            <b-tab title="Shipping & Payments">
                <p>I'm the sixth tab</p>
            </b-tab>
        </b-tabs>

    </div>
</template>
<script>
export default {
}
</script>

