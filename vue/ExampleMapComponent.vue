<template>
    <div v-bind:id="mapID" v-bind:style="style"></div>
</template>

<script>
import { mapState, mapActions, mapGetters } from "vuex";
import { imageCdn } from "helpers";
import MarkerOverlay from "./assets/js/MarkerOverlay";

export default {
    data() {
        return {
            mapID: "vueLeafletMap",
            mapIsInitialized: false,
            markers: [],
            bounds: [],
            infoWindows: [],
            overlayIcons: [],
            IconPaths: {
                //might need to update this to cdn locations
                currentLocation: "/assets/images/map/currentLocationIcon.png",
                markerPartial: "markers/markers/marker-"
            },
            icons: {
                location: {
                    size: {
                        x: 47,
                        y: 35
                    },
                    // anchor is offset from top left corner of icon
                    anchor: {
                        x: 23,
                        y: 35
                    },
                    cdnImagePath: imageCdn("markers/green-marker.png")
                },
                store: {
                    size: {
                        x: 34,
                        y: 51
                    },
                    // anchor is offset from top left corner of icon
                    anchor: {
                        x: 17,
                        y: 51
                    },
                    // popupAnchor is offset from anchor position
                    popupAnchor: {
                        x: 0,
                        y: -51
                    },
                    cdnImagePath: function(storeNum) {
                        return imageCdn('markers/markers-v2/'+storeNum+'.png');
                    }
                }
            }
        };
    },
    props: {
        zoom: {
            type: Number,
            default: 6
        },
        scrollwheel: {
            type: Boolean,
            default: true
        },
        zoomControl: {
            type: Boolean,
            default: false
        },
        height: {
            type: Number,
            default: 350
        },
        showInfoWindows: {
            type: Boolean,
            default: true
        }
    },
    computed: {
        ...mapState("mapData", ["mapLocation"]),
        ...mapState("biffyStores", ["storeData", "selectedStoreMapIndex"]),
        ...mapState(["isMobile"]),
        ...mapGetters("mapData", ["getDefaultOrMapLocation"]),
        //map options, initialized from props
        mapOptions() {
            return {
                zoom: this.zoom,
                scrollWheelZoom: this.scrollwheel,
                zoomControl: this.zoomControl,
                center: this.mapLocation,
            };
        },
        //dynamic style from parent (way of adding a "style prop")
        style() {
            return "height: " + this.height + "px";
        }
    },
    methods: {
        ...mapActions("userLocation", [
            "fetchUserLocation",
            "getFetchUserLocation"
        ]),
        ...mapActions("mapData", [
            "initMapLocation",
            "addMap"
        ]),
        ...mapActions("biffyStores", [
            "fetchStoresFromLocation",
            "changeSelectedStoreListIndex",
            "changeSelectedStoreMapIndex",
            "resetSelectedStoreListIndexTo"
        ]),
        ...mapGetters("mapData", ["getMapLocation"]),
        //initializes map
        initMap() {
            L.Map.prototype.panToOffset = function (latlng, offset, options) {
                var x = this.latLngToContainerPoint(latlng).x - offset[0]
                var y = this.latLngToContainerPoint(latlng).y - offset[1]
                var point = this.containerPointToLatLng([x, y])
                return this.setView(point, this._zoom, { pan: options })
            }

            let centerObj = this.getMapLocation()
                ? this.mapLocation
                : {
                      center: this.getDefaultOrMapLocation,
                      zoom: this.zoom
                  };
            this.map = L.map(
                document.getElementById(this.mapID),
                {
                    ...this.mapOptions,
                    ...centerObj
                }
            );

            let _tileLayer = L.tileLayer(process.env.MIX_TILE_URL, {
                attribution: '<a href="' + process.env.MIX_TILE_ATTRIBUTE_URL + '">' + process.env.MIX_TILE_ATTRIBUTE_NAME + '</a>'
            }).addTo( this.map );

            if (typeof _st !== "undefined") {
                _st.render();
            }

            this.addMap(this.map);
            this.resetMarkers();
            this.initMarkers();
            this.mapIsInitialized = true;
        },
        //checks if map has been initialized already when mapLocation changes, calls accordingly
        onNewMapLocation() {
            if (!this.mapIsInitialized) {
                this.initMap();
            } else {
                this.updateMapLocation();
            }
        },
        //updates mapLocation to new mapLocation after map has been initialized already
        updateMapLocation() {
            this.updateMapOptions();
            this.resetMarkers();
            this.changeSelectedStoreListIndex(0);
            this.changeSelectedStoreMapIndex(0);
            this.resetInfoWindows();
            this.initMarkers();
        },
        updateMapOptions() {
            this.map.setView(this.mapOptions.center, this.mapOptions.zoom);
        },
        clearMarkers(map) {
            this.markers.map(marker => {
                marker.removeFrom(map);
            });
        },
        showMarkers(map) {
            this.markers.map(marker => {
                marker.addTo(map);
            });
        },
        deleteMarkers() {
            this.clearMarkers(this.map);
            this.markers = [];
            this.overlayIcons = [];
        },
        resetMarkers() {
            if (this.markers.length > 0) {
                this.deleteMarkers();
            }
        },
        //resets infoWindows
        resetInfoWindows() {
            if (this.infoWindows.length > 0) {
                this.infoWindows = [];
            }
        },
        //initializes center marker and markers[]
        initMarkers() {
            this.markers = [
                L.marker(this.getDefaultOrMapLocation, {
                    icon: L.icon({
                        iconUrl: this.icons.location.cdnImagePath,
                        iconSize: this.icons.location.size,
                        iconAnchor: this.icons.location.anchor
                    })
                }).addTo(this.map)
            ];
            this.bounds.push(this.mapLocation);
        },
        //adds markers to map from storeData
        addMarkers(storeData) {
            storeData.slice(0, storeData.length).map((store, index) => {
                let latLng = {
                    lat: parseFloat(store.store_info.latitude),
                    lng: parseFloat(store.store_info.longitude)
                };

                let markerIcon = L.icon({
                    iconUrl: this.icons.store.cdnImagePath(index+1),
                    iconSize: this.icons.store.size,
                    iconAnchor: this.icons.store.anchor,
                    popupAnchor: this.icons.store.popupAnchor
                });
                
                let marker = L.marker(latLng, {icon: markerIcon}).addTo(this.map).on('click', function() {
                    this.changeSelectedStoreListIndex(index + 1);
                    this.changeSelectedStoreMapIndex(index + 1);
                    if (typeof _st !== "undefined") {
                        _st.render();
                    }
                }.bind(this));

                if(this.showInfoWindows) this.createMarkerInfoWindowContent(store, marker, index);

                this.markers.push(marker);
                this.bounds.push(latLng);
            });
        },
        //changes focus on new marker, mobile
        changeMarkerFocusMobile(marker) {
            this.map.panToOffset(marker.getLatLng(), [0,10]);
        },
        //changes focus on new marker, desktop
        changeMarkerFocus(marker, infoWindow) {
            marker.openPopup();
            this.map.panToOffset(marker.getLatLng(), [0,60]);
        },
        //creates click functionality and infowindow for each marker
        createMarkerInfoWindowContent(store, marker, index) {
            marker.bindPopup(store.marker, {minWidth: 400, maxWidth: 600}).openPopup();
        },
        //fits bounds and changes viewport
        fitAndMapBounds() {
            let mapBounds = L.latLngBounds(this.bounds);
            this.map.fitBounds(mapBounds);
            if(this.storeData.length > 0) {
                this.map.once('zoomend', function() {
                    this.map.panToOffset(this.markers[1].getLatLng(), [0,60]);
                }.bind(this));
            } else {
                this.map.once('zoomend', function() {
                    this.map.panToOffset(this.mapLocation, [0,-60]);
                }.bind(this));
            }
        },
        //resets map bounds
        resetBounds() {
            this.bounds = [];
        },
        //changes markerFocus on map when selectedStoreIndex changes.
        watchSelectedStoreMapIndex() {
            if (this.selectedStoreMapIndex !== 0 && !this.isMobile) {
                this.changeMarkerFocus(
                    this.markers[this.selectedStoreMapIndex]
                );
            } else if (this.selectedStoreMapIndex !== 0) {
                this.changeMarkerFocusMobile(
                    this.markers[this.selectedStoreMapIndex]
                );
            }
        }
    },
    watch: {
        //calls onNewMapLocation() when mapLocation changes.
        mapLocation() {
            this.onNewMapLocation();
        },
        //resets bounds/markers and fits map when storeData changes
        storeData() {
            this.resetBounds();
            this.addMarkers(this.storeData);
            if (typeof _st !== "undefined") 
            {
                _st.render();
            }
            if(this.map)
            {
                this.map.invalidateSize();
                this.fitAndMapBounds();
            }
        }
    },
    mounted() {
        if(!window.selectedStore)
        {
            this.onNewMapLocation();
        }
    },
    created() {
        this.unwatchSelectedStoreMapIndex = this.$watch('selectedStoreMapIndex', this.watchSelectedStoreMapIndex);
    }
};
</script>