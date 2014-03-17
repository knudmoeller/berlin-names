<div id="main" class="container">
  <ol class="breadcrumb">
    <li><a href="<?php echo APP_PATH; ?>">Start</a></li>
    <li><a href="<?php echo APP_PATH; ?>name/">Name</a></li>
    <li><a href="<?php echo APP_PATH; ?>name/<?php echo $name; ?>"><?php echo $name; ?></a></li>
    <li class="active"><?php echo ($sex == "F") ? "weiblich" : "männlich"; ?></li>
  </ol>
  <div class="row">
    <div class="col-md-12">
      <!-- Nav tabs -->
      <ul id="year-selector" class="nav nav-tabs">
        <?php
          foreach($data["slices"] as $slice) {
            $year = $slice["year"];
            $year_id = "year" . $year;
            echo "<li><a href='#$year_id' data-toggle='tab'>$year</a></li>";
          }
        ?>
      </ul>

      <!-- Tab panes -->
      <div class="tab-content">
        <?php
          foreach($data["slices"] as $slice) {
            $year = $slice["year"];
            $year_id = "year" . $year;
            echo "<div class='tab-pane' id='$year_id'></div>";
          }
        ?>
      </div>

    </div>
  </div>
</div>

<script type="text/javascript">

  $( ".nav-tabs li:last-child" )
    .addClass( "active" );
  $( ".tab-content div:last-child" )
    .addClass( "active" );


  // Load in topoJSON data
	d3.json("<?php echo APP_PATH; ?>data/bezirke.json", function(error, berlin) {
  
    // Width and height
    var width = 500;
    var height = 370;

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
        s = .90 / Math.max((b[1][0] - b[0][0]) / width, (b[1][1] - b[0][1]) / height),
        t = [(width - s * (b[1][0] + b[0][0])) / 2, (height - s * (b[1][1] + b[0][1])) / 2];

    // Update the projection to use computed scale & translate.
    projection
        .scale(s)
        .translate(t);

    var data = <?php echo json_encode($data); ?>;

    var color = d3.scale.quantize()
      .range(["#ffffe5","#f7fcb9","#d9f0a3","#addd8e","#78c679","#41ab5d","#238443","#006837","#004529"]);
    color.domain([1, <?php echo MAX_COUNT; ?>]);
    var svg = {};    
    var map = {};
    for (var k = 0; k < data.slices.length; k++) {
      var slice = data.slices[k];
      var year = slice.year;
      var year_id = "#year" + year;
      var counts = slice.counts;
      var total = slice.total;
      
      // Create SVG element
      svg[year] = d3.select(year_id)
      			.append("svg")
      			.attr("width", width)
      			.attr("height", height);

      // add a rectangle to see the bound of the svg
      // svg[year].append("rect").attr('width', width).attr('height', height)
      //   .style('stroke', 'gray').style('fill', 'none');

      map[year] = $.extend(true, {}, geojson);
      
      for (var i = 0; i < counts.length; i++) {

        // Grab district name
        var dataDistrict = counts[i].district;

        // Grab data value, and convert from string to int
        var dataCount = parseInt(counts[i].count);

        for (var j = 0; j < map[year].features.length; j++) {

          var jsonDistrict = map[year].features[j].properties.name;

          if (dataDistrict == jsonDistrict) {
            map[year].features[j].properties.count = dataCount;
            break;                
          }
        }
      }
     
      // Bind data and create one path per GeoJSON feature
      svg[year].selectAll("path")
        .data(map[year].features)
        .enter()
        .append("path")
        .attr("d", path)
        .style("fill", function(d) {
          return count_color(d);
        })
        .on("mouseover", mouseover)
        .on("mouseout", mouseout);
   
        svg[year].append("path")
          .datum(topojson.mesh(berlin, berlin.objects.berliner_bezirke, function(a, b) { return a !== b ; }))
          .attr("d", path)
          .attr("class", "inner-boundary");
        
        svg[year].append("path")
          .datum(topojson.mesh(berlin, berlin.objects.berliner_bezirke, function(a, b) { return a === b ; }))
          .attr("d", path)
          .attr("class", "outer-boundary");

        svg[year].selectAll(".count-label")
          .data(map[year].features)
          .enter()
          .append("text")
          .attr("class", "count-label")
          .attr("id", function(d) { return "count-" + year + "-" + d.id })
          .attr("transform", function(d) { return "translate(" + path.centroid(d) + ")"; })
          .attr("dy", ".35em")
          .text(function(d) { return d.properties.count; });
      
        svg[year]
          .append("text")
          .attr("class", "count-total")
          .attr("x", 15)
          .attr("y", 25)
          .text("Gesamt: " + total);
          
        svg[year]
          .append("text")
          .attr("id", "district-name-" + year)
          .attr("x", 15)
          .attr("y", 350)
          .text("");

    }

    function mouseover(d, i) {
      var current_year = $("#year-selector>li.active>a").text();
      // change colour of district
      d3.select(this).style("fill", "#b81d18");
      // change colour of district count
      $("#count-" + current_year + "-" + d.id ).css("fill", "#f0f0f0");

      // determine total count
      if (d.properties.count) {
        count = d.properties.count;
      } else {
        count = "-";
      }
      // show total count
      $("#district-name-" + current_year).text(d.properties.name + ": " + count);
    }

    function mouseout(d, i) {
      var current_year = $("#year-selector>li.active>a").text();

      // revert colour of distrct
      d3.select(this).style("fill", count_color(d));

      // revert colour of district count
      $("#count-" + current_year + "-" + d.id ).css("fill", "#b81d18");

      // clear total count
      $("#district-name-" + current_year).text("");
    }
    
    function count_color(d) {
      // Get data value
      var count = d.properties.count;
      if (count) {
        return color(count);
      } else {
        return "#ccc";
      }      
    }

  });


</script>