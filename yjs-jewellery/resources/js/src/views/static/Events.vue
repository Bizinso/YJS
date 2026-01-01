<template>
    <div class="yjs_Events">
        <div class="global_breadcrumbs">
            <div class="container">
                <ul class="breadcrumb">
                    <li>Events</li>
                </ul>
            </div>
        </div>
        <div class="container">
            <b-row>
                <b-col md="8">
                    <div class="overlayEvent">
                        <span class="overlaybox"></span>
                        <img src="../assets/images/static/event/event1.png" alt="ring1" class="offerIcon" />

                        <div class="infoBox">
                            <h5 class="eventHeading">Jewellery World Expo 2025</h5>
                            <p class="eventDetails"><span>Date: </span> October 10–12</p>
                            <p class="eventDetails bracketSpace"><span>Venue: </span> Paris Expo Porte de Versailles,
                                France</p>
                            <b-link :to="{ name: 'details' }" class="listingIcon">Register Now <img class="mr-1"
                                    src="../assets/images/icons/right-arrow.svg" alt="right-arrow-black"></b-link>
                        </div>
                    </div>

                </b-col>
                <b-col md="4">
                    <div class="upcomingEvents">
                        <h2 class="upHead">Upcoming</h2>
                        <div class="eventItem">
                            <img src="../assets/images/static/event/upcomingEvents1.png" alt="ring1"
                                class="offerIcon" />
                            <div class="eventDetails">
                                <h2 class="eventTitle">Jewellery World Expo 2025</h2>
                                <h5 class="descBox">A showcase of timeless classics and modern artistry.</h5>
                                <h5 class="eventDates">October 10–12, 2025, France</h5>
                            </div>
                        </div>
                        <div class="eventItem">
                            <img src="../assets/images/static/event/upcomingEvents2.png" alt="ring1"
                                class="offerIcon" />
                            <div class="eventDetails">
                                <h2 class="eventTitle">Dubai International Jewellery Fair</h2>
                                <h5 class="descBox">A showcase of timeless classics and modern artistry.</h5>
                                <h5 class="eventDates">December 2–5, 2025, UAE</h5>
                            </div>
                        </div>
                        <div class="eventItem">
                            <img src="../assets/images/static/event/upcomingEvents3.png" alt="ring1"
                                class="offerIcon" />
                            <div class="eventDetails">
                                <h2 class="eventTitle">Royal Heritage Jewelry Summit</h2>
                                <h5 class="descBox">Showcase of regal and antique-inspired jewelry designs, bringing
                                    together global artisans and collectors.</h5>
                                <h5 class="eventDates">December 2–5, 2025, UAE</h5>
                                <b-link :to="{name: 'details'}">View Details <img src="../assets/images/icons/right-arrow-black.svg" alt="right-arrow-black"></b-link>
                            </div>
                        </div>
                    </div>
                </b-col>
            </b-row>
        </div>

        <div class="newEventBlogs">
            <div class="container">
                <b-row>
                    <b-col md="8">
                        <div class="listEvents">
                            <h2 class="headBox">Our Journey Through the Years</h2>
                            <h2 class="DescBox">A look back at where we’ve showcased our artistry and unveiled new
                                creations.</h2>

                            <div class="listOFEvents">
                                <img src="../assets/images/static/event/upcomingEvents3.png" alt="ring1"
                                    class="offerIcon" />
                                <div class="eventDetails">
                                    <div>
                                        <h2 class="eventTitle">Royal Heritage Jewelry Summit</h2>
                                        <h5 class="innerDescBox">Showcase of regal and antique-inspired jewelry designs, bringing
                                        together global artisans and collectors.</h5>
                                        <h5 class="eventDates"><span>Date: </span> March 21–25, 2024</h5>
                                        <h5 class="eventDates"><span>Venue: </span> Basel, Switzerland</h5>
                                        </div>
                                    <b-link class="menuLink" :to="{name: 'details'}">View Details <img src="../assets/images/icons/right-arrow-black.svg" alt="right-arrow-black"></b-link>
                                </div>
                            </div>

                            <div class="listOFEvents">
                                <img src="../assets/images/static/event/upcomingEvents3.png" alt="ring1"
                                    class="offerIcon" />
                                <div class="eventDetails">
                                    <div>
                                        <h2 class="eventTitle">Royal Heritage Jewelry Summit</h2>
                                        <h5 class="innerDescBox">Showcase of regal and antique-inspired jewelry designs, bringing
                                        together global artisans and collectors.</h5>
                                        <h5 class="eventDates"><span>Date: </span> March 21–25, 2024</h5>
                                        <h5 class="eventDates"><span>Venue: </span> Basel, Switzerland</h5>
                                        </div>
                                    <b-link class="menuLink" :to="{name: 'details'}">View Details <img src="../assets/images/icons/right-arrow-black.svg" alt="right-arrow-black"></b-link>
                                </div>
                            </div>

                            <div class="listOFEvents">
                                <img src="../assets/images/static/event/upcomingEvents3.png" alt="ring1"
                                    class="offerIcon" />
                                <div class="eventDetails">
                                    <div>
                                        <h2 class="eventTitle">Royal Heritage Jewelry Summit</h2>
                                        <h5 class="innerDescBox">Showcase of regal and antique-inspired jewelry designs, bringing
                                        together global artisans and collectors.</h5>
                                        <h5 class="eventDates"><span>Date: </span> March 21–25, 2024</h5>
                                        <h5 class="eventDates"><span>Venue: </span> Basel, Switzerland</h5>
                                        </div>
                                    <b-link class="menuLink" :to="{name: 'details'}">View Details <img src="../assets/images/icons/right-arrow-black.svg" alt="right-arrow-black"></b-link>
                                </div>
                            </div>

                            <div class="listOFEvents">
                                <img src="../assets/images/static/event/upcomingEvents3.png" alt="ring1"
                                    class="offerIcon" />
                                <div class="eventDetails">
                                    <div>
                                        <h2 class="eventTitle">Royal Heritage Jewelry Summit</h2>
                                        <h5 class="innerDescBox">Showcase of regal and antique-inspired jewelry designs, bringing
                                        together global artisans and collectors.</h5>
                                        <h5 class="eventDates"><span>Date: </span> March 21–25, 2024</h5>
                                        <h5 class="eventDates"><span>Venue: </span> Basel, Switzerland</h5>
                                        </div>
                                    <b-link class="menuLink" :to="{name: 'details'}">View Details <img src="../assets/images/icons/right-arrow-black.svg" alt="right-arrow-black"></b-link>
                                </div>
                            </div>
                        </div>
                    </b-col>
                    <b-col md="4">

                    </b-col>
                </b-row>
            </div>
        </div>

    </div>
</template>

<script setup>


</script>
