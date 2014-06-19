<?php
  echo '<?xml version="1.0" encoding="UTF-8"?>';
  require_once("def.inc");
  
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
	<title>Berliner Vornamen | Datalysator</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="<?php echo APP_PATH; ?>lib/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo APP_PATH; ?>css/vornamen_style.css" />	
	<script type="text/javascript" src="<?php echo APP_PATH; ?>lib/d3/d3.v3.js"></script>
	<script type="text/javascript" src="<?php echo APP_PATH; ?>lib/js/topojson.v1.min.js"></script>
  <script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
  <script type="text/javascript" src="<?php echo APP_PATH; ?>lib/js/typeahead.bundle.js"></script>
  <script type="text/javascript" src="<?php echo APP_PATH; ?>lib/js/handlebars-v1.3.0.js"></script>
</head>

<body class="index home no-sidebar">
  <header class="header">
    <div class="container">
    	<div id="sitename"><a href="<?php echo APP_PATH; ?>">Berliner Vornamen</a></div>
    </div>
  </header>
  <div id="search">
    <form id="formId" role="form" enctype="multipart/form-data" action="<?php echo APP_PATH; ?>api/name_process.php" method="GET">
      <div id="tt-container">
        <input id="name_input" name="name" type="text" class="form-control" placeholder="Name"/>
      </div>
      <div id="select-container">
        <select class="form-control" id="sex_select" name="sex">
          <option value="F">Weiblich</option>
          <option value="M">Männlich</option>
        </select>
      </div>
      <input type="submit" style="visibility: hidden; position: fixed;"/>
    </form>
  </div>

<script type="text/javascript">

d3.json("<?php echo APP_PATH; ?>data/names.json", function(error, all_names) {
  
  d3.json("<?php echo APP_PATH; ?>data/charMap.json", function(error, charMap) {
    
    var normalize = function(str) {
      $.each(charMap, function(chars, normalized) {
        var regex = new RegExp('[' + chars + ']', 'gi');
        str = str.replace(regex, normalized);
      });

      return str;
    }

    var queryTokenizer = function(q) {
      var normalized = normalize(q);
      return Bloodhound.tokenizers.whitespace(normalized);
    };

    // populate name typeahead text field
    var name_suggest = new Bloodhound({
      datumTokenizer: function(d) { return Bloodhound.tokenizers.whitespace(normalize(d.name, charMap)); },
      queryTokenizer: queryTokenizer,
      local: all_names,
      limit: 15
    });

    name_suggest.initialize();

    $('#name_input').typeahead(null, {
      displayKey: 'name',
      source: name_suggest.ttAdapter(),
      templates: {
        suggestion: Handlebars.compile([
          '<span class="name-string">{{name}}</span>',
          '<span class="name-sex">{{sex}}</span>'
        ].join(''))
      }
    })
    .on("typeahead:selected", function (event, data, dataset) {
        var sex = data.sex;
        if (sex === "W") {
          sex = "F";
        }
        $("#sex_select option")
          .each(function() { this.selected = (this.value === sex); });
        document.forms["formId"].submit();
    });

  });
  
});

$(document).ready(function(){
    $("#formId").submit(function(event){
        console.log("bunga!");
        event.preventDefault();
        window.location.href = <?php echo APP_PATH; ?> + "name/" + 
                               encodeURI($("#name_input").val()) + "/" +
                               encodeURI($("#sex_select").val());
    });
});

</script>

