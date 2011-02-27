<?
require "../common/inc.common.php";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Real Time Earthquake's Around the World</title>
		<link type="text/css" href="css/ui-lightness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />	
		<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
		<script type="text/javascript" src="js/gmap-icons.js"></script>
		<style type="text/css">
			/*demo page css*/
			body{ font: 62.5% "Trebuchet MS", sans-serif; margin: 10px;}
			.demoHeaders { margin-top: 2em; }
			#dialog_link {padding: .4em 1em .4em 20px;text-decoration: none;position: relative;}
			#dialog_link span.ui-icon {margin: 0 5px 0 0;position: absolute;left: .2em;top: 50%;margin-top: -8px;}
			ul#icons {margin: 0; padding: 0;}
			ul#icons li {margin: 2px; position: relative; padding: 4px 0; cursor: pointer; float: left;  list-style: none;}
			ul#icons span.ui-icon {float: left; margin: 0 4px;}
			#content {
				float: left; width: 435px; height: 200px; overflow: auto; background: white; clear: both; border: 1px solid black; margin-top: 10px; display: none;}
			#textProgress { width: 200px; padding: 6px; height: 13px; clear: both; float: left; margin-right: 20px; }
			#loadProgress { float: left; width: 200px; display: none; }
			.quakeData { display: none; }
		</style>			
	</head>
	<body onload="initialize()" onunload="GUnload()">
		<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
			<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
			<strong>Hint:</strong> This page will load data itself, you don't need to refresh.</p>
		</div>	
		<br />
		<div id="textProgress"></div> <div id="loadProgress"></div> 
		<div id="dataSelect" style="float: right;">
			<label for="1day-M1"><input type="radio" id="1day-M1" name="type" onclick="changeSource('1day-M1');" checked="checked" /> Magnitude 1+ Quakes, Last 24 Hours <strong style="color: green">(Updated Live)</strong></label> 
			<label for="7day-M2.5"><input type="radio" id="7day-M2.5" name="type" onclick="changeSource('7day-M2.5');" /> Magnitude 2.5+ Quakes, Last 7 Days</label> 
			<label for="7day-M5"><input type="radio" id="7day-M5" name="type" onclick="changeSource('7day-M5');" /> Magnitude 5+ Quakes, Last 7 Days</label> 			
		</div>
		<br style="clear: both;" />
		<div id="quakeMap" name="quakeMap" style="width: 100%; height: 450px; border: 1px solid #ccc; float: left; clear: both; margin-top: 10px;"></div>
		<div id="content"></div>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?=$_common_google_map_key;?>&sensor=false" type="text/javascript"></script>
    <script type="text/javascript">
	gmarkers = [];
	uri = "1day-M1";
	timer = 0;
	
    function initialize() {
      if (GBrowserIsCompatible()) {
        map = new GMap2(document.getElementById("quakeMap"));
		map.setCenter(new GLatLng(53.126998, -1.980972),2,G_HYBRID_MAP);
		bounds = new GLatLngBounds();        
        
		tectonic = new GGeoXml("http://code.tig.gr/earthquake/tectonic.xml");
		map.addOverlay(tectonic);

      }
    }
		// Fucntion to change source
		function changeSource(uri) {
			// Clear the timer, then set with new 
			clearInterval(timer);
			loadQuakeData(uri);
			timer = setInterval("loadQuakeData(uri)", 60000);
		}
		
		// Function to create a tabbed marker
		function createTabbedMarker(point,title,magnatude) {
			var iconOptions = {};
			iconOptions.width = 20;
			iconOptions.height = 20;
			iconOptions.labelColor = "#000000";
			if (magnatude == '1') {
				iconOptions.primaryColor = "#3a5c00";
				iconOptions.labelColor = "#ffffff";
			} else if (magnatude == '2') {
				iconOptions.primaryColor = "#988e00";
			} else if (magnatude == '3') {
				iconOptions.primaryColor = "#d8a300";
			} else if (magnatude == '4') {
				iconOptions.primaryColor = "#fdd046";
			} else if (magnatude == '5') {
				iconOptions.primaryColor = "#b84200";
			} else if (magnatude == '6') {
				iconOptions.primaryColor = "#b70200";
			} else if (magnatude == '7') {
				iconOptions.primaryColor = "#000000";			
				iconOptions.labelColor = "#ffffff";
			}
			iconOptions.label = magnatude;
			iconOptions.labelSize = 10;
			iconOptions.shape = "circle";
			var icon = MapIconMaker.createFlatIcon(iconOptions);

			var marker = new GMarker(point, {icon: icon});
			var marker_num = gmarkers.length;
			var title_search = encodeURI(escape(title));	
			marker.marker_num = marker_num;
			gmarkers[marker_num] = marker;
			
			GEvent.addListener(gmarkers[marker_num], "click", function() {
			  marker.openInfoWindowTabsHtml(title); 
			});
			return marker;
		}		

		function loadQuakeData(uri) {
			$("#textProgress").text("Checking for Quakes...");
			$("#loadProgress").toggle();
			$("#loadProgress").progressbar({ value: 0 });		
			$.ajax({
				type: "GET",
				url: "/earthquake/" + uri + ".xml",
				dataType: "xml",
				success: function(xml) {
					var total = $(xml).find('entry').size();
					var i = 0; var added = 0;
					$(xml).find('entry').each(function(){
						// Indicate the progress to the user
						i++; var progress = (100/total)*i; $("#loadProgress").progressbar('value',progress);
						
						var eq_id = $(this).find('id').text();
						var eq_title = $(this).find('title').text();
						var eq_updated = $(this).find('updated').text();
						var eq_link = $(this).find('link').attr('href');
						var eq_summary = $(this).find('summary').text();
						var eq_point = $(this).find('georss\\:point').text();
						var eq_elevation = $(this).find('georss\\:elev').text();
						var eq_label = $(this).find('category').attr('label');
						var eq_term = $(this).find('category').attr('term');
						var eq_latlng = eq_point.split(" ",2);
						var eq_latitude = eq_latlng[0];
						var eq_longitude = eq_latlng[1];
						 
						var store_id = eq_updated.replace(":","").replace(":","");

						if( $('#' + store_id).length == 0) {
							added++;
							// Keep track of what we've added to the map by assigning an id to a span
							// listing the title		
							$('#content').prepend('<span id="' + store_id + '" class="quakeData">' + eq_title + '(' + eq_latitude + ' / ' + eq_longitude + ')</span><br />');
														
							// Now we need to add this marker to our google map
							var bounds = new GLatLngBounds();
							var point = new GLatLng(eq_latitude,eq_longitude);
							bounds.extend(point);	
							var marker = createTabbedMarker(point,eq_title,eq_title[2]);
							map.addOverlay(marker);

							$('#' + store_id).fadeIn('slow');							
						}
					});			
					if (added > 0) {
						$("#textProgress").text("Updating complete, added " + added + " new quakes.");
						
						// Realign the viewport
						map.setZoom(map.getBoundsZoomLevel(bounds));
						map.setCenter(bounds.getCenter());
					} else {
						$("#textProgress").text("Updating complete, no new quakes.");
					}
					$("#loadProgress").fadeTo(800,0);
				}
			});
		}		

	$(function(){
		//hover states on the static widgets
		$('#dialog_link, ul#icons li').hover(
			function() { $(this).addClass('ui-state-hover'); }, 
			function() { $(this).removeClass('ui-state-hover'); }
		);
		
		changeSource('1day-M1');
	});		   
</script>
</body>
</html>


