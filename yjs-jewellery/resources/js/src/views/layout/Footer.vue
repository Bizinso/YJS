<script setup>
</script>

<template>
    <div class="yjs_footer">
        <div class="container">
            <div class="footer_links_info">
                <div class="footer_link_item">
                    <b-link :to="{name: 'home'}">

                        <img src="../assets/images/yjs_white_logo.svg" class="yjs_footer_logo" alt="yjs_white_logos">
                        <div class="footer_hallmark">
                            <img src="../assets/images/bisHallMarked.png" class="hallmark_logo" alt="bisHallMarked">
                            <p>All Products are BIS Hallmarked <br />Certified</p>
                        </div>
                    </b-link>
                </div>
                <div class="footer_link_item">
                    <h6>Company</h6>
                    <ul>
                        <li><b-link :to="{name: 'about'}">About Yashoda Jagdish And Sons Jewellery</b-link></li>
                        <li><b-link>Contact us</b-link></li>
                    </ul>
                    <h6 class="mt-4">We Accept</h6>
                    <img src="../assets/images/visa.svg" class="we_accept me-2" alt="visa">
                    <img src="../assets/images/mastercard.svg" class="we_accept" alt="mastercard">
                </div>
                <div class="footer_link_item">
                    <h6>Quick Links</h6>
                    <ul>
                        <li><b-link>Terms and conditions</b-link></li>
                        <li><b-link>Privacy Policy</b-link></li>
                        <li><b-link>Returns & Refund Policy</b-link></li>
                        <li><b-link>Cancellation Policy</b-link></li>
                        <li><b-link>Shipping Policy</b-link></li>
                    </ul>
                </div>
                <div class="footer_link_item">
                    <h6>Address</h6>
                    <ul>
                        <li>14, Floor 3, Plot 19, Shreeji Niwas, Pophalwadi 3 lane, Bhuleshwar, Kalbadevi,
                            MUMBAI, Mumbai City,
                            Maharashtra - 400002.</li>
                    </ul>
                    <h6 class="mt-4">Follow us on</h6>
                    <i class="fa-brands fa-instagram social_link_footer"></i>
                    <i class="fa-brands fa-facebook-f social_link_footer"></i>
                    <i class="fa-brands fa-youtube social_link_footer"></i>
                    <i class="fa-brands fa-x-twitter social_link_footer"></i>
                </div>
            </div>
            <div class="footer_links_info mt-4">
                <div class="footer_link_item"></div>
                <div class="footer_link_item">
                    <p class="support_mail"><i class="fa-regular fa-envelope"></i> sales@yjs.com</p>
                </div>
                <div class="footer_link_item">
                    <p class="support_mail"><i class="fa-solid fa-headset"></i> Support@yjs.com</p>
                </div>
                <div class="footer_link_item"></div> 
            </div>
        </div>
    </div>
</template>