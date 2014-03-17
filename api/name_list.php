  <div id="main" class="container">
    <ol class="breadcrumb">
      <li><a href="<?php echo APP_PATH; ?>">Start</a></li>
      <li class="active">Name</li>
    </ol>
    <div class="row">

    <?php

    $per_column = ceil(LIST_LIMIT / 4);

    $output = "";
    for ($slice = 0; $slice < 4; $slice++) {
      $name_subset = array_slice($data, $per_column*$slice, $per_column); ?>
      <div class='col-md-3'>
        <ul>
    <?php
      foreach($name_subset as $name) {
        echo "<li><a href='$name'>$name</a></li>";
      }
    ?>
        </ul>
      </div>
    <?php } ?>

    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="text-center">
          <ul class="pagination pagination-lg">            
            <?php echo ($page == 1) ? "<li class='disabled'><span>&laquo;</span>" : "<li><a href='.?page=1'>&laquo;</a>"; ?></li>
            <?php echo ($page == 1) ? "<li class='disabled'><span>‹</span>" : ("<li><a href='.?page=" . ($page-1) . "'>‹</a>"); ?></li>
            <?php
            // loop to show links to range of pages around current page
            for ($x = ($page - PAGINATION_RANGE); $x < (($page + PAGINATION_RANGE)  + 1); $x++) {
               // if it's a valid page number...
               if (($x > 0) && ($x <= $max_pages)) {
                  // if we're on current page...
                  if ($x == $page) {
                     // 'highlight' it but don't make a link
                     echo "<li class='active'><span>$page<span></li>";
                  // if not current page...
                  } else {
                     // make it a link
                     echo "<li><a href='.?page=$x'>$x</a></li>";
                  } // end else
               } // end if 
            } // end for
            ?>
<!--            
            <li class="disabled"><span>…</span></li>
-->
            <?php echo ($page == $max_pages) ? "<li class='disabled'><span>›</span>" : "<li><a href='.?page=" . ($page+1) . "'>›</a>"; ?></li>
            <?php echo ($page == $max_pages) ? "<li class='disabled'><span>&raquo;</span>" : "<li><a href='.?page=$max_pages'>&raquo;</a>"; ?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
