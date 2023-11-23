@extends('layouts.app')
@section('content')
<?php
    header('Content-Type: application/json');
?>
    <style>
        #map {
            width: 100%;
            height: 100%;
        }
    </style>
    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-md-5 align-self-center">
                <h3 class="page-title">{{ __('View Trip') }}</h3>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">{{ __('Trips') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('Trip') }}</li>
                            @if(!empty($tripName))
                            <li class="breadcrumb-item active" aria-current="page">{{ $tripName }}</li>
                            @endif
                        </ol>
                    </nav>
                </div>
            </div>
            <div class="d-flex align-items-right">
                <p>
                    @php
                        if (!empty($distanceKm) && !empty($distanceMile) && !empty($duration)) {
                            echo "<b>Distance:</b> ";
                            echo $distanceKm." km / ";
                            echo $distanceMile." mi";
                            echo " <b> | </b> ";
                            echo "<b>Duration:</b> ";
                            echo $duration;

                        }
                    @endphp
                </p>
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Container fluid  -->
    <!-- ============================================================== -->
    <div class="container-fluid">
        <!-- Row -->
        <div class="row">
            <!-- Column -->
            <div class="col-lg-12 col-md-12">
                <div style="height: 500px; width: 100%; margin-top: 15px" >
                    <div id="map"></div>
                </div>  
            </div>
            <!-- Column -->
        </div>
        <!-- Row -->
    </div>
    <!-- ============================================================== -->
    <!-- End Container fluid  -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    @push('scripts')
    <script>

        let locations = [];
        let images = [];

        let tripId =    @php
                            echo $tripId;
                        @endphp

        function getRequestParameters() {
           
            let parameters = window.location.search.substr(1);
            let imagesJson = window.location.search.substr(1);

            parameters = @php 
                            echo json_encode(($lastTrip));
                        @endphp

            imagesJson =    @php 
                                echo $imagesAr;
                            @endphp
     
            if(parameters !== ""){
            // Split '/' from parameters
                // parameters = parameters.split(',');
            // Split(',') and Get coordinates from parameters
                for(var i=0; i < parameters.length; i++){
                    locations.push(parameters[i].split(','));
                }
            }else{
                console.log("something went wrong!!");
            }

            if(imagesJson !== "" && imagesJson !== 0){
                for(var i=0; i < imagesJson.length; i++){
                    images.push(imagesJson[i].split(','));
                }
            }
          
           initMap();
        }

        function initMap() {

          
            if(locations.length <= 0){
               return false;
            }
            var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 10,
            center: new google.maps.LatLng(-33.92, 151.25),
            mapTypeId: 'roadmap'
            });

            var marker, i;
            var markers = []; // Array to get all markers for fitBounds function
            let imageMarker;
            let imageMarkers = [];

            let icon = {
                url: "{{ asset('assets/images/camera_pin.png') }}", // url
                scaledSize: new google.maps.Size(50, 50), // scaled size
                origin: new google.maps.Point(0,0), // origin
                // anchor: new google.maps.Point(0, 0) // anchor
            };

            for (i = 0; i < images.length; i++) {  
   
                let imageName = images[i].join(',');
                let timestap = images[i][2];

                let timestapx = timestap.split('.').shift();
                let formatedDateTime = timestapx.replace(/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/,"$1/$2/$3 $4:$5:$6"); 

                let imgUrla = '<?php echo asset("storage/trips/tripId/imageName"); ?>';
                let imgUrlb = imgUrla.replace("tripId", tripId);
                let imgUrl = imgUrlb.replace("imageName", imageName);

                let contentString = "<div>"
                                    +"<h6 style='text-align: center; color: black'>"+formatedDateTime+"</h6>"
                                    +"<img src='"+imgUrl+"' width='180px' height='auto' alt='geo image'/>"
                                    +"</div>";

                const infowindow = new google.maps.InfoWindow({
                    maxWidth: 450,
                });

                imageMarker = new google.maps.Marker({
                    position: new google.maps.LatLng(images[i][0], images[i][1]),
                    map,
                    icon: icon
                });
                
                imageMarkers.push(imageMarker)

                google.maps.event.addListener(imageMarker, 'click', (function(imageMarker, i) {
                    return function() {
                        infowindow.setContent(contentString);
                        infowindow.open(map, imageMarker);
                    }
                })(imageMarker, i));

            }

            let endLoc = locations.length - 1;

            let iconSt = {
                    url: "{{ asset('assets/images/start.png') }}", // url
                    scaledSize: new google.maps.Size(25, 25), // scaled size
                    origin: new google.maps.Point(0,0), // origin
                    // anchor: new google.maps.Point(0, 0) // anchor
                };

            let iconEd = {
                    url: "{{ asset('assets/images/end.png') }}", // url
                    scaledSize: new google.maps.Size(25, 25), // scaled size
                    origin: new google.maps.Point(0,0), // origin
                    // anchor: new google.maps.Point(0, 0) // anchor
                };

            for (i = 0; i < locations.length; i++) {  

                var startMarker = new google.maps.Marker({
                        position: new google.maps.LatLng(locations[0][0], locations[0][1]), 
                        map:map,
                        title: "START",
                        icon: iconSt
                    });

                var endMarker =  new google.maps.Marker({
                        position: new google.maps.LatLng(locations[endLoc][0], locations[endLoc][1]), 
                        map:map,
                        title: "END",
                        icon: iconEd
                    });
            
                markers.push(new google.maps.LatLng(locations[i][0], locations[i][1]))
            }

            var flightPath = new google.maps.Polyline({
                path: markers,
                geodesic: true,
                strokeColor: '#FF0000',
                strokeOpacity: 1.0,
                strokeWeight: 2
                });

              flightPath.setMap(map);
              zoomToObject(flightPath);

            function zoomToObject(obj){
                var bounds = new google.maps.LatLngBounds();
                var points = obj.getPath().getArray();
                for (var n = 0; n < points.length ; n++){
                    bounds.extend(points[n]);
                }
                map.fitBounds(bounds);
            }
        }
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('app.map_key') }}&callback=getRequestParameters" type="text/javascript"></script>
    @endpush
@endsection