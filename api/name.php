<div id="main" class="container">
  <ol class="breadcrumb">
    <li><a href="<?php echo APP_PATH; ?>">Start</a></li>
    <li><a href="<?php echo APP_PATH; ?>name/">Name</a></li>
    <li class="active"><?php echo $name; ?></li>
  </ol>
  <div class="row">
    
    <div class="col-md-12">
      <ul>
      <?php
    
        foreach($data['slices'] as $sex_slice) {
          $sex = ($sex_slice['sex'] == "F") ? "weiblich" : "männlich";
          $id = $sex_slice['@id'];
          echo "<li><a href='$id'>$name ($sex)</a></li>";
        }
    
      ?>
      </ul>
    </div>

  </div>
</div>