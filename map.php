<?php

include 'header.php';
require_once( "lib/php/sparqllib.php" );

$db = sparql_connect( "http://dydra.com/knudmoeller/berliner-vornamen/sparql" );

$name_param = $_GET["name"];
$year_param = $_GET["year"];
$sex_param = $_GET["sex"];

$query = <<<SPARQL
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
PREFIX qb: <http://purl.org/linked-data/cube#> 
PREFIX sdmx-code: <http://purl.org/linked-data/sdmx/2009/code#> 
PREFIX sdmx-dimension: <http://purl.org/linked-data/sdmx/2009/dimension#> 
PREFIX namevoc: <http://data.datalysator.com/vocab/> 


SELECT DISTINCT ?district_name ?count
WHERE {
  BIND("$year_param" AS ?year)
  BIND("$name_param" AS ?name_string)
  BIND(sdmx-code:sex-$sex_param AS ?sex)
  ?obs a qb:Observation ;
    sdmx-dimension:refTime ?year ;
    namevoc:name_dim ?name ;
    namevoc:district_dim ?district ;
    namevoc:count_measure ?count ;
  .

  ?name rdfs:label  ?name_string ;
    namevoc:sex ?sex ;
  .

  ?district rdfs:label ?district_name .

}
ORDER BY desc(?count) ?district_name
SPARQL;

$php_data = array();
$result = sparql_query( $query ); 
while( $row = sparql_fetch_array( $result ) )
{
  $php_data[] = $row;
}

$names = array();
foreach (file("data/names.txt") as $name) {
  $names[] = array("name" => $name);
}
?>

<div class="col-md-8" id="map-cell"></div>
<div class="col-md-4">
  
  <form role="form" enctype="multipart/form-data" action="map.php" method="GET">
    <div class="form-group">
      <label for="name_input">Name</label>
      <input id="name_input" name="name" type="text" class="form-control" placeholder="Name" value="<?php echo $name_param; ?>"/>
    </div>
    <div class="form-group">
      <label for="year_selector">Jahr</label>
      <select id="year_selector" name="year" class="form-control">
        <?php
          $years = array("2012", "2013");
          foreach($years as $year)
          {
            if ($year_param === $year) {
              echo "<option selected='selected'>" . $year . "</option>";
            } else {
              echo "<option>" . $year . "</option>";              
            }
          }
        ?>
      </select>
    </div>
    <div class="form-group">
      <label for="sex_selector">Geschlecht</label>
      <select id="sex_selector" name="sex" class="form-control">
        <?php
          $sexes = array("W", "M");
          foreach($sexes as $sex)
          {
            if ($sex_param === $sex) {
              echo "<option selected='selected'>" . $sex . "</option>";
            } else {
              echo "<option>" . $sex . "</option>";              
            }
          }
        ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Anzeigen</button>
  </form>
  
  
</div>


<script type="text/javascript">

  // Width and height
  var width = 600;
  var height = 450;

  // Create SVG element
  var svg = d3.select("#map-cell")
  			.append("svg")
  			.attr("width", width)
  			.attr("height", height);

  // add a rectangle to see the bound of the svg
  svg.append("rect").attr('width', width).attr('height', height)
    .style('stroke', 'gray').style('fill', 'none');

  // Define quantize scale to sort data values into buckets of color
  // var color = d3.scale.quantize()
  //          .range(["rgb(237,248,233)","rgb(186,228,179)","rgb(116,196,118)","rgb(49,163,84)","rgb(0,109,44)"]);
  var color = d3.scale.quantize()
  					.range(["#ffffe5","#f7fcb9","#d9f0a3","#addd8e","#78c679","#41ab5d","#238443","#006837","#004529"]);

  data = <?php echo json_encode($php_data); ?>;
  
  // Set input domain for color scale
	color.domain([
		d3.min(data, function(d) { return parseInt(d.count); }), 
		d3.max(data, function(d) { return parseInt(d.count); })
	]);
	
	// Load in topoJSON data
		d3.json("data/bezirke.json", function(error, berlin) {
		
		  var geojson = topojson.feature(berlin, berlin.objects.berliner_bezirke)
		
      // Create a unit projection.
      var projection = d3.geo.mercator()
          .scale(1)
          .translate([0, 0]);

      // Create a path generator.
      var path = d3.geo.path()
          .projection(projection);


      // Compute the bounds of a feature of interest, then derive scale & translate.
      var b = path.bounds(geojson),
          s = .95 / Math.max((b[1][0] - b[0][0]) / width, (b[1][1] - b[0][1]) / height),
          t = [(width - s * (b[1][0] + b[0][0])) / 2, (height - s * (b[1][1] + b[0][1])) / 2];

      // Update the projection to use computed scale & translate.
      projection
          .scale(s)
          .translate(t);
          
      for (var i = 0; i < data.length; i++) {

        // Grab district name
        var dataDistrict = data[i].district_name;

        // Grab data value, and convert from string to int
        var dataCount = parseInt(data[i].count);

        for (var j = 0; j < geojson.features.length; j++) {

          var jsonDistrict = geojson.features[j].properties.name;

          if (dataDistrict == jsonDistrict) {
            geojson.features[j].properties.count = dataCount;
            break;                
          }
        }
      }
      
      // Bind data and create one path per GeoJSON feature
      svg.selectAll("path")
        .data(geojson.features)
        .enter()
        .append("path")
        .attr("d", path)
        // .attr("class", "inner-boundary")
        // .attr("stroke", "orange")
        // .attr("stroke-width", "2")
        // .attr("stroke-linejoin", "round")
        .style("fill", function(d) {
          // Get data value
          var count = d.properties.count;
          if (count) {
            return color(count);
          } else {
            return "#ccc";
          }
        });
   
        svg.append("path")
          .datum(topojson.mesh(berlin, berlin.objects.berliner_bezirke, function(a, b) { return a !== b ; }))
          .attr("d", path)
          .attr("class", "inner-boundary");

        svg.append("path")
          .datum(topojson.mesh(berlin, berlin.objects.berliner_bezirke, function(a, b) { return a === b ; }))
          .attr("d", path)
          .attr("class", "outer-boundary");

        svg.selectAll(".count-label")
          .data(geojson.features)
          .enter()
          .append("text")
          .attr("class", function(d) { return "count-label " + d.id; })
          .attr("transform", function(d) { return "translate(" + path.centroid(d) + ")"; })
          .attr("dy", ".35em")
          .text(function(d) { return d.properties.count; }
         );

		});
  
    // populate name typeahead text field
    var all_names = <?php echo json_encode($names); ?>;
    // var all_names = [
    //   { "name": "Barbara" },
    //   { "name": "Berit" },
    //   { "name": "Fritz" },
    //   { "name": "Franz" },
    //   { "name": "Ilse" },
    //   { "name": "Inke" },
    //   { "name": "Knud" },
    //   { "name": "Konrad" }
    // ];
    
    var name_suggest = new Bloodhound({
      datumTokenizer: function(d) { return Bloodhound.tokenizers.whitespace(d.name); },
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      local: all_names
    });
    
    name_suggest.initialize();
  
    $('#name_input').typeahead(null, {
      displayKey: 'name',
      source: name_suggest.ttAdapter()
    });
    
</script>


<?php

include 'footer.php';


?>